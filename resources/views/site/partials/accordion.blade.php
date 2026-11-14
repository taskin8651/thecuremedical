<div class="accordion accordion-cure" id="{{ $idp }}Acc">
  @foreach ($faqs as $i => $f)
    <div class="accordion-item">
      <h3 class="accordion-header"><button class="accordion-button {{ $i ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $idp }}{{ $f->id }}" aria-expanded="{{ $i ? 'false' : 'true' }}">{{ $f->question }}</button></h3>
      <div id="{{ $idp }}{{ $f->id }}" class="accordion-collapse collapse {{ $i ? '' : 'show' }}" data-bs-parent="#{{ $idp }}Acc"><div class="accordion-body">{!! nl2br(e($f->answer)) !!}</div></div>
    </div>
  @endforeach
</div>
