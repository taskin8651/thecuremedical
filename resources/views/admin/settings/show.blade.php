@extends('admin.layouts.app')
@section('title', $scope === 'homepage' ? 'Homepage' : 'Settings')
@section('content')
@php
  $baseRoute = $scope === 'homepage' ? 'admin.content.homepage' : 'admin.settings.show';
  $putRoute = $scope === 'homepage' ? 'admin.content.homepage.update' : 'admin.settings.update';
  $keys = array_values(array_filter($tabs[$active][1], fn ($k) => isset($defs[$k])));
  $hasFile = collect($keys)->contains(fn ($k) => $defs[$k][0] === 'image');
@endphp
<div class="page-head"><div><h1>{{ $scope === 'homepage' ? 'Homepage' : 'Settings' }}</h1><p class="sub">{{ $scope === 'homepage' ? 'Control every section of the public homepage.' : 'Business details, website, SEO and compliance switches.' }}</p></div></div>
<div class="tabs-c">@foreach ($tabs as $k => [$label])<a href="{{ route($baseRoute, ['tab' => $k]) }}" class="{{ $k === $active ? 'active' : '' }}">{{ $label }}</a>@endforeach</div>

@if ($active === 'features')
  <div class="notice mb-3"><i class="bi bi-shield-exclamation"></i><div>Online ordering, payments and prescriptions stay <strong>off</strong> until the owner confirms the licensing, prescription, delivery and tax rules that apply. No payment gateway is connected. Only a Super Admin can switch these on.</div></div>
@endif

<form method="POST" action="{{ route($putRoute) }}" @if ($hasFile) enctype="multipart/form-data" @endif data-once novalidate>
  @csrf @method('PUT')
  <input type="hidden" name="tab" value="{{ $active }}">
  <div class="card-c mb-3"><div class="card-body-c"><div class="row g-3">
    @foreach ($keys as $k)
      @php
        [$type, $label, $default, $rules] = $defs[$k];
        $help = $defs[$k][4] ?? null;
        $cur = setting($k);
        $val = old($k, $cur);
        $locked = in_array($k, $compliance, true) && ! $canToggleCompliance;
        $dis = ! $canEdit || $locked;
        $wide = in_array($type, ['textarea', 'code'], true);
        $err = $errors->first($k);
      @endphp
      <div class="col-12 col-md-{{ $wide ? 12 : 6 }}">
        @if ($type === 'checkbox')
          <div class="form-check form-switch pt-1">
            <input type="hidden" name="{{ $k }}" value="0">
            <input class="form-check-input" type="checkbox" role="switch" id="s_{{ $k }}" name="{{ $k }}" value="1" @checked(filter_var($val, FILTER_VALIDATE_BOOLEAN)) @disabled($dis)>
            <label class="form-check-label" for="s_{{ $k }}">{{ $label }}</label>
          </div>
          @if ($dis && $locked)<input type="hidden" name="{{ $k }}" value="{{ filter_var($cur, FILTER_VALIDATE_BOOLEAN) ? 1 : 0 }}">@endif
        @else
          <label class="form-label" for="s_{{ $k }}">{{ $label }}@if (str_contains((string) $rules, 'required'))<span class="text-danger"> *</span>@endif</label>
          @if ($type === 'textarea' || $type === 'code')
            <textarea id="s_{{ $k }}" name="{{ $k }}" rows="{{ $type === 'code' ? 8 : 3 }}" class="form-control {{ $type === 'code' ? 'code' : '' }} @if ($err) is-invalid @endif" @disabled($dis) @if ($type === 'code') spellcheck="false" @endif>{{ $val }}</textarea>
          @elseif ($type === 'image')
            @if ($url = setting_url($k))<div class="media-grid"><div class="media-tile"><img src="{{ $url }}" alt="">@if (! $dis)<label class="text-danger"><input type="checkbox" name="remove_{{ $k }}" value="1"> Remove</label>@endif</div></div>@endif
            @unless ($dis)<input id="s_{{ $k }}" type="file" name="{{ $k }}" class="form-control @if ($err) is-invalid @endif" accept=".jpg,.jpeg,.png,.webp{{ $k === 'favicon' ? ',.ico' : '' }}" data-preview="p_{{ $k }}"><div class="upload-preview" id="p_{{ $k }}"></div>@endunless
          @elseif ($type === 'color')
            <div class="d-flex gap-2"><input type="color" class="form-control form-control-color" value="{{ $val ?: '#0D6B4B' }}" data-mirror="s_{{ $k }}" @disabled($dis) aria-label="Pick colour"><input id="s_{{ $k }}" name="{{ $k }}" value="{{ $val }}" class="form-control @if ($err) is-invalid @endif" maxlength="7" @readonly($dis)></div>
          @else
            <input id="s_{{ $k }}" type="{{ ['email' => 'email', 'url' => 'url', 'number' => 'number'][$type] ?? 'text' }}" name="{{ $k }}" value="{{ $val }}" class="form-control @if ($err) is-invalid @endif" @readonly($dis) @if ($type === 'number') min="0" step="1" @endif>
          @endif
        @endif
        @if ($err)<div class="text-danger small mt-1">{{ $err }}</div>@endif
        @if ($help)<div class="form-text">{{ $help }}</div>@endif
        @if ($locked)<div class="form-text text-warning">Only a Super Admin can change this.</div>@endif
      </div>
    @endforeach
    @if ($active === 'features' && $canToggleCompliance && $canEdit)
      <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" id="confirm_compliance" name="confirm_compliance" value="1"><label class="form-check-label" for="confirm_compliance">I confirm the shop owner has verified the legal and operational requirements before enabling ordering, payments or prescriptions.</label></div></div>
    @endif
  </div></div></div>
  @if ($canEdit)<div class="pb-4"><button class="btn-c" type="submit"><i class="bi bi-check2"></i> Save {{ strtolower($tabs[$active][0]) }}</button></div>@endif
</form>
@endsection
