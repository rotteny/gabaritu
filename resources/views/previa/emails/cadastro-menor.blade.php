@component('mail::message')
# Falta a autorização do seu responsável

{{ $apelido }}, o seu cadastro no Gabaritu foi recebido, mas por lei (ECA Digital) quem tem menos de 18 anos só usa a plataforma depois que um responsável autoriza. Enviamos o convite para **{{ str_replace('*', '\*', $emailResponsavel) }}**.

**O que acontece a seguir:**
- o seu responsável abre o convite, cria a conta dele (ou entra) e autoriza;
- assim que ele autorizar, você recebe um e-mail e a sua conta é liberada;
- o convite vale até **{{ $validade }}** (7 dias). Se o e-mail não chegar, você pode reenviar ou corrigir o endereço na tela de espera.

@component('mail::button', ['url' => $url])
Abrir a tela de espera
@endcomponent

Se a conta ficar mais de 30 dias sem autorização, ela é apagada (avisamos 3 dias antes).

O Peixoto está torcendo para o seu responsável ler o e-mail rapidinho.
@endcomponent
