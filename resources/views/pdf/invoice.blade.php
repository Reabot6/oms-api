<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice - {{ $order->order_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { margin: 0; color: #333; }
        .info-section { margin-bottom: 30px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .info-box { width: 48%; }
        .info-box h3 { margin: 0 0 10px 0; color: #555; }
        .items-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .items-table th, .items-table td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        .items-table th { background: #f0f0f0; font-weight: bold; }
        .totals { margin-top: 30px; text-align: right; }
        .totals table { margin-left: auto; width: 300px; }
        .totals td { padding: 5px 10px; }
        .totals .grand-total { font-size: 18px; font-weight: bold; border-top: 2px solid #333; }
        .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>INVOICE</h1>
        <p><strong>Invoice Number:</strong> {{ $order->order_number }}</p>
        <p><strong>Date:</strong> {{ $order->created_at->format('F d, Y') }}</p>
    </div>

    <div class="info-section">
        <div class="info-row">
            <div class="info-box">
                <h3>Customer Information</h3>
                <p>
                    <strong>{{ $order->customer->name }}</strong><br>
                    {{ $order->customer->email }}<br>
                    @if($order->customer->phone)
                        {{ $order->customer->phone }}<br>
                    @endif
                </p>
            </div>
            <div class="info-box">
                <h3>Shipping Address</h3>
                <p>{{ nl2br($order->shipping_address) }}</p>
            </div>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    {{ $item->product->name }}
                    @if($item->productVariant)
                        <br><small>{{ $item->productVariant->name }}</small>
                    @endif
                </td>
                <td>{{ $item->quantity }}</td>
                <td>${{ number_format($item->unit_price, 2) }}</td>
                <td>${{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td>Subtotal:</td>
                <td>${{ number_format($order->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td>Tax:</td>
                <td>${{ number_format($order->tax, 2) }}</td>
            </tr>
            <tr>
                <td>Shipping:</td>
                <td>${{ number_format($order->shipping, 2) }}</td>
            </tr>
            <tr class="grand-total">
                <td>Total:</td>
                <td>${{ number_format($order->total, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>Thank you for your business!</p>
        <p>This is a computer-generated invoice and does not require a signature.</p>
    </div>
</body>
</html>
