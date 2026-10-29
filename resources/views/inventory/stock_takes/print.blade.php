<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock_Take_Audit_{{ $stockTake->reference_no }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
            font-size: 11px;
            color: #1e293b;
            background: #f8fafc;
            padding: 30px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: #ffffff;
            padding: 35px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .logo-title h1 {
            font-size: 20px;
            font-weight: 900;
            color: #0f172a;
        }
        .logo-title p {
            color: #64748b;
            font-size: 11px;
            margin-top: 3px;
        }
        .doc-details {
            text-align: right;
        }
        .doc-details h2 {
            font-size: 16px;
            color: #0f172a;
            font-weight: 800;
            text-transform: uppercase;
        }
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            background: #f1f5f9;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 25px;
        }
        .metric-card h4 {
            font-size: 10px;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 4px;
        }
        .metric-card .val {
            font-size: 16px;
            font-weight: 800;
            font-family: monospace;
            color: #0f172a;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        th {
            background: #f8fafc;
            color: #475569;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            border-bottom: 2px solid #cbd5e1;
            text-align: left;
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: monospace; }
        .variance-negative { color: #dc2626; font-weight: bold; }
        .variance-positive { color: #16a34a; font-weight: bold; }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding-top: 20px;
        }
        .sig-box {
            width: 220px;
            text-align: center;
            border-top: 1px dashed #94a3b8;
            padding-top: 8px;
            font-size: 10px;
            color: #64748b;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .container { box-shadow: none; padding: 0; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="max-width: 900px; margin: 0 auto 15px; text-align: right;">
        <button onclick="window.print()" style="background: #d97706; color: #fff; border: none; padding: 8px 18px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 12px;">
            Print / Export Audit PDF
        </button>
    </div>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo-title">
                <h1>PSI ONE GLOBAL POS</h1>
                <p>Physical Stock Count &amp; Inventory Variance Reconciliation Audit</p>
                <p>Branch: {{ $stockTake->branch->name }} ({{ $stockTake->branch->code }})</p>
            </div>
            <div class="doc-details">
                <h2>Audit Report</h2>
                <p><strong>Audit Ref:</strong> {{ $stockTake->reference_no }}</p>
                <p><strong>Audited By:</strong> {{ $stockTake->user->name }}</p>
                <p><strong>Date:</strong> {{ $stockTake->started_at->format('F d, Y H:i') }}</p>
                <p><strong>Status:</strong> {{ strtoupper($stockTake->status) }}</p>
            </div>
        </div>

        <!-- Metrics -->
        <div class="metrics-grid">
            <div class="metric-card">
                <h4>System Baseline Qty</h4>
                <div class="val">{{ number_format($stockTake->total_system_qty) }}</div>
            </div>
            <div class="metric-card">
                <h4>Physical Verified Qty</h4>
                <div class="val">{{ number_format($stockTake->total_physical_qty) }}</div>
            </div>
            <div class="metric-card">
                <h4>Net Discrepancy Units</h4>
                <div class="val {{ $stockTake->total_variance_qty < 0 ? 'variance-negative' : ($stockTake->total_variance_qty > 0 ? 'variance-positive' : '') }}">
                    {{ $stockTake->total_variance_qty > 0 ? '+' : '' }}{{ number_format($stockTake->total_variance_qty) }}
                </div>
            </div>
            <div class="metric-card">
                <h4>Net Discrepancy Valuation</h4>
                <div class="val {{ $stockTake->total_variance_cost < 0 ? 'variance-negative' : ($stockTake->total_variance_cost > 0 ? 'variance-positive' : '') }}">
                    Rs. {{ number_format($stockTake->total_variance_cost, 2) }}
                </div>
            </div>
        </div>

        <!-- Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 30px;" class="text-center">#</th>
                    <th>Product &amp; SKU</th>
                    <th class="text-center">Barcode</th>
                    <th class="text-right">Unit Cost</th>
                    <th class="text-center">System Target</th>
                    <th class="text-center">Physical Count</th>
                    <th class="text-center">Variance</th>
                    <th class="text-right">Valuation Impact (Rs.)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stockTake->items as $idx => $item)
                    <tr>
                        <td class="text-center" style="color: #64748b;">{{ $idx + 1 }}</td>
                        <td>
                            <strong>{{ $item->productVariation->product->name }}</strong>
                            <div style="font-size: 10px; color: #64748b;">{{ $item->productVariation->name }} (SKU: {{ $item->productVariation->sku }})</div>
                        </td>
                        <td class="text-center font-mono" style="color: #64748b;">{{ $item->productVariation->barcode ?: 'N/A' }}</td>
                        <td class="text-right font-mono">{{ number_format($item->unit_cost, 2) }}</td>
                        <td class="text-center font-mono">{{ $item->system_qty }}</td>
                        <td class="text-center font-mono font-bold">{{ $item->physical_qty }}</td>
                        <td class="text-center font-mono {{ $item->variance_qty < 0 ? 'variance-negative' : ($item->variance_qty > 0 ? 'variance-positive' : '') }}">
                            {{ $item->variance_qty > 0 ? '+' : '' }}{{ $item->variance_qty }}
                        </td>
                        <td class="text-right font-mono {{ $item->variance_cost < 0 ? 'variance-negative' : ($item->variance_cost > 0 ? 'variance-positive' : '') }}">
                            {{ number_format($item->variance_cost, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Signatures -->
        <div class="signatures">
            <div class="sig-box">
                Audited &amp; Counted By<br>
                <strong>{{ $stockTake->user->name }}</strong><br>
                Inventory Auditor
            </div>
            <div class="sig-box">
                Branch Manager Certification<br>
                <strong>{{ $stockTake->branch->name }}</strong><br>
                Signature &amp; Date
            </div>
            <div class="sig-box">
                Head of Finance / Audit<br>
                <strong>PSI ONE GLOBAL POS</strong><br>
                Reconciliation Approval
            </div>
        </div>
    </div>

</body>
</html>

