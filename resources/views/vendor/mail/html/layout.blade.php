<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<title>{{ config('app.name') }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<style>
@media only screen and (max-width: 620px) {
.inner-body, .footer, .content { width: 100% !important; }
.content-cell { padding: 20px 16px !important; }
.button-link { display: block !important; }
}
</style>
{!! $head ?? '' !!}
</head>
<body bgcolor="#35c9ee" style="margin:0;padding:0;background-color:#35c9ee;">

<table class="wrapper" width="100%" bgcolor="#35c9ee" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#35c9ee;">
<tr>
<td align="center" style="padding:16px 8px;">
<table class="content" width="600" cellpadding="0" cellspacing="0" role="presentation" style="width:600px;max-width:100%;">
{!! $header ?? '' !!}

<!-- Corpo: cartão creme, borda de 3px e sombra simulada por borda grossa embaixo e à direita -->
<tr>
<td class="body" style="padding:0 0 4px;">
<table class="inner-body" width="100%" bgcolor="#fff6d9" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#fff6d9;border:3px solid #1b1030;border-right:8px solid #1b1030;border-bottom:8px solid #1b1030;">
<tr>
<td class="content-cell" style="padding:28px 28px 24px;font-family:'Nunito','Segoe UI',Arial,sans-serif;font-weight:700;font-size:16px;line-height:24px;color:#1b1030;">
{!! Illuminate\Mail\Markdown::parse($slot) !!}

{!! $subcopy ?? '' !!}
</td>
</tr>
</table>
</td>
</tr>

{!! $footer ?? '' !!}
</table>
</td>
</tr>
</table>
</body>
</html>
