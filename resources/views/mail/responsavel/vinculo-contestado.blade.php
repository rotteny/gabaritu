@component('mail::message')
# {{ $apelido }} contestou um responsável

**{{ $apelido }}** disse que **{{ $nome }}** ({{ $parentesco }}, e-mail {{ $emailMascarado }}) não é responsável por ele. O acesso de {{ $nome }} aos dados do aluno foi **suspenso agora**.

Se ainda houver responsável ativo, ele pode confirmar a remoção ou restaurar o vínculo no painel. Se não sobrar nenhum, {{ $apelido }} volta a aguardar a autorização de um responsável.

@component('mail::button', ['url' => $url])
Abrir o painel
@endcomponent

Dúvidas ou suspeita de fraude? Escreva para {{ $emailContato }}.

O Peixoto prefere pedir desculpa a deixar estranho no aquário.
@endcomponent
