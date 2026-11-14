@php $img = $a->getFirstMediaUrl('featured', 'thumb'); @endphp
<article class="b-card">
  <a class="b-media" href="{{ route('site.article', $a->slug) }}" tabindex="-1" aria-hidden="true">@if ($img)<img src="{{ $img }}" alt="" loading="lazy" style="width:100%;height:100%;object-fit:cover">@else<i class="bi bi-journal-medical"></i>@endif</a>
  <div class="b-body">
    @if ($a->category)<span class="badge-c align-self-start">{{ $a->category }}</span>@endif
    <h3><a href="{{ route('site.article', $a->slug) }}" class="text-reset">{{ $a->title }}</a></h3>
    <p>{{ \Illuminate\Support\Str::limit($a->excerpt, 120) }}</p>
    <span class="b-meta">{{ ($a->published_at ?? $a->created_at)->format('d M Y') }}</span>
  </div>
</article>
