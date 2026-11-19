@extends('admin.layouts.app')
@section('title', 'Add product')
@section('content')
@php
  $quick = ['name','images','category_id','subcategory_id','brand_id','mrp','sale_price','opening_stock','description'];
  $quickFields = collect($fields)->whereIn('name', $quick)->sortBy(fn($f) => array_search($f['name'], $quick))->values();
  $advancedFields = collect($fields)->reject(fn($f) => in_array($f['name'], $quick, true) || in_array($f['name'], ['sku','slug'], true))->values();
  $categoryChildren = \App\Models\Category::whereNotNull('parent_id')->orderBy('name')->get(['id','parent_id','name']);
@endphp
<div class="page-head"><div><h1>Add product</h1><p class="sub">Quick Add keeps the essentials upfront. More details are optional.</p></div><div class="page-actions"><a class="btn-c-outline" href="{{ route('admin.products.index') }}"><i class="bi bi-arrow-left"></i> Back to products</a></div></div>
<form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" data-once id="product-create-form">
  @csrf
  <input type="hidden" name="sku" id="f_sku" value="{{ old('sku') }}">
  <input type="hidden" name="slug" id="f_slug" value="{{ old('slug') }}">
  <div class="card-c mb-3"><div class="card-body-c"><div class="section-title">Quick Add</div><div class="row g-3">
    @foreach ($quickFields as $f)
      @if ($f['name'] === 'brand_id')
        @include('admin.crud.field', ['f'=>$f, 'model'=>$model])
        <div class="col-12"><button class="btn-c-ghost btn-sm-c" type="button" data-brand-add><i class="bi bi-plus"></i> Quick Add Brand</button></div>
      @else
        @include('admin.crud.field', ['f'=>$f, 'model'=>$model])
      @endif
    @endforeach
    <div class="col-12"><div class="small text-muted" id="duplicate-warning" aria-live="polite"></div></div>
    <div class="col-12"><label class="form-label" for="discount_percent">Discount (%)</label><div class="input-group"><input class="form-control" type="number" min="0" max="100" step="0.01" id="discount_percent" placeholder="Optional"><span class="input-group-text">%</span></div><div class="form-text">Enter a discount to calculate the sale price, or enter a sale price directly.</div></div>
  </div></div></div>
  <div class="card-c mb-3"><div class="card-body-c"><div class="section-title">Product type</div><div class="row g-3"><div class="col-md-6"><label class="form-label" for="product_type">Product type</label><select class="form-select" id="product_type"><option>Medicine</option><option>Medical Equipment</option><option>Healthcare</option><option>Personal Care</option><option>Baby Care</option><option>Other</option></select></div><div class="col-md-6 d-flex align-items-end"><span class="form-text" id="type-note">Medical fields are optional and remain unverified until reviewed.</span></div></div></div></div>
  <details class="card-c mb-3"><summary class="card-head" style="cursor:pointer"><h2>Advanced Product Details</h2><span class="small text-muted">Optional</span></summary><div class="card-body-c"><div class="row g-3">
    @foreach ($advancedFields as $f)
      <div class="{{ in_array($f['name'], ['generic_name','composition','strength','dosage_form','pack_size','pack_type','manufacturer','shelf_life','storage_conditions','side_effects','contraindications','requires_prescription'], true) ? 'medical-field' : '' }}">@include('admin.crud.field', ['f'=>$f, 'model'=>$model])</div>
    @endforeach
  </div></div></details>
  <div class="d-flex gap-2 pb-4 flex-wrap"><button class="btn-c" type="submit" name="save_action" value="save"><i class="bi bi-check2"></i> Save Product</button><button class="btn-c-outline" type="submit" name="save_action" value="another"><i class="bi bi-plus-lg"></i> Save &amp; Add Another</button><button class="btn-c-outline" type="submit" name="save_action" value="draft"><i class="bi bi-file-earmark"></i> Save as Draft</button><a class="btn-c-outline" href="{{ route('admin.products.index') }}">Cancel</a></div>
