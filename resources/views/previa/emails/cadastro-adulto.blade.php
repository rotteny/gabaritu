@component('mail::message')
# Sua conta está pronta

{{ $apelido }}, o seu cadastro no Gabaritu deu certo. É só entrar e começar a estudar.

@component('mail::button', ['url' => $url])
Entrar
@endcomponent

O Peixoto já preparou o aquário e prometeu não zoar demais no primeiro dia. Prometeu.
@endcomponent
