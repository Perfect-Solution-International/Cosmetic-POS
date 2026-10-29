<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Customer Display | {{ $branch->name ?? 'Psi One Global POS' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-carbon-950 text-white font-sans overflow-hidden flex flex-col justify-between selection:bg-brand-600">

    <!-- Top Navigation Header -->
    <header class="p-4 sm:p-5 border-b border-carbon-800 bg-carbon-900/80 backdrop-blur-md flex items-center justify-between shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-brand-500/10 border border-brand-500/30 flex items-center justify-center p-1.5 shadow-lg">
                <img src="{{ asset('images/psi-logo.png') }}" alt="PSI" class="w-full h-full object-contain filter drop-shadow">
            </div>
            <div>
                <h1 class="text-base sm:text-lg font-black tracking-tight text-white flex items-center gap-2">
                    {{ $branch->name ?? 'PSI ONE GLOBAL POS' }}
                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Customer Screen</span>
                </h1>
                <p class="text-xs text-silver-400">{{ $branch->address ?? 'Welcome to our store' }} &bull; {{ $branch->city ?? 'Sri Lanka' }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 text-xs font-mono text-silver-400 bg-carbon-800 px-3 py-1.5 rounded-xl border border-carbon-700">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span id="liveClock">--:--:--</span>
            </div>
            <button onclick="toggleFullScreen()" class="p-2 rounded-xl bg-carbon-800 hover:bg-carbon-700 text-silver-300 hover:text-white transition" title="Toggle Fullscreen">
                <i class="fa-solid fa-expand"></i>
            </button>
        </div>
    </header>

    <!-- Main Content Grid -->
    <main class="flex-1 overflow-hidden grid grid-cols-1 lg:grid-cols-12 gap-6 p-6">
        
        <!-- Left: Cart Items or Idle Promotional Slideshow (8 Cols) -->
        <section class="lg:col-span-7 xl:col-span-8 flex flex-col rounded-3xl bg-carbon-900 border border-carbon-800 shadow-2xl overflow-hidden">
            
            <!-- When Cart Has Items -->
            <div id="cartView" class="hidden flex-1 flex-col overflow-hidden">
                <div class="p-4 border-b border-carbon-800 bg-carbon-850/60 flex items-center justify-between">
                    <h2 class="text-xs font-black text-silver-300 uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-basket-shopping text-brand-500"></i>
                        <span>Your Order Items</span>
                    </h2>
                    <span id="itemsBadge" class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-carbon-800 text-white border border-carbon-700">0 items</span>
                </div>

                <div class="flex-1 overflow-y-auto p-4">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="border-b border-carbon-800 text-silver-400 font-bold uppercase text-xs">
                                <th class="py-2.5 px-3">Item</th>
                                <th class="py-2.5 px-3 text-center">Qty</th>
                                <th class="py-2.5 px-3 text-right">Unit Price</th>
                                <th class="py-2.5 px-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody id="cartTbody" class="divide-y divide-carbon-800/60">
                            <!-- Injected dynamically via BroadcastChannel -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Idle Promotional View (When Cart is Empty) -->
            <div id="idleView" class="flex-1 flex flex-col items-center justify-center p-8 text-center space-y-6">
                <div class="w-24 h-24 rounded-3xl bg-brand-500/10 border-2 border-brand-500/20 flex items-center justify-center p-4 psi-red-glow">
                    <img src="{{ asset('images/psi-logo.png') }}" alt="PSI" class="w-full h-full object-contain">
                </div>

                <div class="space-y-2 max-w-md">
                    <h2 class="text-2xl font-black text-white">Welcome to {{ $branch->name ?? 'Psi One Global POS' }}</h2>
                    <p class="text-sm text-silver-400">Please place your items on the counter. Your invoice will be displayed here live.</p>
                </div>

                <div class="grid grid-cols-3 gap-3 w-full max-w-lg pt-4">
                    <div class="p-3 rounded-2xl bg-carbon-850 border border-carbon-800 text-center">
                        <i class="fa-solid fa-qrcode text-amber-400 text-xl mb-1.5"></i>
                        <div class="text-xs font-bold text-white">LankaQR</div>
                        <div class="text-[10px] text-silver-400">All Bank Apps</div>
                    </div>
                    <div class="p-3 rounded-2xl bg-carbon-850 border border-carbon-800 text-center">
                        <i class="fa-solid fa-credit-card text-blue-400 text-xl mb-1.5"></i>
                        <div class="text-xs font-bold text-white">Visa &amp; Master</div>
                        <div class="text-[10px] text-silver-400">Contactless Card</div>
                    </div>
                    <div class="p-3 rounded-2xl bg-carbon-850 border border-carbon-800 text-center">
                        <i class="fa-solid fa-star text-emerald-400 text-xl mb-1.5"></i>
                        <div class="text-xs font-bold text-white">Loyalty Points</div>
                        <div class="text-[10px] text-silver-400">Earn with every Rs. 100</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Right: Totals, Discounts & Payment Status (4-5 Cols) -->
        <section class="lg:col-span-5 xl:col-span-4 flex flex-col justify-between rounded-3xl bg-carbon-900 border border-carbon-800 p-6 sm:p-8 shadow-2xl space-y-6">
            <div>
                <h3 class="text-xs font-black text-silver-400 uppercase tracking-wider mb-6 flex items-center justify-between">
                    <span>Payment Summary</span>
                    <span id="customerPill" class="hidden text-brand-400 normal-case font-bold"></span>
                </h3>

                <div class="space-y-3.5 text-sm">
                    <div class="flex items-center justify-between text-silver-300">
                        <span>Subtotal</span>
                        <span class="font-mono font-bold text-white text-base" id="subtotalVal">Rs. 0.00</span>
                    </div>

                    <div id="discountRow" class="hidden flex items-center justify-between text-amber-400">
                        <span>Discount</span>
                        <span class="font-mono font-bold text-base" id="discountVal">-Rs. 0.00</span>
                    </div>

                    <div id="loyaltyRow" class="hidden flex items-center justify-between text-emerald-400">
                        <span>Loyalty Points Redeemed</span>
                        <span class="font-mono font-bold text-base" id="loyaltyVal">-Rs. 0.00</span>
                    </div>

                    <div id="taxRow" class="hidden flex items-center justify-between text-silver-400">
                        <span>Tax</span>
                        <span class="font-mono font-bold text-white text-base" id="taxVal">Rs. 0.00</span>
                    </div>
                </div>
            </div>

            <!-- Big Glowing Grand Total -->
            <div class="p-6 rounded-2xl bg-carbon-950 border-2 border-brand-500/30 text-center space-y-1 psi-red-glow">
                <span class="text-xs font-black uppercase tracking-widest text-silver-400">Total Amount Payable</span>
                <div class="text-3xl sm:text-4xl font-black font-mono text-emerald-400" id="grandTotalVal">
                    Rs. 0.00
                </div>
            </div>

            <!-- Checkout Completed Status (Tendered & Change) -->
            <div id="checkoutSuccessBox" class="hidden p-4 rounded-2xl bg-emerald-500/10 border-2 border-emerald-500/30 text-center space-y-2">
                <div class="flex items-center justify-center gap-2 text-emerald-400 font-extrabold text-sm uppercase">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Payment Complete</span>
                </div>
                <div class="flex items-center justify-between text-xs text-silver-300">
                    <span>Amount Paid:</span>
                    <span class="font-mono font-bold text-white" id="paidVal">Rs. 0.00</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="font-bold text-emerald-300">Change Due:</span>
                    <span class="font-mono font-black text-emerald-400 text-lg" id="changeVal">Rs. 0.00</span>
                </div>
            </div>

            <!-- Footer Message -->
            <div class="text-center text-xs text-silver-500 pt-2 border-t border-carbon-800">
                Thank you for shopping at {{ $branch->name ?? 'Psi One Global POS' }}!
            </div>
        </section>

    </main>

    <script>
        // Clock
        function updateClock() {
            const now = new Date();
            document.getElementById('liveClock').textContent = now.toLocaleTimeString();
        }
        setInterval(updateClock, 1000);
        updateClock();

        function toggleFullScreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(() => {});
            } else {
                if (document.exitFullscreen) document.exitFullscreen();
            }
        }

        // Live Channel Listener
        const channel = new BroadcastChannel('psi_pos_customer_display');

        function renderCartData(data) {
            if (!data || !data.items || data.items.length === 0) {
                document.getElementById('cartView').classList.add('hidden');
                document.getElementById('idleView').classList.remove('hidden');
                document.getElementById('checkoutSuccessBox').classList.add('hidden');

                document.getElementById('subtotalVal').textContent = 'Rs. 0.00';
                document.getElementById('grandTotalVal').textContent = 'Rs. 0.00';
                document.getElementById('discountRow').classList.add('hidden');
                document.getElementById('loyaltyRow').classList.add('hidden');
                document.getElementById('taxRow').classList.add('hidden');
                return;
            }

            document.getElementById('idleView').classList.add('hidden');
            document.getElementById('cartView').classList.remove('hidden');
            document.getElementById('cartView').style.display = 'flex';

            const tbody = document.getElementById('cartTbody');
            tbody.innerHTML = '';

            let totalQty = 0;
            data.items.forEach(item => {
                totalQty += item.quantity;
                const isExchange = item.is_exchange || item.quantity < 0;
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-carbon-800/40 transition ' + (isExchange ? 'text-rose-400 bg-rose-500/5' : '');

                tr.innerHTML = `
                    <td class="py-3 px-3">
                        <div class="font-bold text-white text-sm">${item.name}</div>
                        <div class="text-xs text-silver-400 font-mono">
                            ${isExchange ? '<span class="text-rose-400 font-bold">[EXCHANGE/RETURN]</span> ' : ''}
                            ${item.sku}
                        </div>
                    </td>
                    <td class="py-3 px-3 text-center font-mono font-bold text-white text-sm">${item.quantity}</td>
                    <td class="py-3 px-3 text-right font-mono text-silver-300">Rs. ${parseFloat(item.unit_price).toFixed(2)}</td>
                    <td class="py-3 px-3 text-right font-mono font-black ${isExchange ? 'text-rose-400' : 'text-white'} text-sm">
                        Rs. ${parseFloat(item.subtotal).toFixed(2)}
                    </td>
                `;
                tbody.appendChild(tr);
            });

            document.getElementById('itemsBadge').textContent = totalQty + ' items';

            // Totals
            document.getElementById('subtotalVal').textContent = 'Rs. ' + parseFloat(data.subtotal || 0).toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('grandTotalVal').textContent = 'Rs. ' + parseFloat(data.grand_total || 0).toLocaleString('en-US', {minimumFractionDigits: 2});

            // Discount
            if (data.discount_amount && data.discount_amount > 0) {
                document.getElementById('discountRow').classList.remove('hidden');
                document.getElementById('discountVal').textContent = '-Rs. ' + parseFloat(data.discount_amount).toLocaleString('en-US', {minimumFractionDigits: 2});
            } else {
                document.getElementById('discountRow').classList.add('hidden');
            }

            // Loyalty
            if (data.loyalty_discount && data.loyalty_discount > 0) {
                document.getElementById('loyaltyRow').classList.remove('hidden');
                document.getElementById('loyaltyVal').textContent = '-Rs. ' + parseFloat(data.loyalty_discount).toLocaleString('en-US', {minimumFractionDigits: 2});
            } else {
                document.getElementById('loyaltyRow').classList.add('hidden');
            }

            // Tax
            if (data.tax_amount && data.tax_amount > 0) {
                document.getElementById('taxRow').classList.remove('hidden');
                document.getElementById('taxVal').textContent = 'Rs. ' + parseFloat(data.tax_amount).toLocaleString('en-US', {minimumFractionDigits: 2});
            } else {
                document.getElementById('taxRow').classList.add('hidden');
            }

            // Customer
            const custPill = document.getElementById('customerPill');
            if (data.customer_name) {
                custPill.textContent = 'Valued Customer: ' + data.customer_name;
                custPill.classList.remove('hidden');
            } else {
                custPill.classList.add('hidden');
            }

            // Checkout Complete Event
            if (data.is_completed) {
                document.getElementById('checkoutSuccessBox').classList.remove('hidden');
                document.getElementById('paidVal').textContent = 'Rs. ' + parseFloat(data.paid_amount || 0).toFixed(2);
                document.getElementById('changeVal').textContent = 'Rs. ' + parseFloat(data.change_amount || 0).toFixed(2);
            } else {
                document.getElementById('checkoutSuccessBox').classList.add('hidden');
            }
        }

        channel.onmessage = function(event) {
            renderCartData(event.data);
        };

        // LocalStorage fallback for separate browser windows/tabs
        window.addEventListener('storage', function(e) {
            if (e.key === 'psi_pos_customer_display') {
                try {
                    const data = JSON.parse(e.newValue);
                    renderCartData(data);
                } catch(err) {}
            }
        });

        // Initial Load check
        try {
            const cached = localStorage.getItem('psi_pos_customer_display');
            if (cached) renderCartData(JSON.parse(cached));
        } catch(err) {}
    </script>
</body>
</html>

