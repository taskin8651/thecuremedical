<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Invoice {{ $order->order_number }}</title>
<style>
  :root{--ink:#19352c;--green:#12684e;--green-dark:#0c4e3a;--mint:#eaf4ef;--line:#dce8e1;--muted:#71837a;--paper:#fff;--canvas:#f2f6f3;--gold:#c59a52}
  *{box-sizing:border-box}
  body{margin:0;background:var(--canvas);color:var(--ink);font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif;font-size:14px;line-height:1.55;-webkit-font-smoothing:antialiased}
  .toolbar{max-width:900px;margin:24px auto 14px;display:flex;justify-content:flex-end}
  .print-btn{display:inline-flex;align-items:center;gap:9px;border:0;border-radius:9px;padding:11px 18px;background:var(--green);color:white;font:600 13px Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif;cursor:pointer;box-shadow:0 5px 14px #12684e26}
  .print-btn:hover{background:var(--green-dark)}
  .sheet{max-width:900px;min-height:1120px;margin:0 auto 40px;padding:54px 60px;background:var(--paper);box-shadow:0 18px 55px #153c2b12}
  .brand-row{display:flex;align-items:flex-start;justify-content:space-between;gap:28px;padding-bottom:30px;border-bottom:1px solid var(--line)}
  .brand{display:flex;align-items:flex-start;gap:15px;min-width:0}
  .brand-mark{width:48px;height:48px;flex:0 0 48px;display:grid;place-items:center;border-radius:14px;background:var(--green);color:#fff;font-size:15px;font-weight:800;letter-spacing:.08em;box-shadow:inset 0 -3px 0 #ffffff1a}
  .brand-name{margin:0 0 5px;color:var(--green-dark);font-size:21px;font-weight:750;letter-spacing:-.035em;line-height:1.2}
  .brand-meta{color:var(--muted);font-size:12px;line-height:1.65}
  .invoice-meta{text-align:right;min-width:190px}
  .eyebrow{color:var(--muted);font-size:10px;font-weight:750;letter-spacing:.16em;text-transform:uppercase}
  .invoice-title{margin:3px 0 0;color:var(--green-dark);font-size:28px;font-weight:750;letter-spacing:-.04em;line-height:1.2}
  .invoice-number{margin-top:8px;color:var(--ink);font-size:13px;font-weight:700;overflow-wrap:anywhere}
  .invoice-date{margin-top:3px;color:var(--muted);font-size:12px}
  .gold-rule{width:46px;height:3px;margin:17px 0 0 auto;border-radius:5px;background:var(--gold)}
  .info-grid{display:grid;grid-template-columns:1.3fr 1fr;gap:16px;margin:26px 0 30px}
  .info-card{min-height:122px;padding:18px 20px;border:1px solid var(--line);border-radius:12px;background:#fff}
  .info-card.ship{background:#f7faf8}
  .card-label{margin-bottom:11px;color:var(--muted);font-size:10px;font-weight:750;letter-spacing:.13em;text-transform:uppercase}
  .customer-name{margin:0 0 4px;font-size:15px;font-weight:750}
  .customer-detail{color:#5e7167;font-size:12px;line-height:1.7;overflow-wrap:anywhere}
  .detail-line{display:flex;justify-content:space-between;gap:16px;margin:0 0 8px;font-size:12px}
  .detail-line:last-child{margin-bottom:0}
  .detail-line span:first-child{color:var(--muted)}
  .detail-line strong{text-align:right;font-weight:650}
  .section-heading{display:flex;align-items:center;justify-content:space-between;margin:0 0 12px}
  .section-heading h2{margin:0;color:var(--ink);font-size:14px;font-weight:750;letter-spacing:-.01em}
  .section-heading span{color:var(--muted);font-size:11px}
  table{width:100%;border-collapse:separate;border-spacing:0}
  thead th{padding:11px 12px;background:var(--green-dark);color:#fff;text-align:left;font-size:10px;font-weight:700;letter-spacing:.09em;text-transform:uppercase}
  thead th:first-child{border-radius:8px 0 0 0}
  thead th:last-child{border-radius:0 8px 0 0}
  th.num,td.num{text-align:right;white-space:nowrap}
  tbody td{padding:14px 12px;border-bottom:1px solid var(--line);font-size:12px;vertical-align:top}
  .item-name{font-size:13px;font-weight:650}
  .item-index{width:34px;color:#9aa9a1;font-size:11px}
  .summary{display:flex;justify-content:flex-end;margin-top:20px}
  .totals{width:310px}
  .total-row{display:flex;justify-content:space-between;gap:16px;padding:6px 2px;color:#5f7168;font-size:12px}
  .total-row strong{color:var(--ink);font-weight:650}
  .total-row.discount strong{color:#a34f48}
  .total-row.grand{align-items:center;margin-top:10px;padding:14px 16px;border-radius:10px;background:var(--mint);color:var(--green-dark)}
  .total-row.grand span{font-size:12px;font-weight:750;letter-spacing:.08em;text-transform:uppercase}
  .total-row.grand strong{color:var(--green-dark);font-size:21px;font-weight:800;letter-spacing:-.03em}
  .tax-note{margin:9px 2px 0;color:var(--muted);font-size:10px;text-align:right}
  .bottom-row{display:flex;align-items:flex-end;justify-content:space-between;gap:24px;margin-top:34px;padding-top:20px;border-top:1px solid var(--line)}
  .payment{max-width:360px}
  .payment-label{margin-bottom:8px;color:var(--muted);font-size:10px;font-weight:750;letter-spacing:.12em;text-transform:uppercase}
  .payment-line{display:flex;align-items:center;gap:9px;font-size:12px}
  .pill{display:inline-flex;align-items:center;border:1px solid #d7e9df;border-radius:999px;padding:4px 10px;background:#f0f8f3;color:var(--green-dark);font-size:10px;font-weight:750;letter-spacing:.04em;text-transform:uppercase}
  .pill.unpaid,.pill.failed{border-color:#f0d9d6;background:#fff5f3;color:#a34f48}
  .thanks{text-align:right;color:var(--green-dark);font-size:13px;font-weight:700}
  .thanks small{display:block;margin-top:3px;color:var(--muted);font-size:10px;font-weight:400}
  .footer{margin-top:38px;padding-top:14px;border-top:1px dashed var(--line);color:#84948b;font-size:10px;text-align:center}
  @media(max-width:680px){.toolbar{margin:14px 14px 10px}.sheet{min-height:0;margin:0 10px 20px;padding:28px 22px}.brand-row{gap:16px}.brand-mark{width:40px;height:40px;flex-basis:40px;border-radius:11px}.brand-name{font-size:17px}.invoice-title{font-size:23px}.invoice-meta{min-width:130px}.info-grid{grid-template-columns:1fr}.info-card{min-height:0}.summary{justify-content:stretch}.totals{width:100%}.bottom-row{align-items:flex-start;flex-direction:column}.thanks{text-align:left}table{min-width:560px}.table-scroll{overflow-x:auto;margin-right:-4px;padding-bottom:4px}}
  @page{size:A4;margin:12mm}
  @media print{body{background:#fff;font-size:12px;-webkit-print-color-adjust:exact;print-color-adjust:exact}.toolbar{display:none}.sheet{width:100%;max-width:none;min-height:0;margin:0;padding:8mm 7mm;box-shadow:none}.brand-row{padding-bottom:20px}.info-grid{margin:19px 0 22px}.info-card{break-inside:avoid}.section-heading{margin-bottom:9px}thead{display:table-header-group}tr{break-inside:avoid}.summary{break-inside:avoid}.bottom-row,.footer{break-inside:avoid}.footer{margin-top:25px}}
</style>
</head>
<body>
<div class="toolbar"><button class="print-btn" type="button" onclick="window.print()" aria-label="Print invoice">Print invoice</button></div>
<main class="sheet">
  <header class="brand-row">
    <div class="brand">
      <div class="brand-mark" aria-hidden="true">TC</div>
      <div>
        <h1 class="brand-name">{{ setting('business_name') }}</h1>
        <div class="brand-meta">{!! nl2br(e(setting('address'))) !!}@if(setting('phone'))<br>Phone: {{ setting('phone') }}@endif</div>
      </div>
    </div>
    <div class="invoice-meta">
      <div class="eyebrow">Order document</div>
      <div class="invoice-title">Invoice</div>
      <div class="invoice-number">{{ $order->order_number }}</div>
      <div class="invoice-date">{{ ($order->placed_at ?? $order->created_at)->format('d M Y') }}</div>
      <div class="gold-rule"></div>
    </div>
  </header>

  <section class="info-grid" aria-label="Invoice details">
    <div class="info-card">
      <div class="card-label">Billed to</div>
      <div class="customer-name">{{ $order->shipping_name ?: ($order->customer?->name ?? 'Customer') }}</div>
      <div class="customer-detail">
        @if($order->shipping_address){{ $order->shipping_address }}<br>@endif
        @if($order->shipping_pincode){{ $order->shipping_pincode }}<br>@endif
        @if($order->shipping_phone)Phone: {{ $order->shipping_phone }}@endif
      </div>
    </div>
    <div class="info-card ship">
      <div class="card-label">Order summary</div>
      <div class="detail-line"><span>Order reference</span><strong>{{ $order->order_number }}</strong></div>
      <div class="detail-line"><span>Order date</span><strong>{{ ($order->placed_at ?? $order->created_at)->format('d M Y') }}</strong></div>
      <div class="detail-line"><span>Payment</span><strong>{{ $order->payment_method ? \Illuminate\Support\Str::headline($order->payment_method) : '—' }}</strong></div>
    </div>
  </section>

  <section aria-label="Invoice items">
    <div class="section-heading"><h2>Order items</h2><span>{{ $order->items->count() }} {{ \Illuminate\Support\Str::plural('item', $order->items->count()) }}</span></div>
    <div class="table-scroll">
      <table>
        <thead><tr><th style="width:40px">#</th><th>Product / description</th><th class="num">Qty</th><th class="num">Unit price</th><th class="num">Amount</th></tr></thead>
        <tbody>
          @foreach ($order->items as $i => $item)
            <tr><td class="item-index">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</td><td class="item-name">{{ $item->product_name }}</td><td class="num">{{ $item->quantity }}</td><td class="num">{{ money($item->unit_price) }}</td><td class="num"><strong>{{ money($item->line_total) }}</strong></td></tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </section>

  <section class="summary" aria-label="Invoice totals">
    <div class="totals">
      <div class="total-row"><span>Subtotal</span><strong>{{ money($order->subtotal) }}</strong></div>
      @if ($order->discount_total > 0)<div class="total-row discount"><span>Discount{{ $order->coupon ? ' · '.$order->coupon->code : '' }}</span><strong>− {{ money($order->discount_total) }}</strong></div>@endif
      <div class="total-row"><span>Shipping</span><strong>{{ $order->shipping_total > 0 ? money($order->shipping_total) : 'Free' }}</strong></div>
      <div class="total-row grand"><span>Order total</span><strong>{{ money($order->grand_total) }}</strong></div>
      @if ($order->tax_total > 0)<div class="tax-note">Includes GST of {{ money($order->tax_total) }}</div>@endif
    </div>
  </section>

  <footer class="bottom-row">
    <div class="payment">
      <div class="payment-label">Payment status</div>
      @php($paymentStatus = \App\Models\Order::PAYMENT_STATUSES[$order->payment_status] ?? \Illuminate\Support\Str::headline($order->payment_status))
      <div class="payment-line"><span class="pill {{ in_array($order->payment_status, ['unpaid', 'failed']) ? $order->payment_status : '' }}">{{ $paymentStatus }}</span></div>
    </div>
    <div class="thanks">Thank you for choosing {{ setting('business_name') }}.<small>We appreciate your trust in our care.</small></div>
  </footer>
  <div class="footer">This invoice was generated electronically. For assistance, contact {{ setting('phone') }}.</div>
</main>
</body>
</html>
