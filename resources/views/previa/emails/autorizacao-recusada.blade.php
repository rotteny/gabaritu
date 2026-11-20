@component('mail::message')
# Seu responsável não autorizou a conta

{{ $apelido }}, o responsável que você indicou recusou a autorização. A sua conta continua bloqueada: sem autorização, ela não pode ser usada.

Você pode corrigir o e-mail do responsável (se digitou errado) ou indicar outra pessoa na tela de espera.

@component('mail::button', ['url' => $url])
Abrir a tela de espera
@endcomponent

O Peixoto está ao seu lado. Ele só não sabe dar bronca em adulto.
@endcomponent
