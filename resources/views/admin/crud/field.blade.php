@php

  $name = $f['name'];

  $type = $f['type'];

  $id = 'f_' . str_replace(['[', ']', '.'], '_', $name);

  $col = $f['col'] ?? 6;

  $default = $f['default'] ?? null;

  $raw = array_key_exists('value', $f) ? $f['value'] : ($model->exists ? data_get($model, $name) : $default);

  $value = old($name, $raw);

  if ($value instanceof \DateTimeInterface) {

      $value = $value->format($type === 'datetime' ? 'Y-m-d\TH:i' : 'Y-m-d');

  }

  $err = $errors->first($name) ?: $errors->first($name . '.*');

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

        $collection = $f['collection'] ?? '';

        $existing = ($model->exists && $collection)

            ? $model->getMedia($collection)

            : collect();



        $isMultiple = in_array($type, ['images', 'files'], true);



        $accept = $type === 'files'

            ? '.pdf,.jpg,.jpeg,.png'

            : '.jpg,.jpeg,.png,.webp';



        $inputName = $name . ($isMultiple ? '[]' : '');

    @endphp



    {{-- Existing Uploaded Files --}}

    @if ($existing->isNotEmpty())

        <div class="media-grid mb-3">

            @foreach ($existing as $m)

                <div class="media-tile">



                    @if (str_starts_with($m->mime_type, 'image/'))

                        <img src="{{ $m->getUrl() }}" alt="">

                    @else

                        <div class="doc">

                            <i class="bi bi-file-earmark-text"></i>

                        </div>

                    @endif



                    <div class="text-truncate">

                        {{ $m->file_name }}

                    </div>



                    @if (!empty($f['primary']))

                        <label>

                            <input type="radio"

                                name="primary_media"

                                value="{{ $m->id }}"

                                @checked($m->getCustomProperty('primary'))>

                            Primary

                        </label>

                    @endif



                    <label class="text-danger">

                        <input type="checkbox"

                            name="remove_media[{{ $collection }}][]"

                            value="{{ $m->id }}">

                        Remove

                    </label>



                </div>

            @endforeach

        </div>

    @endif



    {{-- Upload Component --}}

    <div class="custom-upload-box">



        <input

            type="file"

            id="{{ $id }}"

            name="{{ $inputName }}"

            class="upload-file-input @if ($err) is-invalid @endif"

            accept="{{ $accept }}"

            data-upload-input

            data-preview="{{ $id }}_preview"

            @if ($isMultiple) multiple @endif

        >



        <div class="upload-dropzone" data-dropzone>



            <div class="upload-icon">

                <i class="bi {{ $type === 'files' ? 'bi-file-earmark-arrow-up' : 'bi-cloud-arrow-up' }}"></i>

            </div>



            <h6>

                {{ $type === 'files' ? 'Upload Documents' : 'Upload Images' }}

            </h6>



            <p>Drag and drop files here or browse from your device</p>



            <button type="button"

                class="btn-c-outline btn-sm-c"

                data-upload-button>



                <i class="bi bi-folder2-open"></i>



                {{ $type === 'files' ? 'Browse Documents' : ($type === 'images' ? 'Browse Images' : 'Browse Image') }}



            </button>



            <div class="upload-formats">

                {{ $type === 'files'

                    ? 'PDF, JPG, PNG'

                    : 'JPG, PNG, WebP' }}

                · Maximum 4 MB each

            </div>



        </div>



        <div class="upload-preview" id="{{ $id }}_preview"></div>



    </div>



    <style>



.upload-file-input {

    position: absolute;

    width: 1px;

    height: 1px;

    opacity: 0;

    pointer-events: none;

}



.custom-upload-box {

    width: 100%;

    margin-top: 8px;

}



.upload-dropzone {

    border: 2px dashed #d2e1d9;

    background: #f8fbf9;

    border-radius: 12px;

    padding: 35px 20px;

    text-align: center;

    transition: .2s;

    cursor: pointer;

}



.upload-dropzone:hover,

