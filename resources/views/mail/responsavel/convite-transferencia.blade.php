@component('mail::message')
# Convite para assumir a responsabilidade por {{ $apelido }}

Um responsável de **{{ $apelido }}** no Gabaritu quer transferir a responsabilidade para você, como {{ $parentesco }}.

**Ao aceitar**, você confirma que é responsável legal por {{ $apelido }}, aceita os Termos e a Política de Privacidade e passa a ver o desempenho dele e a definir o teto de zoeira. Quando você aceita, o vínculo do responsável anterior é encerrado. Até lá, ele continua responsável.

@component('mail::button', ['url' => $url])
{{ $temConta ? 'Entrar e aceitar' : 'Criar conta e aceitar' }}
@endcomponent

O link vale até **{{ $validade }}** e só pode ser usado uma vez. Se você não esperava este convite, ignore o e-mail: nada muda.

Leia os [Termos]({{ $urlTermos }}) e a [Política de Privacidade]({{ $urlPrivacidade }}).

O Peixoto pediu para dizer que a troca de turno no aquário é sempre tranquila. Com você, vai ser também.

Dúvidas? Escreva para {{ $emailContato }}.
@endcomponent
