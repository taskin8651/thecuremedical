<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Invoice {{ $order->order_number }}</title>
<style>
  body{font-family:Arial,Helvetica,sans-serif;color:#172B24;margin:0;padding:24px;background:#fff}
  .wrap{max-width:780px;margin:0 auto} h1{color:#0D6B4B;margin:0 0 4px;font-size:22px}
  table{width:100%;border-collapse:collapse;margin-top:16px} th,td{padding:8px;border-bottom:1px solid #dfe8e2;text-align:left;font-size:14px}
  th{background:#F5F8F6} .r{text-align:right} .muted{color:#718078;font-size:13px} .top{display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap}
  button{background:#0D6B4B;color:#fff;border:0;padding:9px 16px;border-radius:8px;cursor:pointer;margin-bottom:16px}
  @media print{button{display:none}}
</style>
</head>
<body><div class="wrap">
  <button onclick="window.print()">Print</button>
  <div class="top">
    <div><h1>{{ setting('business_name') }}</h1><div class="muted">{!! nl2br(e(setting('address'))) !!}<br>Phone {{ setting('phone') }}</div></div>
    <div class="r"><strong>Invoice</strong><br>{{ $order->order_number }}<br><span class="muted">{{ ($order->placed_at ?? $order->created_at)->format('d M Y') }}</span></div>
  </div>
  <p><strong>Bill to:</strong> {{ $order->shipping_name ?: ($order->customer?->name ?? 'Customer') }}<br><span class="muted">{!! nl2br(e($order->shipping_address)) !!} {{ $order->shipping_pincode }}<br>{{ $order->shipping_phone }}</span></p>
  <table>
    <thead><tr><th>Item</th><th class="r">Qty</th><th class="r">Price</th><th class="r">Total</th></tr></thead>
    <tbody>@foreach ($order->items as $i)<tr><td>{{ $i->product_name }}</td><td class="r">{{ $i->quantity }}</td><td class="r">{{ money($i->unit_price) }}</td><td class="r">{{ money($i->line_total) }}</td></tr>@endforeach</tbody>
    <tfoot>
      <tr><td colspan="3" class="r">Subtotal</td><td class="r">{{ money($order->subtotal) }}</td></tr>
      @if ($order->discount_total > 0)<tr><td colspan="3" class="r">Discount</td><td class="r">- {{ money($order->discount_total) }}</td></tr>@endif
      <tr><td colspan="3" class="r">Shipping</td><td class="r">{{ money($order->shipping_total) }}</td></tr>
      <tr><td colspan="3" class="r"><strong>Total</strong></td><td class="r"><strong>{{ money($order->grand_total) }}</strong></td></tr>
    </tfoot>
  </table>
  <p class="muted">Prices include GST of {{ money($order->tax_total) }}. Payment status: {{ \App\Models\Order::PAYMENT_STATUSES[$order->payment_status] ?? $order->payment_status }}.</p>
</div></body></html>
