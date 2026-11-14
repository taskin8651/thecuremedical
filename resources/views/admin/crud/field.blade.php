@php
  $name = $f['name'];
  $type = $f['type'];
  $id = 'f_'.str_replace(['[', ']', '.'], '_', $name);
  $col = $f['col'] ?? 6;
  $default = $f['default'] ?? null;
  $raw = array_key_exists('value', $f) ? $f['value'] : ($model->exists ? data_get($model, $name) : $default);
  $value = old($name, $raw);
  if ($value instanceof \DateTimeInterface) {
      $value = $value->format($type === 'datetime' ? 'Y-m-d\TH:i' : 'Y-m-d');
  }
  $err = $errors->first($name) ?: $errors->first($name.'.*');
  $readonly = ! empty($f['readonly']);
@endphp

@if ($type === 'notice')
  <div class="col-12"><div class="notice"><i class="bi bi-shield-exclamation"></i><div>{{ $f['text'] }}</div></div></div>
@elseif ($type === 'hidden')
  <input type="hidden" name="{{ $name }}" value="{{ $value }}">
@else
<div class="col-12 col-md-{{ $col }}">
  @if ($type === 'media_image')
    <label class="form-label">{{ $f['label'] }}</label>
    @if (! empty($f['options']))
      <div class="media-picker">
        @foreach ($f['options'] as $option)
          <label class="media-picker-option" for="{{ $id }}_{{ $option['id'] }}">
            <input id="{{ $id }}_{{ $option['id'] }}" type="radio" name="{{ $name }}" value="{{ $option['id'] }}" @checked((string) $value === (string) $option['id'])>
            <img src="{{ $option['url'] }}" alt="">
            <span>{{ $option['label'] }}</span>
          </label>
        @endforeach
      </div>
    @else
      <div class="form-text">No saved Brand logos yet. Upload an image above to add one to Media Library.</div>
    @endif
  @elseif ($type === 'checkbox')
    <div class="form-check form-switch pt-md-4">
      <input type="hidden" name="{{ $name }}" value="0">
      <input class="form-check-input" type="checkbox" role="switch" id="{{ $id }}" name="{{ $name }}" value="1" @checked((bool) $value) @disabled($readonly)>
      <label class="form-check-label" for="{{ $id }}">{{ $f['label'] }}</label>
    </div>
  @else
    <label class="form-label" for="{{ $id }}">{{ $f['label'] }}@if (is_string($f['rules'] ?? null) && str_contains($f['rules'], 'required'))<span class="text-danger"> *</span>@endif</label>

    @if ($type === 'textarea')
      <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $f['rows'] ?? 3 }}" class="form-control {{ ! empty($f['code']) ? 'code' : '' }} @if ($err) is-invalid @endif" placeholder="{{ $f['placeholder'] ?? '' }}" @readonly($readonly)>{{ $value }}</textarea>

    @elseif ($type === 'select')
      <select id="{{ $id }}" name="{{ $name }}" class="form-select @if ($err) is-invalid @endif" @disabled($readonly)>
        <option value="">{{ $f['empty'] ?? 'Choose...' }}</option>
        @foreach ($f['options'] ?? [] as $optValue => $optLabel)
          <option value="{{ $optValue }}" @selected((string) $value === (string) $optValue)>{{ $optLabel }}</option>
        @endforeach
      </select>

    @elseif ($type === 'multiselect')
      @php($selected = collect(old($name, $raw ?? []))->map(fn ($v) => (string) $v)->all())
      <select id="{{ $id }}" name="{{ $name }}[]" class="form-select @if ($err) is-invalid @endif" multiple size="{{ min(8, max(3, count($f['options'] ?? []))) }}">
        @foreach ($f['options'] ?? [] as $optValue => $optLabel)
          <option value="{{ $optValue }}" @selected(in_array((string) $optValue, $selected, true))>{{ $optLabel }}</option>
        @endforeach
      </select>

    @elseif ($type === 'color')
      <div class="d-flex gap-2"><input type="color" class="form-control form-control-color" value="{{ $value ?: '#0D6B4B' }}" data-mirror="{{ $id }}" aria-label="Pick colour"><input id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" class="form-control" maxlength="7"></div>

    @elseif (in_array($type, ['image', 'images', 'files'], true))
      @php
        $collection = $f['collection'];
        $existing = $model->exists ? $model->getMedia($collection) : collect();
      @endphp
      @if ($existing->isNotEmpty())
        <div class="media-grid">
          @foreach ($existing as $m)
            <div class="media-tile">
              @if (str_starts_with($m->mime_type, 'image/'))<img src="{{ $m->hasGeneratedConversion('thumb') ? $m->getUrl('thumb') : $m->getUrl() }}" alt="">@else<div class="doc"><i class="bi bi-file-earmark-text"></i></div>@endif
              <div class="text-truncate" title="{{ $m->file_name }}">{{ $m->name }}</div>
              @if (! empty($f['primary']))<label><input type="radio" name="primary_media" value="{{ $m->id }}" @checked($m->getCustomProperty('primary'))> Primary</label>@endif
              <label class="text-danger"><input type="checkbox" name="remove_media[{{ $collection }}][]" value="{{ $m->id }}"> Remove</label>
            </div>
          @endforeach
        </div>
      @endif
      @if ($type === 'image')
        <input id="{{ $id }}" type="file" class="image-upload-input @if ($err) is-invalid @endif" name="{{ $name }}" accept=".jpg,.jpeg,.png,.webp" data-preview="{{ $id }}_preview">
        <label class="image-upload-dropzone" for="{{ $id }}" data-upload-for="{{ $id }}">
          <span class="image-upload-icon"><i class="bi bi-cloud-arrow-up"></i></span>
          <span><strong>Choose an image</strong><small>Click here or drag and drop JPG, PNG, or WebP</small></span>
          <span class="image-upload-action">Browse files</span>
        </label>
      @else
        <input id="{{ $id }}" type="file" class="form-control @if ($err) is-invalid @endif" name="{{ $name }}[]" multiple accept="{{ $type === 'files' ? '.pdf,.jpg,.jpeg,.png' : '.jpg,.jpeg,.png,.webp' }}" data-preview="{{ $id }}_preview">
      @endif
      <div class="upload-preview" id="{{ $id }}_preview"></div>

    @elseif ($type === 'money')
      <div class="input-group"><span class="input-group-text">{{ in_array($name, ['gst_rate'], true) ? '%' : '₹' }}</span><input id="{{ $id }}" type="number" step="0.01" min="0" name="{{ $name }}" value="{{ $value }}" class="form-control @if ($err) is-invalid @endif" @readonly($readonly)></div>

    @else
      @php($htmlType = ['email' => 'email', 'tel' => 'tel', 'url' => 'url', 'number' => 'number', 'date' => 'date', 'datetime' => 'datetime-local', 'password' => 'password'][$type] ?? 'text')
      <input id="{{ $id }}" type="{{ $htmlType }}" name="{{ $name }}" value="{{ $type === 'password' ? '' : $value }}" class="form-control @if ($err) is-invalid @endif" placeholder="{{ $f['placeholder'] ?? '' }}" @if ($type === 'password') autocomplete="new-password" @endif @if ($type === 'number') min="0" step="1" @endif @readonly($readonly)>
    @endif
  @endif

  @if ($err)<div class="text-danger small mt-1">{{ $err }}</div>@endif
  @if (! empty($f['help']))<div class="form-text">{{ $f['help'] }}</div>@endif
</div>
@endif
