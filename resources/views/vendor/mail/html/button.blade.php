@props([
    'url',
    'color' => 'primary',
    'align' => 'center',
])
@php
    // Fundos do design system; o texto é sempre ink (#1b1030), que passa de 4,5:1 em todos.
    $fundo = match ($color) {
        'success' => '#7ddc1f',
        'error' => '#ff3d2e',
        'white' => '#ffffff',
        default => '#ff8a1f',
    };
@endphp
<table class="action" align="{{ $align }}" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}" style="padding:8px 0 16px;">
<table border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td bgcolor="{{ $fundo }}" style="background-color:{{ $fundo }};border:3px solid #1b1030;border-right:6px solid #1b1030;border-bottom:6px solid #1b1030;">
<a href="{{ $url }}" class="button-link" target="_blank" rel="noopener" style="display:inline-block;padding:12px 24px;font-family:'Titan One','Arial Black',Arial,sans-serif;font-weight:400;font-size:18px;line-height:22px;color:#1b1030;text-decoration:none;">{!! $slot !!}</a>
</td>
</tr>
</table>
</td>
</tr>
</table>