</form>
<script>
(() => {
 const form=document.getElementById('product-create-form'), name=document.getElementById('f_name'), sku=document.getElementById('f_sku'), slug=document.getElementById('f_slug'), cat=document.getElementById('f_category_id'), sub=document.getElementById('f_subcategory_id'), mrp=document.getElementById('f_mrp'), sale=document.getElementById('f_sale_price'), discount=document.getElementById('discount_percent');
 const children=@json($categoryChildren); let skuManual=false;
 const makeSku=()=>{if(!skuManual) sku.value='TCM-'+Math.random().toString(36).slice(2,10).toUpperCase()}; makeSku(); sku.addEventListener('input',()=>skuManual=true);
 name.addEventListener('input',()=>{slug.value=name.value.toLowerCase().trim().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,''); warning()});
 const loadSubs=()=>{const selected=sub.value;sub.innerHTML='<option value="">Choose...</option>';children.filter(x=>String(x.parent_id)===cat.value).forEach(x=>{const o=new Option(x.name,x.id);sub.add(o)});if([...sub.options].some(o=>o.value===selected))sub.value=selected}; cat.addEventListener('change',loadSubs); loadSubs();
 const recalc=()=>{if(mrp.value && discount.value!=='' && (!sale.dataset.manual || sale.value==='')) sale.value=(Number(mrp.value)*(1-Number(discount.value)/100)).toFixed(2)};
 mrp.addEventListener('input',recalc); discount.addEventListener('input',()=>{sale.dataset.manual='';recalc()}); sale.addEventListener('input',()=>sale.dataset.manual='1');
 const warn=document.getElementById('duplicate-warning'), barcode=document.getElementById('f_barcode'); let timer; function warning(){clearTimeout(timer);timer=setTimeout(async()=>{try{const u=new URL(@json(route('admin.products.duplicate-check')),location.origin);u.searchParams.set('name',name.value);u.searchParams.set('sku',sku.value);u.searchParams.set('barcode',barcode?.value||'');const r=await fetch(u,{headers:{'Accept':'application/json'}});const d=await r.json();warn.textContent=d.matches?.length?'Possible duplicate: '+d.matches.join(', '):'';warn.className=d.matches?.length?'small text-warning':'small text-muted'}catch(e){}},350)}
 sku.addEventListener('input',warning); barcode?.addEventListener('input',warning);
 form.querySelector('[data-brand-add]').addEventListener('click',async()=>{const brand=prompt('Brand name');if(!brand?.trim())return;try{const r=await fetch(@json(route('admin.products.quick-brand')),{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':form.querySelector('[name=_token]').value,'Accept':'application/json'},body:JSON.stringify({name:brand.trim()})});const d=await r.json();if(!r.ok)throw new Error(d.message||'Could not add brand');const s=document.getElementById('f_brand_id');s.add(new Option(d.name,d.id,true,true))}catch(e){alert(e.message)}});
 form.querySelector('[name="images[]"]')?.addEventListener('change',e=>{const box=document.getElementById('f_images_preview');if(!box)return;box.querySelectorAll('[data-primary-new]').forEach(n=>n.remove());Array.from(e.target.files).forEach((f,i)=>{if(!f.type.startsWith('image/'))return;const url=URL.createObjectURL(f),card=document.createElement('div');card.dataset.primaryNew='1';card.className='upload-preview-item';const img=document.createElement('img');img.src=url;const label=document.createElement('label');label.innerHTML=`<input type="radio" name="primary_new" value="${i}" ${i===0?'checked':''}> Primary`;card.append(img,label);box.append(card)})});
 form.addEventListener('submit',e=>{const action=e.submitter?.value;if(action==='draft'){document.getElementById('f_is_active')?.setAttribute('value','0');const active=document.getElementById('f_is_active');if(active)active.checked=false}});
 document.getElementById('product_type').addEventListener('change',e=>{document.querySelectorAll('.medical-field').forEach(x=>x.hidden=e.target.value!=='Medicine')});
})();
</script>
@endsection
