<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #1f2937; background: #fff; }
    .page { padding: 40px 48px; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; border-bottom: 2px solid #2563eb; padding-bottom: 20px; }
    .brand { font-size: 22px; font-weight: 700; color: #2563eb; }
    .brand-sub { font-size: 11px; color: #6b7280; margin-top: 2px; }
    .inv-meta { text-align: right; }
    .inv-meta .inv-number { font-size: 18px; font-weight: 700; color: #2563eb; }
    .inv-meta .inv-status { display: inline-block; margin-top: 4px; padding: 2px 10px; border-radius: 9999px; font-size: 10px; font-weight: 600; text-transform: uppercase; background: #dcfce7; color: #166534; }
    .parties { display: flex; gap: 48px; margin-bottom: 28px; }
    .party h3 { font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; margin-bottom: 6px; }
    .party p { font-size: 12px; line-height: 1.6; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    th { background: #f3f4f6; padding: 8px 10px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280; }
    td { padding: 8px 10px; border-bottom: 1px solid #e5e7eb; font-size: 12px; }
    td.right, th.right { text-align: right; }
    .totals { margin-left: auto; width: 260px; }
    .totals table { margin-bottom: 0; }
    .totals td { border: none; padding: 4px 10px; }
    .totals .total-row { font-weight: 700; font-size: 13px; border-top: 2px solid #1f2937; }
    .totals .paid-row { color: #16a34a; }
    .totals .due-row { color: #dc2626; font-size: 14px; }
    .footer { margin-top: 40px; padding-top: 16px; border-top: 1px solid #e5e7eb; font-size: 10px; color: #9ca3af; text-align: center; }
    .badge-paid { background: #dcfce7; color: #166534; }
    .badge-draft { background: #f3f4f6; color: #374151; }
    .badge-sent { background: #dbeafe; color: #1e40af; }
    .badge-cancelled { background: #fee2e2; color: #991b1b; }
</style>
</head>
<body>
<div class="page">
    <div class="header">
        <div>
            <div class="brand">{{ $invoice->tenant->name ?? 'MedCore' }}</div>
            <div class="brand-sub">Healthcare Management System</div>
        </div>
        <div class="inv-meta">
            <div class="inv-number">{{ $invoice->invoice_number }}</div>
            <div>Date: {{ $invoice->created_at->format('d M Y') }}</div>
            @if($invoice->due_date)
            <div>Due: {{ $invoice->due_date->format('d M Y') }}</div>
            @endif
            <span class="inv-status badge-{{ $invoice->status }}">{{ strtoupper(str_replace('_', ' ', $invoice->status)) }}</span>
        </div>
    </div>

    <div class="parties">
        <div class="party">
            <h3>Bill To</h3>
            <p>
                <strong>{{ $invoice->patient->first_name }} {{ $invoice->patient->last_name }}</strong><br>
                MRN: {{ $invoice->patient->mrn }}<br>
                @if($invoice->patient->phone)Phone: {{ $invoice->patient->phone }}<br>@endif
                @if($invoice->patient->email){{ $invoice->patient->email }}<br>@endif
            </p>
        </div>
        @if($invoice->encounter)
        <div class="party">
            <h3>Encounter</h3>
            <p>
                ID: #{{ $invoice->encounter->id }}<br>
                Date: {{ $invoice->encounter->encounter_date }}<br>
                Type: {{ ucfirst($invoice->encounter->encounter_type) }}
            </p>
        </div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="right">Qty</th>
                <th class="right">Unit Price</th>
                <th class="right">Tax</th>
                <th class="right">Discount</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->lines as $line)
            <tr>
                <td>{{ $line->description }}</td>
                <td class="right">{{ rtrim(rtrim(number_format($line->quantity, 3), '0'), '.') }}</td>
                <td class="right">{{ number_format($line->unit_price, 2) }}</td>
                <td class="right">{{ number_format($line->tax_amount, 2) }}</td>
                <td class="right">{{ number_format($line->discount_amount, 2) }}</td>
                <td class="right">{{ number_format($line->line_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr><td>Subtotal</td><td class="right">{{ number_format($invoice->subtotal, 2) }}</td></tr>
            <tr><td>Tax</td><td class="right">{{ number_format($invoice->tax_total, 2) }}</td></tr>
            @if($invoice->discount_amount > 0)
            <tr><td>Discount</td><td class="right">({{ number_format($invoice->discount_amount, 2) }})</td></tr>
            @endif
            <tr class="total-row"><td>Total</td><td class="right">{{ number_format($invoice->total_amount, 2) }}</td></tr>
            @if($invoice->amount_paid > 0)
            <tr class="paid-row"><td>Amount Paid</td><td class="right">({{ number_format($invoice->amount_paid, 2) }})</td></tr>
            @endif
            <tr class="due-row"><td><strong>Balance Due</strong></td><td class="right"><strong>{{ number_format($invoice->amount_due, 2) }}</strong></td></tr>
        </table>
    </div>

    @if($invoice->payments->isNotEmpty())
    <div style="margin-top: 24px;">
        <p style="font-size:10px; text-transform:uppercase; letter-spacing:0.04em; color:#6b7280; margin-bottom:6px;">Payment History</p>
        <table>
            <thead>
                <tr>
                    <th>Date</th><th>Method</th><th>Reference</th><th class="right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->payments as $payment)
                <tr>
                    <td>{{ $payment->paid_at->format('d M Y H:i') }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td>
                    <td>{{ $payment->reference_number ?? '—' }}</td>
                    <td class="right">{{ number_format($payment->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if($invoice->notes)
    <p style="margin-top:20px; font-size:11px; color:#6b7280;"><strong>Notes:</strong> {{ $invoice->notes }}</p>
    @endif

    <div class="footer">
        Generated by MedCore — {{ now()->format('d M Y H:i') }} · This is a computer-generated document.
    </div>
</div>
</body>
</html>
