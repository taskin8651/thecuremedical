@php $tel = \App\Support\Settings::tel(); $hours = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) setting('opening_hours'))))); @endphp
<div class="form-card">
  <div class="info-row"><span class="ic"><i class="bi bi-geo-alt"></i></span><div><strong>{{ setting('business_name') }}</strong><small>{{ setting('address') }}</small></div></div>
  <div class="info-row"><span class="ic"><i class="bi bi-telephone"></i></span><div><strong><a href="tel:{{ $tel }}">{{ setting('phone') }}</a></strong><small>Call to check availability</small></div></div>
  @if ($hours)<div class="info-row"><span class="ic"><i class="bi bi-clock"></i></span><div><strong>{{ $hours[0] }}</strong>@foreach (array_slice($hours, 1) as $h)<small>{{ $h }}</small>@endforeach</div></div>@endif
  @if (setting('contact_email'))<div class="info-row"><span class="ic"><i class="bi bi-envelope"></i></span><div><strong><a href="mailto:{{ setting('contact_email') }}">{{ setting('contact_email') }}</a></strong><small>Email</small></div></div>@endif
  <div class="d-flex flex-wrap gap-2 mt-3">
    <a class="btn-cure btn-sm-c" href="tel:{{ $tel }}"><i class="bi bi-telephone"></i> Call Now</a>
    @if (setting('whatsapp_number'))<a class="btn-cure-outline btn-sm-c" target="_blank" rel="noopener" href="{{ \App\Support\Settings::whatsapp('Hello, I would like to make an enquiry.') }}"><i class="bi bi-whatsapp"></i> WhatsApp</a>@endif
    <a class="btn-cure-outline btn-sm-c" target="_blank" rel="noopener" href="{{ \App\Support\Settings::mapsDirections() }}"><i class="bi bi-signpost-split"></i> Directions</a>
  </div>
</div>
