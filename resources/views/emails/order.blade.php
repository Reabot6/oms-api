<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order {{ $type === 'created' ? 'Confirmation' : 'Update' }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #007bff; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .order-info { margin: 20px 0; }
        .items-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .items-table th, .items-table td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        .items-table th { background: #007bff; color: white; }
        .total { text-align: right; font-size: 18px; font-weight: bold; margin-top: 20px; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $type === 'created' ? 'Order Confirmation' : 'Order Status Update' }}</h1>
        </div>

        <div class="content">
            <p>Dear {{ $customer->name }},</p>

            @if($type === 'created')
                <p>Thank you for your order! Your order has been successfully placed.</p>
            @else
                <p>Your order status has been updated to: <strong>{{ ucfirst($order->status) }}</strong></p>
            @endif

            <div class="order-info">
                <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
                <p><strong>Order Date:</strong> {{ $order->created_at->format('F d, Y') }}</p>
                <p><strong>Status:</strong> {{ ucfirst($order->status) }}</p>
            </div>

            <h3>Order Items:</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
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

            <div class="total">
                <p>Subtotal: ${{ number_format($order->subtotal, 2) }}</p>
                <p>Tax: ${{ number_format($order->tax, 2) }}</p>
                <p>Shipping: ${{ number_format($order->shipping, 2) }}</p>
                <p><strong>Total: ${{ number_format($order->total, 2) }}</strong></p>
            </div>

            <h3>Shipping Address:</h3>
            <p>{{ $order->shipping_address }}</p>
        </div>

        <div class="footer">
            <p>Thank you for shopping with us!</p>
            <p>If you have any questions, please contact our support team.</p>
        </div>
    </div>
</body>
</html>
