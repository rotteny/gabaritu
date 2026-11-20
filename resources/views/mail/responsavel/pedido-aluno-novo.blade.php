@component('mail::message')
# {{ $nome }} quer ser seu responsável no Gabaritu

**{{ $nome }}** ({{ $parentesco }}) pediu para ser seu responsável no Gabaritu, um projeto gratuito de estudo para o ENEM. Quem tem menos de 18 anos só usa a plataforma com a autorização de um responsável.

Se você conhece {{ $nome }} e quer seguir por aqui, crie a sua conta pelo botão abaixo. No cadastro, você confirma que {{ $nome }} é o seu responsável e a sua conta já nasce liberada, porque ele já declarou e aceitou os Termos. Se você **não** confirmar, o cadastro segue o fluxo normal e pede o e-mail de outro responsável.

@component('mail::button', ['url' => $url])
Criar a minha conta
@endcomponent

Quem tem 18 anos ou mais não precisa de responsável: este pedido não vale para maiores de idade. O link vale até **{{ $validade }}** (7 dias). Se você não conhece essa pessoa, ignore este e-mail: nada acontece.

Leia os [Termos]({{ $urlTermos }}) e a [Política de Privacidade]({{ $urlPrivacidade }}).

O Peixoto desconfia de adulto estranho, mas deixa você decidir.

Dúvidas? Escreva para {{ $emailContato }}.
@endcomponent
