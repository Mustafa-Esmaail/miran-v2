<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Orders Export</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111;
            font-size: 12px;
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #eee;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .title {
            font-size: 18px;
            font-weight: 700;
        }
        .subtitle {
            font-size: 11px;
            color: #666;
        }
        .logo {
            height: 40px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background: #f5f5f5;
        }
        .muted {
            color: #666;
        }
</style>
</head>
<body>
    @php
        $safeText = function ($value): string {
            if ($value === null) {
                return '';
            }

            $text = (string) $value;
            $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $text);

            return $clean === false ? '' : $clean;
        };
    @endphp
    <div class="header">
        <div>
            <div class="title">Orders Export</div>
            <div class="subtitle">Generated {{ $generatedAt }}</div>
        </div>
        @if ($logoUrl)
            <img class="logo" src="{{ $logoUrl }}" alt="Logo">
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Order No</th>
                <th>Customer</th>
                <th>Email</th>
                <th>Status</th>
                <th>Subtotal</th>
                <th>Discount</th>
                <th>Total</th>
                <th>Currency</th>
                <th>Payment Method</th>
                <th>Coupon</th>
                <th>Employee Code</th>
                <th>Employee Name</th>
                <th>Notes</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $order)
                <tr>
                    <td>{{ $order->order_no }}</td>
                    <td>{{ $safeText($order->user?->name) }}</td>
                    <td>{{ $safeText($order->user?->email) }}</td>
                    <td>{{ $safeText($order->status?->value ?? $order->status) }}</td>
                    <td>{{ $order->subtotal_amount }}</td>
                    <td>{{ $order->discount_amount }}</td>
                    <td>{{ $order->total_amount }}</td>
                    <td>{{ $safeText($order->currency) }}</td>
                    <td>{{ $safeText($order->primary_payment_method) }}</td>
                    <td>{{ $safeText($order->coupon?->code) }}</td>
                    <td>{{ $safeText($order->referredByEmployee?->employee_code) }}</td>
                    <td>{{ $safeText($order->referredByEmployee?->name) }}</td>
                    <td>{{ $safeText($order->notes) }}</td>
                    <td>{{ $order->created_at?->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="14" class="muted">No records</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
