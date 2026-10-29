@extends('layouts.pos')

@section('title', 'Barcode Label Generator')

@section('content')
<div class="h-full flex flex-col overflow-hidden bg-carbon-950">
    <!-- Header -->
    <div class="bg-carbon-900 border-b border-carbon-800 px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4 shrink-0 no-print">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-brand-600/20 border border-brand-500/30 flex items-center justify-center text-brand-500">
                <i class="fa-solid fa-barcode text-lg"></i>
            </div>
            <div>
                <h1 class="text-base sm:text-lg font-black text-white flex items-center gap-2">
                    <span>Barcode Label Printing</span>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-carbon-800 text-silver-300 border border-carbon-700">50mm x 30mm Thermal / A4</span>
                </h1>
                <p class="text-xs text-silver-400">Print retail price stickers, SKU barcodes & batch labels for shelves and cosmetics packaging</p>
            </div>
        </div>

        <div class="flex items-center gap-2.5">
            <button
                type="button"
                onclick="window.print()"
                class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-brand-600 to-rose-700 hover:from-brand-500 hover:to-rose-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-brand-600/30 transition flex items-center gap-2 touch-action-btn"
            >
                <i class="fa-solid fa-print"></i>
                <span>Print Labels (Ctrl+P)</span>
            </button>
        </div>
    </div>

    <!-- Main Container -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-6">
        
        <!-- Controls & Filter Card (Hidden on Print) -->
        <div class="p-4 sm:p-5 rounded-2xl bg-carbon-900 border border-carbon-800 shadow-xl space-y-4 no-print">
            <form method="GET" action="{{ route('inventory.barcodes') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                <div class="sm:col-span-6">
                    <label class="text-[11px] font-bold text-silver-300 uppercase block mb-1.5">Select Product Variation *</label>
                    <select name="variation_id" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                        @foreach($variations as $v)
                            <option value="{{ $v->id }}" {{ $selectedVariation && $selectedVariation->id == $v->id ? 'selected' : '' }}>
                                {{ $v->product->name ?? '' }} - {{ $v->name }} (SKU: {{ $v->sku }}) - Rs. {{ number_format($v->selling_price, 2) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-3">
                    <label class="text-[11px] font-bold text-silver-300 uppercase block mb-1.5">Quantity of Stickers *</label>
                    <input type="number" name="qty" min="1" max="100" value="{{ $labelQty }}" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-mono font-bold text-right focus:outline-none focus:border-brand-500">
                </div>

                <div class="sm:col-span-3">
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-carbon-800 hover:bg-carbon-750 text-white text-xs font-bold border border-carbon-700 transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-arrows-rotate"></i>
                        <span>Generate Stickers</span>
                    </button>
                </div>
            </form>
        </div>

        @if($selectedVariation)
        <!-- Sticker Grid Area -->
        <div class="space-y-3">
            <div class="flex items-center justify-between text-xs text-silver-400 no-print">
                <span>Previewing <strong>{{ $labelQty }}</strong> label sticker(s) for <strong>{{ $selectedVariation->product->name ?? '' }} ({{ $selectedVariation->name }})</strong>:</span>
                <span class="text-silver-500 font-mono">Format: 50x30mm Standard Retail Adhesive Tag</span>
            </div>

            <!-- Print Sheet Container -->
            <div class="label-sheet-container grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6 gap-3 print:grid-cols-3 print:gap-2">
                @for($i = 0; $i < $labelQty; $i++)
                    <div class="label-sticker bg-white text-slate-900 border border-slate-300 rounded-lg p-2.5 shadow-sm flex flex-col justify-between text-center select-none aspect-[5/3] overflow-hidden">
                        <!-- Top Store Name -->
                        <div class="flex items-center justify-between text-[8px] font-extrabold uppercase tracking-wider text-slate-600 border-b border-slate-200 pb-0.5">
                            <span>PSI ONE GLOBAL</span>
                            <span>{{ $selectedVariation->product->brand->name ?? 'RETAIL' }}</span>
                        </div>

                        <!-- Product Name & Shade/Size -->
                        <div class="my-auto py-1">
                            <div class="text-[10px] font-black text-slate-900 leading-tight truncate">
                                {{ $selectedVariation->product->name ?? '' }}
                            </div>
                            <div class="text-[9px] font-semibold text-slate-600 truncate mt-0.5">
                                {{ $selectedVariation->name }}
                            </div>
                        </div>

                        <!-- Barcode Simulation Pattern -->
                        <div class="flex flex-col items-center justify-center my-0.5">
                            <div class="barcode-bars h-7 w-full flex items-center justify-center gap-[1.5px] px-2 overflow-hidden">
                                @php
                                    $code = $selectedVariation->barcode ?: ($selectedVariation->sku ?: '12345678');
                                    $len = strlen($code);
                                @endphp
                                @for($j = 0; $j < 36; $j++)
                                    @php
                                        $w = ($j % 3 == 0) ? 'w-[2.5px]' : (($j % 5 == 0) ? 'w-[3px]' : 'w-[1.2px]');
                                        $h = ($j == 0 || $j == 1 || $j == 34 || $j == 35) ? 'h-7' : 'h-6';
                                    @endphp
                                    <span class="bg-black {{ $w }} {{ $h }} inline-block shrink-0"></span>
                                @endfor
                            </div>
                            <span class="text-[8.5px] font-mono tracking-widest font-black text-slate-800 mt-0.5">
                                {{ $selectedVariation->barcode ?: $selectedVariation->sku }}
                            </span>
                        </div>

                        <!-- Price & Batch Bottom Strip -->
                        <div class="pt-1 border-t border-slate-200 flex items-baseline justify-between text-[8.5px] font-mono font-bold text-slate-700">
                            <span class="truncate">SKU: {{ $selectedVariation->sku }}</span>
                            <span class="text-xs font-black text-rose-700 font-mono">
                                Rs. {{ number_format($selectedVariation->selling_price, 2) }}
                            </span>
                        </div>
                    </div>
                @endfor
            </div>
        </div>
        @endif

    </div>
</div>

<style>
@media print {
    body {
        background-color: white !important;
        color: black !important;
    }
    .no-print, aside, header, nav {
        display: none !important;
    }
    .label-sheet-container {
        display: grid !important;
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 8px !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .label-sticker {
        border: 1px dashed #bbb !important;
        box-shadow: none !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        width: 100% !important;
        height: auto !important;
    }
}
</style>
@endsection

