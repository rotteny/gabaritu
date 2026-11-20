@component('mail::message')
# Seu acesso foi liberado

{{ $apelido }}, **{{ $responsavel }}** autorizou o seu uso do Gabaritu. Já dá para entrar e estudar.

@component('mail::button', ['url' => $url])
Entrar no Gabaritu
@endcomponent

Lembrete: o seu responsável vê o seu desempenho por matéria e pode limitar o nível de zoeira. Você encontra isso na página Conta.

O Peixoto deu uma volta no aquário de alegria. Foram dois segundos, mas foram intensos.
@endcomponent
