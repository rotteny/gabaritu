@component('mail::message')
# {{ $apelido }} encerrou o acompanhamento

**{{ $apelido }}** tem 18 anos ou mais e decidiu encerrar o acompanhamento no Gabaritu. A partir de agora você **não vê mais** os dados dele e o vínculo foi desfeito. Isso não pode ser restaurado.

@component('mail::button', ['url' => $url])
Abrir o painel
@endcomponent

Dúvidas? Escreva para {{ $emailContato }}.

O Peixoto respeita quem já nadou para a maioridade.
@endcomponent
