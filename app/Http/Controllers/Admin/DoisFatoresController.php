<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Admin\AcaoDeAuditoria;
use App\Domain\Admin\Auditoria;
use App\Domain\Admin\DoisFatores;
use App\Domain\Admin\EstadoDaSessao;
use App\Domain\Admin\LimiteDeCodigos;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** Configuração obrigatória do 2FA (primeiro login) e desafio a cada sessão. */
class DoisFatoresController extends Controller
{
    private const string FLASH_CODIGOS = 'admin.codigos_recuperacao';

    public const string FLASH_RESTANTES = 'admin.recuperacao_restantes';

    public function __construct(private readonly DoisFatores $doisFatores, private readonly Auditoria $auditoria, private readonly LimiteDeCodigos $limite) {}

    public function show(Request $request): Response|RedirectResponse
    {
        $admin = $this->admin();
        $estado = new EstadoDaSessao($request->session());
        $props = ['acoes' => $this->acoes()];

        return match (true) {
            $request->session()->has(self::FLASH_CODIGOS) => Inertia::render('admin/DoisFatores', $props + ['modo' => 'codigos', 'codigos' => $request->session()->get(self::FLASH_CODIGOS)]),
            ! $admin->temDoisFatoresConfirmado() => Inertia::render('admin/DoisFatores', $props + ['modo' => 'configurar'] + $this->dadosDeConfiguracao($admin)),
            ! $estado->doisFatoresCumprido() => Inertia::render('admin/DoisFatores', $props + ['modo' => 'desafiar']),
            default => redirect()->route('admin.dashboard'),
        };
    }

    public function confirmar(Request $request): RedirectResponse
    {
        $admin = $this->admin();
        abort_if($admin->temDoisFatoresConfirmado(), 403);

        $dados = $request->validate(['codigo' => ['required', 'string', 'max:20']]);
        $this->limite->registrar($admin, (string) $request->ip());

        $codigos = $this->doisFatores->confirmar($admin, $dados['codigo']);
        if ($codigos === null) {
            throw ValidationException::withMessages(['codigo' => 'Código inválido. Confira o horário do celular e tente de novo.']);
        }

        $this->concluirLogin($request, $admin, AcaoDeAuditoria::DoisFatoresConfirmado);

        return redirect()->route('admin.dois-fatores')->with(self::FLASH_CODIGOS, $codigos);
    }

    public function desafiar(Request $request): RedirectResponse
    {
        $admin = $this->admin();
        abort_unless($admin->temDoisFatoresConfirmado(), 403);

        $dados = $request->validate(['codigo' => ['nullable', 'string', 'max:20'], 'recuperacao' => ['nullable', 'string', 'max:30']]);
        if (blank($dados['codigo'] ?? null) && blank($dados['recuperacao'] ?? null)) {
            throw ValidationException::withMessages(['codigo' => 'Informe o código do aplicativo ou um código de recuperação.']);
        }
        $this->limite->registrar($admin, (string) $request->ip());

        $acao = filled($dados['recuperacao'] ?? null)
            ? $this->usarRecuperacao($admin, $dados['recuperacao'])
            : $this->usarCodigo($admin, (string) $dados['codigo']);

        $this->concluirLogin($request, $admin, $acao);

        return redirect()->route('admin.dashboard');
    }

    private function usarCodigo(Admin $admin, string $codigo): AcaoDeAuditoria
    {
        if (! $this->doisFatores->aceitarCodigo($admin, $codigo)) {
            throw ValidationException::withMessages(['codigo' => 'Código inválido ou já usado.']);
        }

        return AcaoDeAuditoria::Login;
    }

    private function usarRecuperacao(Admin $admin, string $codigo): AcaoDeAuditoria
    {
        if (! $this->doisFatores->consumirCodigoDeRecuperacao($admin, $codigo)) {
            throw ValidationException::withMessages(['recuperacao' => 'Código de recuperação inválido ou já usado.']);
        }

        session()->flash(self::FLASH_RESTANTES, $this->doisFatores->restantes($admin));

        return AcaoDeAuditoria::CodigoDeRecuperacaoUsado;
    }

    private function concluirLogin(Request $request, Admin $admin, AcaoDeAuditoria $acao): void
    {
        $request->session()->regenerate();
        $estado = new EstadoDaSessao($request->session());
        $estado->atualizarAssinatura($admin->refresh());
        $estado->marcarDoisFatoresCumprido();
        $admin->forceFill(['ultimo_login_em' => now()])->save();
        $this->auditoria->registrar($admin, $acao);
    }

    /** @return array{segredo: string, qr_svg: string, otpauth_url: string} */
    private function dadosDeConfiguracao(Admin $admin): array
    {
        $segredo = $this->doisFatores->segredoParaConfigurar($admin);

        return ['segredo' => $segredo, 'qr_svg' => $this->doisFatores->qrSvg($admin), 'otpauth_url' => $this->doisFatores->urlOtpauth($admin)];
    }

    /** @return array<string, string> */
    private function acoes(): array
    {
        return [
            'confirmar' => route('admin.dois-fatores.confirmar', absolute: false),
            'desafiar' => route('admin.dois-fatores.desafiar', absolute: false),
            'continuar' => route('admin.dashboard', absolute: false),
            'sair' => route('admin.logout', absolute: false),
        ];
    }

    private function admin(): Admin
    {
        $admin = Auth::guard('admin')->user();
        abort_unless($admin instanceof Admin, 403);

        return $admin;
    }
}
