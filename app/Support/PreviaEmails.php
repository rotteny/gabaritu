<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\Response;
use Illuminate\Mail\Markdown;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

/**
 * Prévia local dos e-mails (usada só por routes/previa.php, com APP_ENV=local).
 * As views em previa/emails repetem o texto dos e-mails do responsável com dados de exemplo.
 */
final class PreviaEmails
{
    /** @return array<string, array<string, mixed>> nome => dados de exemplo */
    private static function exemplos(): array
    {
        $comum = [
            'apelido' => 'Estudante',
            'url' => 'https://gabaritu.local/responsavel/convite/abc123',
            'urlTermos' => '/termos',
            'urlPrivacidade' => '/privacidade',
            'emailContato' => (string) config('gabaritu.email_contato'),
            'validade' => '26/11/2026',
            'parentesco' => 'mãe',
            'serie' => '2º ano do médio',
            'temConta' => false,
            'responsavel' => 'Maria Silva',
            'dias' => 3,
            'emailResponsavel' => 'm***a@gmail.com',
            'aceitoEm' => '19/11/2026 às 20:41',
            'versaoTermos' => '2026-11-20',
        ];

        return [
            'convite-solicitacao' => $comum,
            'convite-solicitacao-com-conta' => [...$comum, 'temConta' => true],
            'convite-coresponsavel' => $comum,
            'convite-transferencia' => $comum,
            'cadastro-menor' => $comum,
            'cadastro-adulto' => $comum,
            'aluno-autorizado' => $comum,
            'autorizacao-confirmada' => $comum,
            'autorizacao-recusada' => $comum,
            'aviso-remocao' => $comum,
            'redefinir-senha' => [],
        ];
    }

    public static function indice(): Response
    {
        $itens = '';
        foreach (array_keys(self::exemplos()) as $nome) {
            $itens .= "<li>{$nome}: <a href=\"/_previa/emails/{$nome}\">html</a> · <a href=\"/_previa/emails/{$nome}/texto\">texto</a></li>";
        }

        return new Response("<!doctype html><meta charset=\"utf-8\"><title>Prévia dos e-mails</title><h1>E-mails do Gabaritu</h1><ul>{$itens}</ul>");
    }

    public static function renderizar(string $nome, bool $texto): Response
    {
        $dados = self::exemplos()[$nome] ?? abort(404);
        URL::forceRootUrl(request()->getSchemeAndHttpHost());
        $markdown = app(Markdown::class);

        if ($nome === 'redefinir-senha') {
            $mensagem = (new ResetPassword('token-de-exemplo'))->toMail(new User(['email' => 'estudante@exemplo.com']));
            $dadosMensagem = $mensagem instanceof MailMessage ? $mensagem->data() : [];
            $html = $markdown->render('notifications::email', $dadosMensagem);
            $puro = $markdown->renderText('notifications::email', $dadosMensagem);
        } else {
            $view = str_replace('-com-conta', '', "previa.emails.{$nome}");
            $html = $markdown->render($view, $dados);
            $puro = $markdown->renderText($view, $dados);
        }

        return $texto
            ? new Response((string) $puro, 200, ['Content-Type' => 'text/plain; charset=UTF-8'])
            : new Response((string) $html);
    }
}