.upload-dropzone.drag-active {

    border-color: #0d6b4b;

    background: #f0f8f4;

}



.upload-icon {

    width: 54px;

    height: 54px;

    border-radius: 14px;

    background: #e5f3ec;

    color: #0d6b4b;

    font-size: 27px;

    display: flex;

    align-items: center;

    justify-content: center;

    margin: 0 auto 14px;

}



.upload-dropzone h6 {

    font-weight: 700;

    color: #193c30;

    margin-bottom: 7px;

}



.upload-dropzone p {

    color: #78877e;

    font-size: 13px;

}



.upload-dropzone .btn-c-outline {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding: 10px 18px;

    background: #fff;

    border: 1px solid #0d6b4b;

    color: #0d6b4b;

    border-radius: 8px;

    cursor: pointer;

}



.upload-dropzone .btn-c-outline:hover {

    background: #0d6b4b;

    color: #fff;

}



.upload-formats {

    margin-top: 13px;

    color: #87948d;

    font-size: 12px;

}



.upload-preview {

    display: grid;

    grid-template-columns: repeat(auto-fill, minmax(115px, 1fr));

    gap: 12px;

    margin-top: 15px;

}



.upload-preview:empty {

    display: none;

}



.upload-preview-item {

    border: 1px solid #e1e9e4;

    padding: 7px;

    border-radius: 10px;

    background: #fff;

    min-width: 0;

}



.upload-preview-item img {

    width: 100%;

    height: 100px;

    object-fit: cover;

    border-radius: 7px;

}



.upload-preview-item .file-icon {

    height: 100px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #f1f5f2;

    border-radius: 7px;

    font-size: 32px;

    color: #0d6b4b;

}



.upload-preview-item small {

    display: block;

    margin-top: 7px;

    overflow: hidden;

    text-overflow: ellipsis;

    white-space: nowrap;

}



    </style>



    <script>



document.addEventListener('click', function (e) {

    const button = e.target.closest('[data-upload-button]');



    if (button) {

        const box = button.closest('.custom-upload-box');

        box.querySelector('[data-upload-input]').click();

    }

});



document.addEventListener('change', function (e) {

    if (!e.target.matches('[data-upload-input]')) return;



    const input = e.target;

    const preview = document.getElementById(input.dataset.preview);



    preview.innerHTML = '';



    Array.from(input.files).forEach(file => {



        const item = document.createElement('div');

        item.className = 'upload-preview-item';



        if (file.type.startsWith('image/')) {

            const img = document.createElement('img');

            img.src = URL.createObjectURL(file);

            img.onload = () => URL.revokeObjectURL(img.src);

            item.appendChild(img);

        } else {

            const icon = document.createElement('div');

            icon.className = 'file-icon';

            icon.innerHTML = '<i class="bi bi-file-earmark-pdf"></i>';

            item.appendChild(icon);

        }



        const name = document.createElement('small');

        name.textContent = file.name;



        item.appendChild(name);

        preview.appendChild(item);

    });

});



// Drag & Drop

document.addEventListener('dragover', function (e) {

    const zone = e.target.closest('[data-dropzone]');

    if (zone) {

        e.preventDefault();

        zone.classList.add('drag-active');

    }

});



document.addEventListener('dragleave', function (e) {

    const zone = e.target.closest('[data-dropzone]');

    if (zone && !zone.contains(e.relatedTarget)) {

        zone.classList.remove('drag-active');

    }

});



document.addEventListener('drop', function (e) {

    const zone = e.target.closest('[data-dropzone]');

    if (!zone) return;



    e.preventDefault();

    zone.classList.remove('drag-active');



    const input = zone.closest('.custom-upload-box')

        .querySelector('[data-upload-input]');



    const files = Array.from(e.dataTransfer.files);



    if (!input.multiple && files.length > 1) {

        alert('Please select only one file.');

        return;

    }



    const transfer = new DataTransfer();



    files.forEach(file => transfer.items.add(file));



    input.files = transfer.files;

    input.dispatchEvent(new Event('change', { bubbles: true }));

});



    </script>



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
