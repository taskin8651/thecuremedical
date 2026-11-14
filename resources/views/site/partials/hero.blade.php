{{-- Inner page banner. $crumbs: [label => url|null], $heading, $lead --}}
<section class="page-hero"><div class="container">
  <ol class="breadcrumb-c"><li><a href="{{ route('site.home') }}">Home</a></li>@foreach ($crumbs ?? [] as $label => $url)<li @if (! $url) aria-current="page" @endif>@if ($url)<a href="{{ $url }}">{{ $label }}</a>@else{{ $label }}@endif</li>@endforeach</ol>
  <h1 class="h2 mb-2">{{ $heading }}</h1>
  @if (! empty($lead))<p class="text-muted-c mb-0" style="max-width:680px">{{ $lead }}</p>@endif
</div></section>
