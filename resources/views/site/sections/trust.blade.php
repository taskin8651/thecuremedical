@if ($trust->isNotEmpty())
<section class="trust" aria-label="Business highlights"><div class="container"><div class="trust-card">
  @foreach ($trust as $t)<div class="trust-item"><span class="ic"><i class="bi {{ $t->icon ?: 'bi-check2-circle' }}"></i></span><div><strong>{{ $t->title }}</strong><span>{{ $t->text }}</span></div></div>@endforeach
</div></div></section>
@endif
