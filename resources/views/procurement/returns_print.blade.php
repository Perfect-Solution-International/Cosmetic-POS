<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Debit Note Voucher - {{ $return->return_no }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            body { font-size: 12px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-white text-slate-900 p-8 max-w-4xl mx-auto">
    <div class="no-print mb-6 flex justify-between items-center border-b pb-4">
        <a href="{{ route('procurement.returns') }}" class="text-sm font-bold text-slate-600 hover:text-black">&larr; Back to Returns</a>
        <button onclick="window.print()" class="px-4 py-2 bg-red-600 text-white font-bold rounded-lg shadow">Print Debit Note Voucher</button>
    </div>

    <!-- Voucher Header -->
    <div class="flex justify-between items-start border-b pb-6">
        <div>
            <h1 class="text-2xl font-black text-red-600">PSI ONE GLOBAL POS</h1>
            <p class="text-xs text-slate-500 font-semibold tracking-wider uppercase mt-1">Goods Return Note / Debit Note</p>
            <p class="text-xs text-slate-600 mt-2">Branch: {{ $return->branch->name ?? 'Colombo HQ' }}</p>
        </div>
        <div class="text-right">
            <span class="inline-block px-3 py-1 bg-red-100 text-red-800 font-mono font-bold text-xs rounded-full">
                DEBIT NOTE
            </span>
            <div class="mt-2 font-mono text-sm font-black">{{ $return->return_no }}</div>
            <div class="text-xs text-slate-500">{{ $return->created_at->format('d M Y, h:i A') }}</div>
        </div>
    </div>

    <!-- Supplier Details -->
    <div class="grid grid-cols-2 gap-8 my-6 text-xs">
        <div class="p-4 rounded-xl bg-slate-50 border">
            <div class="font-bold text-slate-400 uppercase text-[10px]">Returned To Supplier</div>
            <div class="font-bold text-sm text-slate-900 mt-1">{{ $return->supplier->name ?? 'Supplier' }}</div>
            <div class="text-slate-600 mt-0.5">{{ $return->supplier->address ?? '' }}</div>
            <div class="text-slate-600 font-mono mt-0.5">Phone: {{ $return->supplier->phone ?? 'N/A' }}</div>
        </div>
        <div class="p-4 rounded-xl bg-slate-50 border">
            <div class="font-bold text-slate-400 uppercase text-[10px]">Return Reference & Reason</div>
            <div class="font-bold text-slate-900 mt-1">Credit Note Ref: {{ $return->credit_note_no ?? 'None' }}</div>
            <div class="text-slate-600 mt-0.5">Reason: {{ $return->reason }}</div>
            <div class="text-slate-600 mt-0.5">Authorized By: {{ $return->creator->name ?? 'Store Manager' }}</div>
        </div>
    </div>

    <!-- Items Table -->
    <table class="w-full text-left text-xs border">
        <thead class="bg-slate-100 font-bold uppercase text-[10px] text-slate-700">
            <tr>
                <th class="p-3">#</th>
                <th class="p-3">Product Description</th>
                <th class="p-3 text-right">Unit Cost (Rs)</th>
                <th class="p-3 text-right">Qty Returned</th>
                <th class="p-3 text-right">Subtotal (Rs)</th>
            </tr>
        </thead>
        <tbody class="divide-y font-mono">
            @foreach($return->items as $idx => $item)
                <tr>
                    <td class="p-3 text-slate-400 font-sans">{{ $idx + 1 }}</td>
                    <td class="p-3 font-sans">
                        <div class="font-bold text-slate-900">{{ $item->productVariation->product->name ?? '' }}</div>
                        <div class="text-[11px] text-slate-500">{{ $item->productVariation->name ?? '' }} (SKU: {{ $item->productVariation->sku ?? '' }})</div>
                    </td>
                    <td class="p-3 text-right">{{ number_format($item->unit_cost, 2) }}</td>
                    <td class="p-3 text-right font-bold">{{ $item->quantity }}</td>
                    <td class="p-3 text-right font-bold">{{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="border-t-2 font-mono">
            <tr class="bg-slate-50">
                <td colspan="4" class="p-3 text-right font-bold font-sans uppercase">Total Debit Value:</td>
                <td class="p-3 text-right font-black text-red-600 text-sm">Rs. {{ number_format($return->total_amount, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- Signatures -->
    <div class="grid grid-cols-2 gap-16 mt-16 text-xs text-center">
        <div class="border-t pt-2">
            <p class="font-bold text-slate-800">Authorized Store Dispatcher</p>
            <p class="text-[10px] text-slate-400">PSI ONE GLOBAL POS</p>
        </div>
        <div class="border-t pt-2">
            <p class="font-bold text-slate-800">Supplier Acknowledgement & Receiver</p>
            <p class="text-[10px] text-slate-400">Signature & Company Rubber Stamp</p>
        </div>
    </div>
</body>
</html>

