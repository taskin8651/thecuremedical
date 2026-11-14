<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>@yield('code') | {{ config('app.name') }}</title>
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#F5F8F6;color:#172B24;font-family:system-ui,Segoe UI,Arial,sans-serif;padding:24px}
.box{max-width:460px;text-align:center}.code{font-size:4rem;font-weight:800;color:#0D6B4B;line-height:1}h1{font-size:1.4rem;margin:.6rem 0}p{color:#718078}
a{display:inline-block;margin:.3rem;padding:.6rem 1.2rem;border-radius:12px;background:#0D6B4B;color:#fff;text-decoration:none;font-weight:600}a.o{background:#fff;color:#0D6B4B;border:1px solid #0D6B4B}</style></head>
<body><div class="box"><div class="code">@yield('code')</div><h1>@yield('title')</h1><p>@yield('message')</p><a href="{{ url('/') }}">Go to home</a> <a class="o" href="javascript:history.back()">Go back</a></div></body></html>
