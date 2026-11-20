@component('mail::message')
# {{ $apelido }} convidou mais um responsável

**{{ $apelido }}** convidou uma pessoa ({{ $parentesco }}, e-mail {{ $emailMascarado }}) para ser responsável também no Gabaritu.

Você não precisa fazer nada se conhece essa pessoa. Se **não** conhece ou não concorda, cancele o convite no painel: enquanto ele estiver pendente, qualquer responsável pode cancelar.

@component('mail::button', ['url' => $url])
Abrir o painel
@endcomponent

O convite vale até **{{ $validade }}**. Algo estranho? Escreva para {{ $emailContato }}.

O Peixoto está de olho.
@endcomponent
