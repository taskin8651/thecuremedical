@php
  $img = $p->imageUrl('thumb');
  $price = public_price($p);
  [$availLabel, $availClass] = public_availability($p);
  $cartUnavailable = public_cart_unavailable_reason($p);
@endphp
<article class="p-card">
  <a class="p-media d-flex" href="{{ route('site.product', $p->slug) }}" aria-label="{{ $p->name }}" tabindex="-1">
    @if ($img)<img src="{{ $img }}" alt="{{ $p->name }}" loading="lazy" style="width:100%;height:100%;object-fit:cover">@else<i class="bi bi-capsule p-ic" aria-hidden="true"></i>@endif
  </a>
  <div class="p-body">
    @if ($p->category)<span class="p-cat">{{ $p->category->name }}</span>@endif
    <h3 class="p-name"><a href="{{ route('site.product', $p->slug) }}">{{ $p->name }}</a></h3>
    @if ($p->brand)<span class="p-brand">{{ $p->brand->name }}</span>@endif
    <div class="d-flex flex-wrap gap-1 mt-1">
      @if ($p->requires_prescription)<span class="badge-c badge-warn"><i class="bi bi-file-earmark-medical"></i> Prescription required</span>@endif
    </div>
    <div class="p-foot">
      <span>
        @if ($price)<strong>{{ $price['now'] }}</strong>@if ($price['mrp']) <s class="small text-muted">{{ $price['mrp'] }}</s>@endif<br>@endif
        <span class="avail {{ $availClass }}">{{ $availLabel }}</span>
      </span>
      @if (! $cartUnavailable)
        <form method="POST" action="{{ route('cart.items.store', $p->id) }}">@csrf<input type="hidden" name="quantity" value="1"><button class="btn-cure btn-sm-c" type="submit"><i class="bi bi-bag-plus"></i> Add to cart</button></form>
      @elseif (! $p->requires_prescription)
        <button class="btn-cure btn-sm-c" type="button" disabled title="{{ $cartUnavailable }}"><i class="bi bi-bag-plus"></i> Add to cart</button>
      @else
        <a class="btn-cure btn-sm-c" href="{{ route('site.contact', ['product' => $p->name]) }}" title="{{ $cartUnavailable }}"><i class="bi bi-chat-left-text"></i> Enquire</a>
      @endif
    </div>
    @if ($cartUnavailable && ! $p->requires_prescription)<small class="d-block text-muted-c mt-2">{{ $cartUnavailable }}</small>@endif
  </div>
</article>
