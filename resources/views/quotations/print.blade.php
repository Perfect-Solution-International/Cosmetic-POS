<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation_{{ $quotation->quotation_no }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
            font-size: 12px;
            color: #1e293b;
            background: #f8fafc;
            padding: 30px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .logo-title h1 {
            font-size: 22px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.5px;
        }
        .logo-title span {
            color: #e11d48;
        }
        .logo-title p {
            color: #64748b;
            font-size: 11px;
            margin-top: 4px;
        }
        .doc-details {
            text-align: right;
        }
        .doc-details h2 {
            font-size: 18px;
            color: #0f172a;
            font-weight: 800;
            text-transform: uppercase;
        }
        .doc-details p {
            font-size: 12px;
            color: #475569;
            margin-top: 2px;
        }
        .meta-grid {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            gap: 20px;
        }
        .meta-col {
            flex: 1;
        }
        .meta-col h3 {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
            margin-bottom: 6px;
        }
        .meta-col p {
            font-size: 12px;
            color: #334155;
            line-height: 1.5;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        th {
            background: #f1f5f9;
            color: #475569;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 12px;
            border-bottom: 2px solid #cbd5e1;
            text-align: left;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 12px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: monospace; }
        .totals-table {
            width: 320px;
            margin-left: auto;
            margin-bottom: 30px;
        }
        .totals-table td {
            padding: 6px 12px;
        }
        .totals-table tr.grand-total td {
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
            border-top: 2px solid #0f172a;
            border-bottom: 2px solid #0f172a;
        }
        .terms {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 30px;
            font-size: 11px;
            color: #475569;
            line-height: 1.6;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            padding-top: 20px;
        }
        .sig-box {
            width: 200px;
            text-align: center;
            border-top: 1px dashed #94a3b8;
            padding-top: 8px;
            font-size: 11px;
            color: #64748b;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .container { box-shadow: none; padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="max-width: 800px; margin: 0 auto 15px; text-align: right;">
        <button onclick="window.print()" style="background: #e11d48; color: #fff; border: none; padding: 8px 18px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 12px;">
            Print / Save PDF
        </button>
    </div>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo-title">
                <h1>PSI ONE <span>GLOBAL POS</span></h1>
                <p>{{ $quotation->branch->name }} &bull; {{ $quotation->branch->address }}, {{ $quotation->branch->city }}</p>
                <p>Phone: {{ $quotation->branch->phone }} &bull; Email: {{ $quotation->branch->email }}</p>
            </div>
            <div class="doc-details">
                <h2>Commercial Quotation</h2>
                <p><strong>Quote #:</strong> {{ $quotation->quotation_no }}</p>
                <p><strong>Date:</strong> {{ $quotation->date->format('F d, Y') }}</p>
                @if($quotation->valid_until)
                    <p><strong>Valid Until:</strong> {{ $quotation->valid_until->format('F d, Y') }}</p>
                @endif
                <p><strong>Mode:</strong> {{ ucfirst($quotation->pricing_mode) }} Pricing</p>
            </div>
        </div>

        <!-- Meta -->
        <div class="meta-grid">
            <div class="meta-col">
                <h3>Quoted To</h3>
                <p><strong>{{ $quotation->customer_name ?: 'Valued Customer' }}</strong></p>
                @if($quotation->customer_phone)<p>Phone: {{ $quotation->customer_phone }}</p>@endif
                @if($quotation->customer_email)<p>Email: {{ $quotation->customer_email }}</p>@endif
            </div>
            <div class="meta-col">
                <h3>Prepared By</h3>
                <p><strong>{{ $quotation->user->name }}</strong></p>
                <p>Sales Representative</p>
                <p>{{ $quotation->branch->name }}</p>
            </div>
        </div>

        <!-- Line Items -->
        <table>
            <thead>
                <tr>
                    <th style="width: 40px;" class="text-center">#</th>
                    <th>Item &amp; Description</th>
                    <th class="text-center" style="width: 80px;">Qty</th>
                    <th class="text-right" style="width: 120px;">Unit Price (Rs.)</th>
                    <th class="text-right" style="width: 130px;">Amount (Rs.)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quotation->items as $idx => $item)
                    <tr>
                        <td class="text-center text-muted">{{ $idx + 1 }}</td>
                        <td>
                            <strong>{{ $item->productVariation->product->name }}</strong>
                            <div style="font-size: 11px; color: #64748b;">{{ $item->productVariation->name }} (SKU: {{ $item->productVariation->sku }})</div>
                        </td>
                        <td class="text-center font-mono">{{ $item->quantity }}</td>
                        <td class="text-right font-mono">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-right font-mono">{{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <table class="totals-table">
            <tr>
                <td>Subtotal:</td>
                <td class="text-right font-mono">Rs. {{ number_format($quotation->subtotal, 2) }}</td>
            </tr>
            @if($quotation->discount_amount > 0)
                <tr>
                    <td style="color: #e11d48;">Discount:</td>
                    <td class="text-right font-mono" style="color: #e11d48;">-Rs. {{ number_format($quotation->discount_amount, 2) }}</td>
                </tr>
            @endif
            @if($quotation->tax_amount > 0)
                <tr>
                    <td>Tax ({{ $quotation->tax_rate }}%):</td>
                    <td class="text-right font-mono">Rs. {{ number_format($quotation->tax_amount, 2) }}</td>
                </tr>
            @endif
            <tr class="grand-total">
                <td>GRAND TOTAL:</td>
                <td class="text-right font-mono">Rs. {{ number_format($quotation->grand_total, 2) }}</td>
            </tr>
        </table>

        <!-- Terms -->
        @if($quotation->terms)
            <div class="terms">
                <strong>Terms &amp; Payment Conditions:</strong><br>
                {!! nl2br(e($quotation->terms)) !!}
            </div>
        @endif

        <!-- Signatures -->
        <div class="signatures">
            <div class="sig-box">
                Authorized Signature<br>
                <strong>PSI ONE GLOBAL</strong>
            </div>
            <div class="sig-box">
                Customer Acceptance<br>
                Signature &amp; Company Stamp
            </div>
        </div>
    </div>

</body>
</html>

