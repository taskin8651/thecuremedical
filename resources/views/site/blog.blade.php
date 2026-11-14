@extends('site.layout')
@section('title', 'Health articles')
@section('content')
@include('site.partials.hero', ['crumbs' => ['Articles' => null], 'heading' => 'Health articles', 'lead' => 'General reading. Not a substitute for advice from a doctor or pharmacist.'])
<section class="section-sm"><div class="container">
  <form method="GET" class="d-flex flex-wrap gap-2 mb-4" role="search">
    <input type="search" name="q" value="{{ $q }}" class="form-control" style="max-width:320px" placeholder="Search articles" aria-label="Search articles" maxlength="100">
    @if ($cats->isNotEmpty())<select name="category" class="form-select" style="max-width:220px" aria-label="Category" onchange="this.form.submit()"><option value="">All categories</option>@foreach ($cats as $c)<option value="{{ $c }}" @selected($current === $c)>{{ $c }}</option>@endforeach</select>@endif
    <button class="btn-cure btn-sm-c" type="submit">Search</button>
  </form>
  <div class="row g-3 g-lg-4">@forelse ($posts as $a)<div class="col-md-6 col-lg-4">@include('site.partials.article-card', ['a' => $a])</div>@empty<div class="col-12"><p class="text-muted-c">No articles yet.</p></div>@endforelse</div>
  <div class="mt-4">{{ $posts->links() }}</div>
</div></section>
@endsection
