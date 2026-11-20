@component('mail::message')
# Convite para acompanhar {{ $apelido }} no Gabaritu

Um responsável de **{{ $apelido }}** convidou você para também acompanhar a conta dele no Gabaritu, um projeto gratuito de estudo para o ENEM, como {{ $parentesco }}.

**Ao aceitar**, você cria (ou usa) a sua conta de responsável, confirma que é responsável legal por {{ $apelido }} e aceita os Termos e a Política de Privacidade. Depois disso você vê o desempenho dele nos estudos e pode limitar o nível de zoeira do Peixoto. Você pode revogar o vínculo quando quiser.

@component('mail::button', ['url' => $url])
{{ $temConta ? 'Entrar e aceitar' : 'Criar conta e aceitar' }}
@endcomponent

O link vale até **{{ $validade }}** e só pode ser usado uma vez. Não reconhece o pedido? Ignore este e-mail e nada acontece.

Leia os [Termos]({{ $urlTermos }}) e a [Política de Privacidade]({{ $urlPrivacidade }}).

O Peixoto balançou a nadadeira em agradecimento. É o jeito dele de dizer obrigado.

Dúvidas? Escreva para {{ $emailContato }}.
@endcomponent
