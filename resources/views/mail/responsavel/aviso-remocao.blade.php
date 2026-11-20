@component('mail::message')
# Sua conta será apagada em {{ $dias }} dias

{{ $apelido }}, a sua conta no Gabaritu está há quase 30 dias esperando a autorização de um responsável. Se ela não for autorizada, **será apagada em {{ $dias }} dias**, junto com todos os dados.

Para evitar isso, peça ao seu responsável que abra o convite. Você pode reenviar o e-mail ou corrigir o endereço na tela de espera.

@component('mail::button', ['url' => $url])
Abrir a tela de espera
@endcomponent

Se preferir que a conta seja apagada agora, é só excluí-la pela página Conta. Dúvidas: {{ $emailContato }}.

O Peixoto vai sentir a sua falta, mas ele entende.
@endcomponent
