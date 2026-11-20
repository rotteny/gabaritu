@component('mail::message')
# Pedido para ser responsável por {{ $apelido }}

**{{ $nome }}** ({{ $parentesco }}, e-mail {{ $emailMascarado }}) pediu para ser responsável por **{{ $apelido }}** no Gabaritu.

Como {{ $apelido }} já tem responsável, o pedido só vale se **{{ $apelido }} aceitar e um dos responsáveis atuais aprovar** no painel. Se você não conhece essa pessoa, recuse o pedido no painel ou ignore: sem a sua aprovação (ou a de outro responsável), nada acontece.

@component('mail::button', ['url' => $url])
Abrir o painel
@endcomponent

O pedido vale até **{{ $validade }}**. Algo estranho? Escreva para {{ $emailContato }}.

O Peixoto está de olho.
@endcomponent
