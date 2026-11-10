<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $quote->quote_no }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #1a1a1a;
            direction: rtl;
            background: #fff;
        }

        .page {
            padding: 30px 40px;
        }

        /* Header */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 24px;
            border-bottom: 3px solid #1d4ed8;
            padding-bottom: 16px;
        }
        .header-left  { display: table-cell; vertical-align: middle; }
        .header-right { display: table-cell; vertical-align: middle; text-align: left; }
        .app-name {
            font-size: 22px;
            font-weight: bold;
            color: #1d4ed8;
        }
        .doc-title {
            font-size: 18px;
            font-weight: bold;
            color: #374151;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .doc-number {
            font-size: 13px;
            color: #6b7280;
            margin-top: 4px;
        }

        /* Status watermark */
        @if($quote->status?->value === 'draft')
        .watermark {
            position: fixed;
            top: 38%;
            right: 20%;
            font-size: 80px;
            font-weight: bold;
            color: rgba(156, 163, 175, 0.18);
            transform: rotate(-35deg);
            text-transform: uppercase;
            letter-spacing: 8px;
            pointer-events: none;
        }
        @elseif($quote->status?->value === 'accepted')
        .watermark {
            position: fixed;
            top: 38%;
            right: 15%;
            font-size: 80px;
            font-weight: bold;
            color: rgba(22, 163, 74, 0.15);
            transform: rotate(-35deg);
            text-transform: uppercase;
            letter-spacing: 8px;
        }
        @elseif($quote->status?->value === 'rejected')
        .watermark {
            position: fixed;
            top: 38%;
            right: 20%;
            font-size: 80px;
            font-weight: bold;
            color: rgba(220, 38, 38, 0.15);
            transform: rotate(-35deg);
            text-transform: uppercase;
            letter-spacing: 8px;
        }
        @else
        .watermark { display: none; }
        @endif

        /* Meta section */
        .meta-section {
            display: table;
            width: 100%;
            margin-bottom: 24px;
        }
        .meta-box {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 14px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
        }
        .meta-box + .meta-box { margin-right: 16px; }
        .meta-label {
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            border-bottom: 1px solid #f3f4f6;
            padding-bottom: 6px;
        }
        .meta-row {
            display: table;
            width: 100%;
            margin-bottom: 5px;
        }
        .meta-key {
            display: table-cell;
            color: #6b7280;
            width: 45%;
            font-size: 11px;
        }
        .meta-value {
            display: table-cell;
            color: #111827;
            font-weight: 500;
            font-size: 11px;
        }

        /* Items table */
        .items-section { margin-bottom: 20px; }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #1d4ed8;
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 2px solid #dbeafe;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
        }
        table.items thead tr {
            background-color: #1d4ed8;
            color: #fff;
        }
        table.items thead th {
            padding: 9px 12px;
            font-size: 11px;
            text-align: right;
        }
        table.items thead th.num { width: 5%; text-align: center; }
        table.items thead th.qty { width: 10%; text-align: center; }
        table.items thead th.price { width: 18%; text-align: left; }
        table.items thead th.total { width: 18%; text-align: left; }
        table.items tbody tr:nth-child(even) { background-color: #f8fafc; }
        table.items tbody td {
            padding: 8px 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 11px;
            vertical-align: top;
        }
        table.items tbody td.num { text-align: center; color: #9ca3af; }
        table.items tbody td.qty { text-align: center; }
        table.items tbody td.price,
        table.items tbody td.total { text-align: left; }

        /* Totals */
        .totals-wrapper {
            display: table;
            width: 100%;
            margin-top: 16px;
        }
        .totals-spacer { display: table-cell; width: 55%; }
        .totals-box {
            display: table-cell;
            width: 45%;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px 16px;
        }
        .totals-row {
            display: table;
            width: 100%;
            margin-bottom: 6px;
        }
        .totals-label { display: table-cell; color: #6b7280; font-size: 11px; }
        .totals-amount { display: table-cell; text-align: left; font-size: 11px; font-weight: 500; }
        .totals-row.grand {
            border-top: 2px solid #1d4ed8;
            padding-top: 8px;
            margin-top: 6px;
        }
        .totals-row.grand .totals-label { font-size: 13px; font-weight: bold; color: #111827; }
        .totals-row.grand .totals-amount { font-size: 13px; font-weight: bold; color: #1d4ed8; }

        /* Notes */
        .notes-section {
            margin-top: 20px;
            padding: 12px 16px;
            background: #f9fafb;
            border-right: 4px solid #1d4ed8;
            border-radius: 4px;
        }
        .notes-title { font-weight: bold; color: #374151; margin-bottom: 6px; font-size: 11px; }
        .notes-body { color: #6b7280; font-size: 11px; line-height: 1.6; }

        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
            display: table;
            width: 100%;
        }
        .footer-left  { display: table-cell; color: #9ca3af; font-size: 10px; }
        .footer-right { display: table-cell; color: #9ca3af; font-size: 10px; text-align: left; }

        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .status-draft    { background: #f3f4f6; color: #6b7280; }
        .status-sent     { background: #dbeafe; color: #1d4ed8; }
        .status-accepted { background: #dcfce7; color: #15803d; }
        .status-rejected { background: #fee2e2; color: #b91c1c; }
        .status-expired  { background: #fef3c7; color: #b45309; }
    </style>
</head>
<body>
<div class="page">

    {{-- Watermark --}}
    <div class="watermark">{{ strtoupper($quote->status?->value ?? '') }}</div>

    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            <div class="app-name">{{ config('app.name') }}</div>
        </div>
        <div class="header-right">
            <div class="doc-title">Quotation</div>
            <div class="doc-number">{{ $quote->quote_no }}</div>
            <div style="margin-top: 6px;">
                <span class="status-badge status-{{ $quote->status?->value ?? 'draft' }}">
                    {{ strtoupper($quote->status?->value ?? 'DRAFT') }}
                </span>
            </div>
        </div>
    </div>

    {{-- Meta info --}}
    <div class="meta-section">
        <div class="meta-box">
            <div class="meta-label">Bill To</div>
            @if($quote->deal?->company)
                <div class="meta-row">
                    <span class="meta-key">Company:</span>
                    <span class="meta-value">{{ $quote->deal->company->name }}</span>
                </div>
                @if($quote->deal->company->tax_id)
                <div class="meta-row">
                    <span class="meta-key">Tax ID:</span>
                    <span class="meta-value">{{ $quote->deal->company->tax_id }}</span>
                </div>
                @endif
                @if($quote->deal->company->address)
                <div class="meta-row">
                    <span class="meta-key">Address:</span>
                    <span class="meta-value">{{ $quote->deal->company->address }}</span>
                </div>
                @endif
            @else
                <div class="meta-value" style="color:#6b7280">—</div>
            @endif
        </div>
        <div class="meta-box" style="margin-right: 16px;">
            <div class="meta-label">Quote Details</div>
            <div class="meta-row">
                <span class="meta-key">Quote No.:</span>
                <span class="meta-value">{{ $quote->quote_no }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-key">Date:</span>
                <span class="meta-value">{{ $quote->created_at?->format('Y-m-d') }}</span>
            </div>
            @if($quote->valid_until)
            <div class="meta-row">
                <span class="meta-key">Valid Until:</span>
                <span class="meta-value">{{ $quote->valid_until->format('Y-m-d') }}</span>
            </div>
            @endif
            @if($quote->deal?->employee)
            <div class="meta-row">
                <span class="meta-key">Salesperson:</span>
                <span class="meta-value">{{ $quote->deal->employee->name }}</span>
            </div>
            @endif
            @if($quote->deal)
            <div class="meta-row">
                <span class="meta-key">Deal:</span>
                <span class="meta-value">{{ $quote->deal->title }}</span>
            </div>
            @endif
        </div>
    </div>

    {{-- Line items --}}
    <div class="items-section">
        <div class="section-title">Items</div>
        <table class="items">
            <thead>
                <tr>
                    <th class="num">#</th>
                    <th>Description</th>
                    <th class="qty">Qty</th>
                    <th class="price">Unit Price (SAR)</th>
                    <th class="total">Total (SAR)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($quote->items ?? [] as $i => $item)
                <tr>
                    <td class="num">{{ $i + 1 }}</td>
                    <td>{{ $item['description'] ?? '—' }}</td>
                    <td class="qty">{{ $item['qty'] ?? 1 }}</td>
                    <td class="price">{{ number_format(($item['unit_price'] ?? 0) / 100, 2) }}</td>
                    <td class="total">{{ number_format(($item['total'] ?? 0) / 100, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center; color:#9ca3af; padding: 20px;">
                        No line items added.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Totals --}}
    <div class="totals-wrapper">
        <div class="totals-spacer"></div>
        <div class="totals-box">
            <div class="totals-row">
                <span class="totals-label">Subtotal:</span>
                <span class="totals-amount">SAR {{ number_format($quote->subtotal / 100, 2) }}</span>
            </div>
            @if($quote->discount > 0)
            <div class="totals-row">
                <span class="totals-label">Discount:</span>
                <span class="totals-amount" style="color:#b91c1c;">- SAR {{ number_format($quote->discount / 100, 2) }}</span>
            </div>
            @endif
            <div class="totals-row grand">
                <span class="totals-label">Total:</span>
                <span class="totals-amount">SAR {{ number_format($quote->total / 100, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Notes --}}
    @if($quote->notes)
    <div class="notes-section">
        <div class="notes-title">Notes &amp; Terms</div>
        <div class="notes-body">{{ $quote->notes }}</div>
    </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <div class="footer-left">
            {{ config('app.name') }} &mdash; Generated {{ now()->format('Y-m-d H:i') }}
        </div>
        <div class="footer-right">
            This quotation is valid
            @if($quote->valid_until) until {{ $quote->valid_until->format('Y-m-d') }}
            @else for 30 days from the issue date
            @endif.
        </div>
    </div>

</div>
</body>
</html>
