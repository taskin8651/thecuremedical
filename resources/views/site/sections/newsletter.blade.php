<section class="section-sm"><div class="container"><div class="newsletter"><div class="row align-items-center g-4">
  <div class="col-lg-6"><h2 class="h3 mb-2">{{ setting('newsletter_title') }}</h2><p class="text-muted-c m-0">{{ setting('newsletter_text') }}</p></div>
  <div class="col-lg-6">
    @if (session('enquiry_success') && old('type') === 'newsletter')<div class="notice info mb-2"><i class="bi bi-check-circle"></i><div>{{ session('enquiry_success') }}</div></div>@endif
    <form method="POST" action="{{ route('site.enquiry') }}" data-enquiry novalidate>
      @csrf
      <input type="hidden" name="type" value="newsletter">
      <div class="hp" aria-hidden="true"><label>Leave empty<input type="text" name="company_website" tabindex="-1" autocomplete="off"></label></div>
      <label for="nlEmail" class="visually-hidden">Email address</label>
      <input type="email" class="form-control @if (old('type') === 'newsletter' && $errors->has('email')) is-invalid @endif" id="nlEmail" name="email" value="{{ old('type') === 'newsletter' ? old('email') : '' }}" placeholder="Your email address" required maxlength="150">
      <button class="btn-cure" type="submit">Subscribe</button>
      @if (old('type') === 'newsletter' && $errors->has('email'))<div class="text-danger small mt-1">{{ $errors->first('email') }}</div>@endif
    </form>
  </div>
</div></div></div></section>
