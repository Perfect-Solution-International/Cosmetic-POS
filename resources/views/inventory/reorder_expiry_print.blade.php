<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Stock Reorder & Batch Expiry Audit - {{ $branchName }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
                color: black !important;
                padding: 0 !important;
            }
            .print-page {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen py-8 text-gray-900 font-sans antialiased">

    <!-- Top floating action bar (hidden during print) -->
    <div class="no-print max-w-4xl mx-auto mb-4 px-4 flex items-center justify-between">
        <a href="{{ route('inventory.reorder_expiry', request()->query()) }}" class="px-4 py-2 rounded-lg bg-gray-800 text-white text-xs font-bold hover:bg-gray-700 flex items-center gap-2">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Back to Reorder Screen</span>
        </a>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-lg flex items-center gap-2">
                <i class="fa-solid fa-print"></i>
                <span>Print Official Audit Sheet (A4 / PDF)</span>
            </button>
        </div>
    </div>

    <!-- Official A4 Document Container -->
    <div class="print-page max-w-4xl mx-auto bg-white border border-gray-300 shadow-xl rounded-xl p-8 sm:p-12">
        
        <!-- Header -->
        <div class="flex items-start justify-between border-b-2 border-gray-900 pb-5 mb-5">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/psi-logo.png') }}" alt="PSI Logo" class="w-14 h-14 object-contain">
                <div>
                    <h1 class="text-xl font-black tracking-tight text-gray-900">PSI ONE GLOBAL POS</h1>
                    <p class="text-xs font-semibold text-gray-600 tracking-wider">PROCUREMENT & INVENTORY CONTROL &bull; WEEKLY REORDER & EXPIRY AUDIT</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $branchName }}</p>
                </div>
            </div>

            <div class="text-right">
                <span class="inline-block px-3 py-1 bg-gray-900 text-white text-xs font-black uppercase tracking-widest rounded mb-1.5">
                    WEEKLY REORDER AUDIT
                </span>
                <div class="text-xs text-gray-600">Audit Date: <strong>{{ date('d M Y') }}</strong></div>
                <div class="text-xs text-gray-500">Auditor: {{ auth()->user()->name ?? 'System Administrator' }}</div>
            </div>
        </div>

        <!-- Executive Highlights Bar -->
        <div class="grid grid-cols-4 gap-3 border border-gray-300 rounded-lg p-3 bg-gray-50 mb-6 text-xs text-center">
            <div>
                <span class="text-[10px] text-gray-500 uppercase font-bold block">Items Needing Reorder</span>
                <span class="font-mono font-black text-sm text-amber-700">{{ $reorderItems->count() }} Lines</span>
            </div>
            <div>
                <span class="text-[10px] text-gray-500 uppercase font-bold block">Est. Replenishment Cost</span>
                <span class="font-mono font-black text-sm text-gray-900">Rs. {{ number_format($totalReorderEstCost, 2) }}</span>
            </div>
            <div>
                <span class="text-[10px] text-gray-500 uppercase font-bold block">Expired Stock Quarantine</span>
                <span class="font-mono font-black text-sm text-rose-700">Rs. {{ number_format($totalExpiredCost, 2) }}</span>
            </div>
            <div>
                <span class="text-[10px] text-gray-500 uppercase font-bold block">Near Expiry (90 Days)</span>
                <span class="font-mono font-black text-sm text-amber-700">Rs. {{ number_format($totalNearExpiryCost, 2) }}</span>
            </div>
        </div>

        <!-- Section 1: Stock Replenishment & Reorder Procurement Schedule -->
        <div class="mb-6">
            <h3 class="text-xs font-black uppercase tracking-wider text-gray-800 border-b border-gray-300 pb-1 mb-2 flex justify-between">
                <span>1. STOCK REPLENISHMENT & REORDER SCHEDULE</span>
                <span class="font-normal text-gray-500 text-[11px]">Threshold &le; Safety Level</span>
            </h3>

            @if($reorderItems->isEmpty())
                <p class="text-xs text-gray-500 italic py-2">All product inventory lines are healthy and above reorder thresholds.</p>
            @else
                <table class="w-full text-xs font-mono">
                    <thead>
                        <tr class="border-b border-gray-200 text-gray-500 text-[10px] uppercase font-sans font-bold text-left">
                            <th class="py-1">Product Description</th>
                            <th class="py-1 text-center">SKU</th>
                            <th class="py-1 text-right">Current</th>
                            <th class="py-1 text-right">Reorder Lvl</th>
                            <th class="py-1 text-right">Suggested Qty</th>
                            <th class="py-1 text-right">Unit Cost</th>
                            <th class="py-1 text-right">Est. Cost (Rs.)</th>
                            <th class="py-1 text-center font-sans">Urgency</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($reorderItems as $item)
                            <tr>
                                <td class="py-1.5 font-sans font-medium text-gray-800">
                                    {{ $item->product_name }} {{ $item->variation_name ? '('.$item->variation_name.')' : '' }}
                                </td>
                                <td class="py-1.5 text-center text-gray-500 text-[11px]">{{ $item->sku }}</td>
                                <td class="py-1.5 text-right font-bold {{ $item->current_stock <= 0 ? 'text-rose-700' : 'text-amber-700' }}">
                                    {{ number_format($item->current_stock) }}
                                </td>
                                <td class="py-1.5 text-right text-gray-500">{{ number_format($item->reorder_threshold) }}</td>
                                <td class="py-1.5 text-right font-bold text-emerald-800">{{ number_format($item->suggested_order) }}</td>
                                <td class="py-1.5 text-right text-gray-600">Rs. {{ number_format($item->purchase_cost, 2) }}</td>
                                <td class="py-1.5 text-right font-bold text-gray-900">Rs. {{ number_format($item->est_total_cost, 2) }}</td>
                                <td class="py-1.5 text-center text-[10px] font-sans font-bold {{ $item->urgency === 'OUT_OF_STOCK' ? 'text-rose-700' : 'text-amber-700' }}">
                                    {{ str_replace('_', ' ', $item->urgency) }}
                                </td>
                            </tr>
                        @endforeach
                        <tr class="border-b-2 border-gray-900 bg-gray-50 font-sans font-bold">
                            <td colspan="6" class="py-2 text-gray-900 uppercase">TOTAL ESTIMATED PURCHASE ORDER VALUE</td>
                            <td class="py-2 text-right font-mono text-sm text-gray-900 font-black">Rs. {{ number_format($totalReorderEstCost, 2) }}</td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            @endif
        </div>

        <!-- Section 2: Batch Expiry Risk Schedule (Within 90 Days & Expired) -->
        <div class="mb-6">
            <h3 class="text-xs font-black uppercase tracking-wider text-gray-800 border-b border-gray-300 pb-1 mb-2 flex justify-between">
                <span>2. BATCH EXPIRY RISK & QUARANTINE SCHEDULE (FEFO AUDIT)</span>
                <span class="font-normal text-gray-500 text-[11px]">Shelf-Life Within 90 Days</span>
            </h3>

            @if($expiringBatches->isEmpty())
                <p class="text-xs text-gray-500 italic py-2">No batches are expired or expiring within 90 days. Stock is fresh.</p>
            @else
                <table class="w-full text-xs font-mono">
                    <thead>
                        <tr class="border-b border-gray-200 text-gray-500 text-[10px] uppercase font-sans font-bold text-left">
                            <th class="py-1">Product Item</th>
                            <th class="py-1 text-center">Batch No</th>
                            <th class="py-1 text-center">Expiry Date</th>
                            <th class="py-1 text-center">Days Left</th>
                            <th class="py-1 text-right">Quantity</th>
                            <th class="py-1 text-right">Cost At Risk</th>
                            <th class="py-1 text-center font-sans">Risk Category</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($expiringBatches as $batch)
                            <tr>
                                <td class="py-1.5 font-sans font-medium text-gray-800">
                                    {{ $batch->product_name }} {{ $batch->variation_name ? '('.$batch->variation_name.')' : '' }}
                                </td>
                                <td class="py-1.5 text-center text-gray-700 font-bold">{{ $batch->batch_number }}</td>
                                <td class="py-1.5 text-center text-gray-600">{{ $batch->exp_date }}</td>
                                <td class="py-1.5 text-center {{ $batch->is_expired ? 'text-rose-700 font-bold' : 'text-gray-900' }}">
                                    {{ $batch->is_expired ? 'EXPIRED' : $batch->days_left . 'd' }}
                                </td>
                                <td class="py-1.5 text-right font-bold text-gray-900">{{ number_format($batch->quantity) }}</td>
                                <td class="py-1.5 text-right font-bold {{ $batch->is_expired ? 'text-rose-700' : 'text-amber-700' }}">
                                    Rs. {{ number_format($batch->cost_at_risk, 2) }}
                                </td>
                                <td class="py-1.5 text-center text-[10px] font-sans font-bold {{ $batch->is_expired ? 'text-rose-700' : 'text-amber-700' }}">
                                    {{ $batch->status_label }}
                                </td>
                            </tr>
                        @endforeach
                        <tr class="border-b-2 border-gray-900 bg-gray-50 font-sans font-bold">
                            <td colspan="5" class="py-2 text-gray-900 uppercase">TOTAL INVENTORY VALUE AT RISK</td>
                            <td class="py-2 text-right font-mono text-sm text-rose-800 font-black">Rs. {{ number_format($totalExpiredCost + $totalNearExpiryCost, 2) }}</td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            @endif
        </div>

        <!-- Authorization Signatures Block -->
        <div class="border-t border-gray-300 pt-6 mt-8 grid grid-cols-3 gap-8 text-center text-xs">
            <div>
                <div class="border-b border-gray-400 pb-1 h-10 flex items-end justify-center font-cursive text-gray-700">
                    {{ auth()->user()->name ?? 'Auditor' }}
                </div>
                <div class="font-bold text-gray-800 mt-1">Audit Conducted By</div>
                <div class="text-[10px] text-gray-500">Warehouse Inventory Clerk &bull; {{ date('d/m/Y') }}</div>
            </div>

            <div>
                <div class="border-b border-gray-400 pb-1 h-10 flex items-end justify-center text-gray-400 italic">
                    (Sign Here)
                </div>
                <div class="font-bold text-gray-800 mt-1">Store Operations Manager</div>
                <div class="text-[10px] text-gray-500">Discrepancies & Stock Levels Approved</div>
            </div>

            <div>
                <div class="border-b border-gray-400 pb-1 h-10 flex items-end justify-center text-gray-400 italic">
                    (Sign Here)
                </div>
                <div class="font-bold text-gray-800 mt-1">Procurement Director</div>
                <div class="text-[10px] text-gray-500">PO Reorder Allocation Authorized</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-6 pt-3 border-t border-gray-200 text-center text-[10px] text-gray-400">
            Official Computerized Inventory Reorder & Expiry Audit generated by Psi One Global POS. Document Hash: {{ md5($branchName . $totalReorderEstCost . date('Ymd')) }}.
        </div>

    </div>

</body>
</html>
