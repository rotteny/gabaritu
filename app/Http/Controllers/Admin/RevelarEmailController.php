<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Admin\AcaoDeAuditoria;
use App\Domain\Admin\Auditoria;
use App\Domain\Admin\DoisFatores;
use App\Domain\Admin\EstadoDaSessao;
use App\Domain\Admin\LimiteDeCodigos;
use App\Enums\TipoConta;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/** "Revelar e-mail": exige motivo, reconfirma o TOTP se passaram 15 minutos e deixa rastro na auditoria. */
class RevelarEmailController extends Controller
{
    public const string FLASH_EMAIL = 'admin.email_revelado';

    private const int MOTIVO_MINIMO = 5;

    private const int MOTIVO_MAXIMO = 200;

    public function __construct(private readonly DoisFatores $doisFatores, private readonly Auditoria $auditoria, private readonly LimiteDeCodigos $limite) {}

    public function aluno(Request $request, int $aluno): RedirectResponse
    {
        return $this->revelar($request, $this->conta($aluno, TipoConta::Aluno), 'admin.alunos.show');
    }

    public function responsavel(Request $request, int $responsavel): RedirectResponse
    {
        return $this->revelar($request, $this->conta($responsavel, TipoConta::Responsavel), 'admin.responsaveis.show');
    }

    private function revelar(Request $request, User $alvo, string $rotaDaFicha): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();
        assert($admin instanceof Admin);
        $estado = new EstadoDaSessao($request->session());

        $dados = $request->validate([
            'motivo' => ['required', 'string', 'min:'.self::MOTIVO_MINIMO, 'max:'.self::MOTIVO_MAXIMO],
            'codigo' => [$estado->reconfirmadoRecentemente() ? 'nullable' : 'required', 'string', 'max:20'],
        ]);

        if (! $estado->reconfirmadoRecentemente()) {
            $this->reconfirmar($request, $admin, $estado, (string) $dados['codigo']);
        }

        $this->auditoria->registrar($admin, AcaoDeAuditoria::RevelarEmail, $alvo, trim($dados['motivo']));

        return redirect()->route($rotaDaFicha, $alvo)->with(self::FLASH_EMAIL, $alvo->email);
    }

    private function reconfirmar(Request $request, Admin $admin, EstadoDaSessao $estado, string $codigo): void
    {
        $this->limite->registrar($admin, (string) $request->ip());

        if (! $this->doisFatores->aceitarCodigo($admin, $codigo)) {
            throw ValidationException::withMessages(['codigo' => 'Código inválido ou já usado.']);
        }

        $estado->reconfirmar();
    }

    private function conta(int $id, TipoConta $tipo): User
    {
        return User::query()->where('tipo', $tipo->value)->findOrFail($id);
    }
}
