@component('mail::message')
# Você autorizou {{ $apelido }}

{{ $responsavel }}, a sua autorização foi registrada e **{{ $apelido }}** já pode usar o Gabaritu.

**O que você pode fazer no painel:**
- ver o desempenho de {{ $apelido }} por matéria, só para leitura;
- definir o teto de zoeira do Peixoto;
- convidar outro responsável ou transferir a responsabilidade;
- revogar a autorização (só se houver outro responsável; se você for o único, transfira ou apague a conta do aluno);
- apagar a conta do aluno.

@component('mail::button', ['url' => $url])
Abrir o painel do responsável
@endcomponent

**Registro do aceite:** {{ $aceitoEm }} (horário de Brasília), Termos e Política de Privacidade na versão {{ $versaoTermos }}. Guarde este e-mail como comprovante.

O Peixoto agradece por cuidar de quem estuda.
@endcomponent
