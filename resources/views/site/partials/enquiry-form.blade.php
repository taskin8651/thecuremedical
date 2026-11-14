{{-- Public enquiry form. $product (optional) pre-fills the product name. --}}
@if (session('enquiry_success'))
  <div class="notice info mb-3" id="enquiry-done" role="status"><i class="bi bi-check-circle"></i><div>{{ session('enquiry_success') }}</div></div>
@endif
<form method="POST" action="{{ route('site.enquiry') }}" data-enquiry novalidate>
  @csrf
  <div class="hp" aria-hidden="true"><label>Leave this empty<input type="text" name="company_website" tabindex="-1" autocomplete="off"></label></div>
  <div class="row g-3">
    <div class="col-md-6"><label class="form-label" for="eName">Name</label><input id="eName" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" autocomplete="name" maxlength="150" required>@error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
    <div class="col-md-6"><label class="form-label" for="ePhone">Phone</label><input id="ePhone" name="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror" inputmode="tel" autocomplete="tel" maxlength="15" required>@error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
    <div class="col-md-6"><label class="form-label" for="eType">Enquiry type</label>
      <select id="eType" name="type" class="form-select @error('type') is-invalid @enderror" required>
        @foreach (['availability' => 'Product availability', 'equipment' => 'Healthcare equipment', 'store_visit' => 'Store visit', 'general' => 'General', 'other' => 'Other'] as $k => $l)<option value="{{ $k }}" @selected(old('type', $product ? 'availability' : 'general') === $k)>{{ $l }}</option>@endforeach
      </select>@error('type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
    <div class="col-md-6"><label class="form-label" for="eEmail">Email (optional)</label><input id="eEmail" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" autocomplete="email" maxlength="150">@error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
    <div class="col-12"><label class="form-label" for="eProduct">Product (optional)</label><input id="eProduct" name="product_name" value="{{ old('product_name', $product ?? '') }}" class="form-control" maxlength="200"></div>
    <div class="col-12"><label class="form-label" for="eMsg">Message</label><textarea id="eMsg" name="message" rows="5" minlength="10" maxlength="1500" class="form-control @error('message') is-invalid @enderror" required>{{ old('message') }}</textarea>@error('message')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
    <div class="col-12"><button class="btn-cure" type="submit"><i class="bi bi-send"></i> Send enquiry</button></div>
  </div>
</form>
