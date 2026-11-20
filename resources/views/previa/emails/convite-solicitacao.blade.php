@component('mail::message')
# {{ $apelido }} pediu que você seja responsável no Gabaritu

**{{ $apelido }}**@if ($serie) ({{ $serie }})@endif pediu que você seja responsável por ele ({{ $parentesco }}) no Gabaritu. O Gabaritu é um projeto gratuito de estudo para o ENEM, com questionários, revisão e acompanhamento de desempenho. Por lei (ECA Digital), quem tem menos de 18 anos só usa a plataforma depois que um responsável autoriza.

**O que você vê e faz como responsável:**
- vê o desempenho de {{ $apelido }} por matéria, só para leitura;
- escolhe até onde vão as brincadeiras do Peixoto (o teto de zoeira);
- convida outro responsável, transfere a responsabilidade ou revoga a autorização quando quiser;
- pode apagar a conta de {{ $apelido }}.

**Os dados que vamos pedir a você:** só nome, ano de nascimento (para confirmar que você tem 18 anos ou mais) e parentesco, além do e-mail e da senha. Nada de CPF, telefone, endereço ou documento. Guardamos também o registro do seu aceite (data e hora, IP e versão dos termos).

@component('mail::button', ['url' => $url])
{{ $temConta ? 'Entrar e autorizar' : 'Criar conta e autorizar' }}
@endcomponent

O link vale até **{{ $validade }}** (7 dias) e só pode ser usado uma vez. Se você não conhece esse aluno, ignore este e-mail: nada acontece e o pedido expira sozinho.

Leia os [Termos]({{ $urlTermos }}) e a [Política de Privacidade]({{ $urlPrivacidade }}).

O Peixoto, que mora num aquário e entende pouco de burocracia, pediu para avisar que é gente boa e só quer ajudar o seu estudante a gabaritar.

Dúvidas? Escreva para {{ $emailContato }}.
@endcomponent
