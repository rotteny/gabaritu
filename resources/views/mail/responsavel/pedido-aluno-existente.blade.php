@component('mail::message')
# {{ $nome }} quer ser seu responsável no Gabaritu

**{{ $nome }}** ({{ $parentesco }}) pediu para ser seu responsável no Gabaritu. O pedido aparece na sua página Conta, na seção "Quem acompanha você", com os botões Aceitar e Recusar.

@if ($precisaAprovacao)
Como você já tem responsável, o pedido só vale se **você aceitar e um dos seus responsáveis atuais também aprovar**. Eles foram avisados.
@else
Como você ainda não tem responsável ativo, basta você aceitar.
@endif

@component('mail::button', ['url' => $url])
Ver o pedido
@endcomponent

O pedido vale até **{{ $validade }}** (7 dias). Se você não conhece essa pessoa, recuse ou ignore este e-mail: nada acontece sem o seu aceite.

Leia os [Termos]({{ $urlTermos }}) e a [Política de Privacidade]({{ $urlPrivacidade }}).

O Peixoto só deixa entrar no aquário quem você aprovar.

Dúvidas? Escreva para {{ $emailContato }}.
@endcomponent
