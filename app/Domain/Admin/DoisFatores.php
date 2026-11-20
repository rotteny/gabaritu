<?php

namespace App\Domain\Admin;

use App\Models\Admin;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\DB;
use PragmaRX\Google2FA\Google2FA;

/** TOTP do admin: segredo cifrado, código aceito uma vez só por janela e códigos de recuperação de uso único com hash. */
class DoisFatores
{
    private const int TAMANHO_DO_SEGREDO = 32;

    private const int TAMANHO_DO_QR = 192;

    private const int CARACTERES_DO_CODIGO = 12;

    private const string ALFABETO_DOS_CODIGOS = 'abcdefghjkmnpqrstuvwxyz23456789';

    public function __construct(private readonly Google2FA $totp) {}

    /** Segredo ainda não confirmado: criado uma vez e reaproveitado até o admin confirmar. */
    public function segredoParaConfigurar(Admin $admin): string
    {
        if ($admin->dois_fatores_segredo === null) {
            $admin->forceFill(['dois_fatores_segredo' => $this->totp->generateSecretKey(self::TAMANHO_DO_SEGREDO)])->save();
        }

        return (string) $admin->dois_fatores_segredo;
    }

    public function urlOtpauth(Admin $admin): string
    {
        return $this->totp->getQRCodeUrl(config('app.name'), $admin->email, (string) $admin->dois_fatores_segredo);
    }

    public function qrSvg(Admin $admin): string
    {
        $svg = (new Writer(new ImageRenderer(
            new RendererStyle(self::TAMANHO_DO_QR, 0, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(45, 55, 72))),
            new SvgImageBackEnd,
        )))->writeString($this->urlOtpauth($admin));

        return trim(substr($svg, (int) strpos($svg, "\n") + 1));
    }

    /**
     * Confirma o primeiro código e liga o 2FA.
     *
     * @return list<string>|null os códigos de recuperação em claro (única vez em que existem), ou null se o código errou
     */
    public function confirmar(Admin $admin, string $codigo): ?array
    {
        if (! $this->aceitarCodigo($admin, $codigo)) {
            return null;
        }

        $codigos = $this->gerarCodigosDeRecuperacao($admin);
        $admin->forceFill(['confirmado_em' => now()])->save();

        return $codigos;
    }

    public function aceitarCodigo(Admin $admin, string $codigo): bool
    {
        $segredo = $admin->dois_fatores_segredo;
        $codigo = preg_replace('/\s+/', '', $codigo) ?? '';

        if ($segredo === null || ! preg_match('/^\d{6}$/', $codigo)) {
            return false;
        }

        $passo = $this->totp->verifyKeyNewer($segredo, $codigo, $admin->ultimo_passo_totp ?? 0);

        return is_int($passo) && $this->gravarPasso($admin, $passo);
    }

    /** Consome um código de recuperação: vale uma vez, mesmo com duas requisições simultâneas. */
    public function consumirCodigoDeRecuperacao(Admin $admin, string $codigo): bool
    {
        return DB::transaction(function () use ($admin, $codigo): bool {
            $atual = Admin::query()->lockForUpdate()->find($admin->id);
            if (! $atual instanceof Admin) {
                return false;
            }

            $hash = $this->hashDoCodigo($codigo);
            $guardados = $atual->codigos_recuperacao ?? [];
            $restantes = array_values(array_filter($guardados, fn (string $guardado) => ! hash_equals($guardado, $hash)));

            if (count($restantes) === count($guardados)) {
                return false;
            }

            $atual->forceFill(['codigos_recuperacao' => $restantes])->save();
            $admin->setRawAttributes($atual->getAttributes(), true);

            return true;
        });
    }

    /** @return list<string> */
    public function gerarCodigosDeRecuperacao(Admin $admin): array
    {
        $codigos = array_map(fn () => $this->novoCodigo(), range(1, (int) config('gabaritu.admin.codigos_recuperacao')));
        $admin->forceFill(['codigos_recuperacao' => array_map($this->hashDoCodigo(...), $codigos)])->save();

        return $codigos;
    }

    public function restantes(Admin $admin): int
    {
        return count($admin->codigos_recuperacao ?? []);
    }

    public function resetar(Admin $admin): void
    {
        $admin->forceFill([
            'dois_fatores_segredo' => null,
            'codigos_recuperacao' => null,
            'ultimo_passo_totp' => null,
            'confirmado_em' => null,
        ])->save();
    }

    /** Só avança o passo se ninguém o usou antes: o mesmo código nunca vale duas vezes. */
    private function gravarPasso(Admin $admin, int $passo): bool
    {
        $gravou = Admin::query()->whereKey($admin->id)
            ->where(fn ($q) => $q->whereNull('ultimo_passo_totp')->orWhere('ultimo_passo_totp', '<', $passo))
            ->update(['ultimo_passo_totp' => $passo]);

        $admin->setAttribute('ultimo_passo_totp', $passo);
        $admin->syncOriginalAttribute('ultimo_passo_totp');

        return $gravou === 1;
    }

    private function novoCodigo(): string
    {
        $codigo = '';
        for ($i = 0; $i < self::CARACTERES_DO_CODIGO; $i++) {
            $codigo .= self::ALFABETO_DOS_CODIGOS[random_int(0, strlen(self::ALFABETO_DOS_CODIGOS) - 1)];
        }

        return substr($codigo, 0, self::CARACTERES_DO_CODIGO / 2).'-'.substr($codigo, self::CARACTERES_DO_CODIGO / 2);
    }

    private function hashDoCodigo(string $codigo): string
    {
        $normalizado = strtolower(str_replace(['-', ' '], '', $codigo));

        return hash_hmac('sha256', $normalizado, (string) config('app.key'));
    }
}
