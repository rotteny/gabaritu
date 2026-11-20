{{-- Peixoto pequeno, com uma fala curta ao lado. Uso: <x-mail::peixoto>Fala</x-mail::peixoto> --}}
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin:8px 0 0;">
<tr>
<td width="64" valign="top" style="padding-right:12px;"><img src="{{ asset('email/peixoto.png') }}" width="56" height="56" alt="Peixoto" style="display:block;border:0;width:56px;height:56px;" /></td>
<td valign="middle" style="font-size:15px;line-height:22px;font-style:italic;">{{ $slot }}</td>
</tr>
</table>
