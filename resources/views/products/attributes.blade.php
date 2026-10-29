@extends('layouts.pos')

@section('title', 'Dynamic Attribute Builder')

@section('content')
<div class="h-full flex flex-col overflow-hidden bg-carbon-950">

    <!-- Header -->
    <div class="bg-carbon-900 border-b border-carbon-800 px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4 shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-brand-600/20 border border-brand-500/30 flex items-center justify-center text-brand-500">
                <i class="fa-solid fa-swatchbook text-base"></i>
            </div>
            <div>
                <h2 class="text-base sm:text-lg font-extrabold text-white">Shades, Colors & Sizes</h2>
                <p class="text-xs text-silver-400">Configure retail options (Dynamic Attribute Builder: Lipstick Shades, Bag Sizes, Volumes)</p>
            </div>
        </div>

        <a href="{{ route('products.index') }}" class="px-4 py-2 rounded-xl bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 text-silver-300 hover:text-white text-xs font-bold transition flex items-center gap-2">
            <i class="fa-solid fa-arrow-left"></i> Back to Products
        </a>
    </div>

    <!-- Main Content Grid -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-6">
        <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- LEFT: Create New Attribute Form -->
            <div class="lg:col-span-1 space-y-4">
                <div class="bg-carbon-900 border border-carbon-800 rounded-2xl p-5 shadow-xl space-y-4">
                    <h3 class="text-xs font-extrabold text-white uppercase tracking-wider flex items-center gap-2 border-b border-carbon-800 pb-3">
                        <i class="fa-solid fa-plus-circle text-brand-500"></i>
                        <span>New Option Group</span>
                    </h3>

                    @if(session('status'))
                        <div class="p-3 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 text-xs font-semibold">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form action="{{ route('products.attributes.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Option Name *</label>
                            <input 
                                type="text" 
                                name="name" 
                                placeholder="e.g. Color, Shade, Size, Volume" 
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white placeholder-silver-500 text-xs font-medium focus:outline-none focus:border-brand-500"
                            >
                        </div>

                        <div>
                            <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Selection Type *</label>
                            <select name="type" required class="w-full px-3.5 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-medium focus:outline-none focus:border-brand-500">
                                <option value="dropdown">Dropdown List</option>
                                <option value="color">Color Swatch (Hex Code)</option>
                                <option value="text">Custom Text</option>
                                <option value="numeric">Number / Measurement</option>
                            </select>
                        </div>

                        <div class="pt-1">
                            <label class="flex items-center gap-2.5 cursor-pointer">
                                <input type="checkbox" name="is_variation" value="1" checked class="w-4 h-4 rounded bg-carbon-800 border-carbon-600 text-brand-600 focus:ring-brand-500">
                                <span class="text-xs font-bold text-silver-300">Creates Variations (Different SKUs or Prices)</span>
                            </label>
                            <p class="text-[10px] text-silver-500 mt-1 pl-6">Allows products to have different shades or sizes with individual barcodes & prices.</p>
                        </div>

                        <button type="submit" class="w-full py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg transition touch-action-btn">
                            Save Option Group
                        </button>
                    </form>
                </div>
            </div>

            <!-- RIGHT: Configured Attributes & Their Values -->
            <div class="lg:col-span-2 space-y-4">
                <h3 class="text-xs font-extrabold text-silver-400 uppercase tracking-wider">
                    Configured Retail Options ({{ $attributes->count() }})
                </h3>

                <div class="space-y-4">
                    @forelse($attributes as $attr)
                        <div class="bg-carbon-900 border border-carbon-800 rounded-2xl p-5 shadow-xl space-y-4">
                            <!-- Attribute Header -->
                            <div class="flex items-center justify-between border-b border-carbon-800 pb-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-carbon-800 border border-carbon-700 flex items-center justify-center text-brand-400 font-bold text-sm">
                                        <i class="fa-solid {{ $attr->type === 'color' ? 'fa-palette' : ($attr->type === 'dropdown' ? 'fa-list-check' : 'fa-font') }}"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-extrabold text-white">{{ $attr->name }}</h4>
                                        <div class="flex items-center gap-2 text-[10px] text-silver-400 mt-0.5">
                                            <span class="font-mono uppercase">{{ $attr->type }}</span>
                                            @if($attr->is_variation)
                                                <span>•</span>
                                                <span class="text-emerald-400 font-semibold">Variation Enabled</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Attribute Values List -->
                            <div>
                                <span class="text-[10px] text-silver-500 font-bold uppercase block mb-2">Available Values / Options</span>
                                <div class="flex flex-wrap items-center gap-2">
                                    @forelse($attr->values as $val)
                                        <div class="px-2.5 py-1 rounded-xl bg-carbon-850 border border-carbon-700/80 flex items-center gap-2 text-xs">
                                            @if($val->color_code)
                                                <span class="w-3.5 h-3.5 rounded-full border border-white/20 shadow-sm" style="background-color: {{ $val->color_code }}"></span>
                                            @endif
                                            <span class="font-semibold text-white">{{ $val->value }}</span>
                                            @if($val->color_code)
                                                <span class="font-mono text-[9px] text-silver-500">{{ $val->color_code }}</span>
                                            @endif
                                        </div>
                                    @empty
                                        <span class="text-xs text-silver-500 italic">No values added yet.</span>
                                    @endforelse
                                </div>
                            </div>

                            <!-- Fast Add Value Form -->
                            <form action="{{ route('products.attributes.values.store', $attr->id) }}" method="POST" class="pt-2 border-t border-carbon-800/80 flex flex-wrap items-center gap-2">
                                @csrf
                                <div class="flex-1 min-w-[140px]">
                                    <input 
                                        type="text" 
                                        name="value" 
                                        placeholder="Add value (e.g. {{ $attr->type === 'color' ? 'Ruby Red' : ($attr->name === 'Size' ? 'XL / 100ml' : 'Matte') }})" 
                                        required 
                                        class="w-full px-3 py-1.5 rounded-lg bg-carbon-850 border border-carbon-700 text-white placeholder-silver-500 text-xs focus:outline-none focus:border-brand-500"
                                    >
                                </div>

                                @if($attr->type === 'color')
                                    <div class="flex items-center gap-1.5 bg-carbon-850 border border-carbon-700 rounded-lg px-2 py-1">
                                        <label class="text-[10px] text-silver-400 font-mono">Hex:</label>
                                        <input 
                                            type="color" 
                                            name="color_code" 
                                            value="#E51A2E"
                                            class="w-6 h-6 rounded cursor-pointer bg-transparent border-0"
                                        >
                                    </div>
                                @endif

                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-carbon-800 hover:bg-carbon-750 text-brand-400 hover:text-white border border-brand-500/30 text-xs font-bold transition">
                                    + Add Option
                                </button>
                            </form>
                        </div>
                    @empty
                        <div class="p-12 text-center bg-carbon-900 border border-carbon-800 rounded-2xl text-silver-500">
                            <i class="fa-solid fa-sliders text-3xl mb-2 text-carbon-700"></i>
                            <p class="text-xs font-semibold text-silver-400">No dynamic attributes created yet</p>
                            <p class="text-[10px] text-silver-600 mt-1">Use the left form to create attributes like Color, Size or Finish</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>

</div>
@endsection

