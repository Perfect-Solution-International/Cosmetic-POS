<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Psi POS">
    <meta name="theme-color" content="#0B0D13">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Operator Terminal Login | Psi One Global POS</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">

    <!-- Google Fonts (Non-blocking Asynchronous Load with System UI Fallback) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap">
    </noscript>

    <!-- Local Precompiled High-Speed Tailwind CSS, FontAwesome 6 & Utilities -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex flex-col justify-between text-slate-100 antialiased selection:bg-brand-600 selection:text-white psi-grid-bg">

    <!-- Mobile & Tablet App Navigation Header -->
    <header class="w-full border-b border-carbon-800 bg-carbon-900/90 backdrop-blur-xl sticky top-0 z-40 safe-top px-4 sm:px-6 py-2.5 sm:py-3.5">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            
            <!-- App Icon & Title -->
            <div class="flex items-center gap-2.5 sm:gap-3.5">
                <div class="relative w-9 h-9 sm:w-11 sm:h-11 rounded-xl sm:rounded-2xl bg-gradient-to-b from-carbon-800 to-carbon-900 p-1 psi-silver-border shadow-md flex items-center justify-center shrink-0">
                    <img src="{{ asset('images/psi-logo.png') }}" alt="PSI" class="w-full h-full object-contain filter drop-shadow(0 2px 4px rgba(229,26,46,0.35))">
                </div>
                <div>
                    <h1 class="font-extrabold text-sm sm:text-lg tracking-tight text-white flex items-center gap-1.5 leading-none">
                        <span class="tracking-wide">PSI ONE</span>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-500 via-rose-400 to-silver-200">GLOBAL POS</span>
                    </h1>
                    <p class="text-[9px] sm:text-[11px] text-silver-400 font-semibold tracking-wider flex items-center gap-1.5 mt-0.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-brand-500 inline-block"></span>
                        <span class="hidden xs:inline">ENTERPRISE</span> TERMINAL
                    </p>
                </div>
            </div>

            <!-- Terminal Status Indicators -->
            <div class="flex items-center gap-2 sm:gap-3">
                <!-- MySQL Live Pill -->
                <div class="flex items-center gap-1.5 px-2 sm:px-2.5 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 font-mono text-[10px] sm:text-xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span class="hidden sm:inline">MySQL</span> Online
                </div>

                <!-- Station Pill -->
                <div class="flex items-center gap-1.5 px-2 sm:px-2.5 py-1 rounded-full bg-carbon-800 border border-carbon-700 text-silver-300 font-mono text-[10px] sm:text-xs">
                    <i class="fa-solid fa-tablet-screen-button text-brand-500 text-[10px] sm:text-xs"></i>
                    <span>Reg #01</span>
                </div>

                <!-- Digital Clock -->
                <div class="hidden md:block text-silver-300 font-mono text-xs px-2.5 py-1 rounded-lg bg-carbon-900/90 border border-carbon-800" id="clock">
                    --:--:--
                </div>
            </div>

        </div>
    </header>

    <!-- Main Viewport: Responsive App Container (Mobile & Tablet App look, Desktop Split) -->
    <main class="flex-1 flex items-center justify-center p-3 xs:p-4 sm:p-6 lg:p-8">
        <div class="w-full max-w-lg md:max-w-2xl lg:max-w-5xl rounded-3xl psi-silver-border bg-carbon-900/90 backdrop-blur-2xl shadow-2xl overflow-hidden psi-red-glow grid grid-cols-1 lg:grid-cols-12 transition-all">
            
            <!-- Left Panel: Tablet Landscape / Desktop POS Showcase (Hidden on narrow mobile screens for sleek app feel) -->
            <div class="hidden lg:flex lg:col-span-5 bg-gradient-to-br from-carbon-900 via-carbon-950 to-black p-8 sm:p-10 flex-col justify-between border-b lg:border-b-0 lg:border-r border-carbon-800 relative overflow-hidden">
                
                <!-- Ambient Glow Orbs -->
                <div class="absolute -top-20 -left-20 w-64 h-64 bg-brand-600/15 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-20 -right-20 w-64 h-64 bg-silver-400/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full metallic-badge text-silver-200 text-xs font-semibold mb-6">
                        <img src="{{ asset('images/psi-logo.png') }}" alt="PSI" class="w-4 h-4 object-contain">
                        <span>Terminal Edition &bull; Touch & Keypad</span>
                    </div>

                    <h2 class="text-2xl xl:text-3xl font-extrabold text-white tracking-tight leading-tight">
                        Engineered for <br>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-500 via-rose-400 to-silver-100">
                            Mobile & Tablet Registers
                        </span>
                    </h2>

                    <p class="mt-4 text-sm text-silver-400 leading-relaxed">
                        High-speed checkout on iPads, Android tablets, touch terminals, and mobile counter devices with instant MySQL synchronization.
                    </p>

                    <!-- Feature List -->
                    <div class="mt-7 space-y-3.5">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-brand-600/20 border border-brand-500/30 flex items-center justify-center shrink-0 text-brand-400">
                                <i class="fa-solid fa-mobile-screen-button text-xs"></i>
                            </div>
                            <span class="text-xs font-semibold text-silver-200">Native Tablet & Phone POS Touch UI</span>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-silver-500/20 border border-silver-400/20 flex items-center justify-center shrink-0 text-silver-300">
                                <i class="fa-solid fa-calculator text-xs"></i>
                            </div>
                            <span class="text-xs font-semibold text-silver-200">Virtual Touch PIN Pad for Cashiers</span>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-brand-600/20 border border-brand-500/30 flex items-center justify-center shrink-0 text-brand-400">
                                <i class="fa-solid fa-barcode text-xs"></i>
                            </div>
                            <span class="text-xs font-semibold text-silver-200">Continuous Camera & Hardware Barcode Scanning</span>
                        </div>
                    </div>
                </div>

                <div class="relative z-10 pt-6 border-t border-carbon-800/80 flex items-center justify-between text-xs text-silver-400 font-mono">
                    <span class="flex items-center gap-1.5">
                        <i class="fa-solid fa-database text-brand-500"></i>
                        <span>psi_one_global_pos</span>
                    </span>
                    <span class="px-2 py-0.5 rounded bg-carbon-800 text-[11px] text-silver-300">v1.0 Touch POS</span>
                </div>
            </div>

            <!-- Right Panel: App Authentication Sheet (Optimized for Mobile Touch & Tablet Terminal) -->
            <div class="lg:col-span-7 p-4 xs:p-6 sm:p-8 md:p-10 flex flex-col justify-center bg-carbon-900/95 relative">
                <div class="max-w-md w-full mx-auto">

                    <!-- Mobile-Only App Brand Card Header (shown on mobile & tablet) -->
                    <div class="flex lg:hidden items-center justify-between mb-5 pb-4 border-b border-carbon-800">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-carbon-800 p-1.5 psi-silver-border flex items-center justify-center shadow-lg">
                                <img src="{{ asset('images/psi-logo.png') }}" alt="PSI" class="w-full h-full object-contain filter drop-shadow(0 2px 4px rgba(229,26,46,0.4))">
                            </div>
                            <div>
                                <h3 class="text-base font-extrabold text-white leading-tight">Psi One Global POS</h3>
                                <p class="text-xs text-silver-400">Operator Login &bull; Touch & PIN</p>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 rounded-full bg-carbon-800 border border-carbon-700 text-[11px] font-mono text-silver-300">
                            App Mode
                        </span>
                    </div>

                    <!-- Desktop & Tablet Landscape Form Header -->
                    <div class="hidden lg:flex items-center justify-between mb-5 pb-4 border-b border-carbon-800">
                        <div>
                            <h3 class="text-2xl font-extrabold text-white tracking-tight">Operator Login</h3>
                            <p class="text-xs text-silver-400 mt-0.5">Sign in to unlock terminal registers</p>
                        </div>
                        <span class="text-[11px] px-2.5 py-1 rounded-full bg-carbon-800 border border-silver-400/20 text-silver-300 font-mono">
                            Terminal #01
                        </span>
                    </div>

                    <!-- App Mode Segmented Switcher (Keyboard Login vs Touch PIN Pad) -->
                    <div class="mb-5 p-1 rounded-2xl bg-carbon-800/90 border border-carbon-700/80 flex items-center gap-1 shadow-inner">
                        <button
                            type="button"
                            id="tabKeyboard"
                            onclick="switchAuthMode('keyboard')"
                            class="flex-1 py-2 sm:py-2.5 px-3 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all bg-gradient-to-r from-brand-600 to-rose-700 text-white shadow-md"
                        >
                            <i class="fa-solid fa-keyboard text-xs"></i>
                            <span>Standard Login</span>
                        </button>
                        <button
                            type="button"
                            id="tabPinPad"
                            onclick="switchAuthMode('pinpad')"
                            class="flex-1 py-2 sm:py-2.5 px-3 rounded-xl text-xs sm:text-sm font-semibold text-silver-400 hover:text-white flex items-center justify-center gap-2 transition-all"
                        >
                            <i class="fa-solid fa-calculator text-xs"></i>
                            <span>Touch PIN Pad</span>
                        </button>
                    </div>

                    <!-- Flash & Validation Feedback -->
                    @if (session('status'))
                        <div class="mb-4 p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs sm:text-sm flex items-start gap-2.5">
                            <i class="fa-solid fa-circle-check text-emerald-400 mt-0.5 shrink-0"></i>
                            <div>{{ session('status') }}</div>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-4 p-3.5 rounded-xl bg-brand-950/70 border border-brand-600/40 text-brand-200 text-xs sm:text-sm flex items-start gap-2.5">
                            <i class="fa-solid fa-triangle-exclamation text-brand-400 mt-0.5 shrink-0"></i>
                            <div class="space-y-1">
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Core Auth Form (Works for both standard and PIN mode) -->
                    <form method="POST" action="{{ route('login.post') }}" id="loginForm" class="space-y-4">
                        @csrf

                        <!-- Operator ID / Username input -->
                        <div>
                            <label for="login" class="block text-[11px] font-bold uppercase tracking-wider text-silver-300 mb-1.5">
                                Operator ID or Email
                            </label>
                            <div class="relative rounded-xl shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-silver-400">
                                    <i class="fa-solid fa-user-tag text-xs sm:text-sm"></i>
                                </div>
                                <input
                                    type="text"
                                    id="login"
                                    name="login"
                                    value="{{ old('login', 'admin') }}"
                                    required
                                    autocomplete="username"
                                    placeholder="admin or cashier"
                                    class="w-full pl-10 pr-4 py-3 sm:py-3.5 bg-carbon-800/90 border border-carbon-700 hover:border-silver-500/50 rounded-xl text-white placeholder-silver-500 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all font-medium"
                                >
                            </div>
                        </div>

                        <!-- Password / PIN Input -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="password" class="block text-[11px] font-bold uppercase tracking-wider text-silver-300">
                                    PIN / Password
                                </label>
                                <span class="text-[10px] text-silver-400 font-mono">Case-sensitive</span>
                            </div>
                            <div class="relative rounded-xl shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-silver-400">
                                    <i class="fa-solid fa-key text-xs sm:text-sm"></i>
                                </div>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    value="admin123"
                                    required
                                    autocomplete="current-password"
                                    placeholder="Enter secret PIN or password"
                                    class="w-full pl-10 pr-11 py-3 sm:py-3.5 bg-carbon-800/90 border border-carbon-700 hover:border-silver-500/50 rounded-xl text-white placeholder-silver-500 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all font-mono"
                                >
                                <button
                                    type="button"
                                    id="togglePassword"
                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-silver-400 hover:text-white transition-colors focus:outline-none touch-pad-btn"
                                    aria-label="Toggle password visibility"
                                >
                                    <i class="fa-solid fa-eye text-sm" id="toggleIcon"></i>
                                </button>
                            </div>
                        </div>

                        <!-- VIRTUAL TOUCHSCREEN NUMERIC PIN PAD (For Tablet POS & Mobile Terminal) -->
                        <div id="virtualPinPad" class="hidden pt-2">
                            <div class="p-3 sm:p-4 rounded-2xl bg-carbon-800/80 border border-carbon-700/80 shadow-lg">
                                <div class="flex items-center justify-between text-xs text-silver-400 mb-2 px-1">
                                    <span><i class="fa-solid fa-fingerprint text-brand-500 mr-1.5"></i>Touch Keypad</span>
                                    <span class="text-[11px] font-mono text-silver-400">Taps enter into PIN field</span>
                                </div>
                                <div class="grid grid-cols-3 gap-2 sm:gap-2.5">
                                    <button type="button" onclick="pressKey('1')" class="touch-pad-btn py-3 sm:py-3.5 rounded-xl bg-carbon-900 hover:bg-carbon-750 border border-carbon-700 text-lg sm:text-xl font-bold font-mono text-white transition-all shadow-sm">1</button>
                                    <button type="button" onclick="pressKey('2')" class="touch-pad-btn py-3 sm:py-3.5 rounded-xl bg-carbon-900 hover:bg-carbon-750 border border-carbon-700 text-lg sm:text-xl font-bold font-mono text-white transition-all shadow-sm">2</button>
                                    <button type="button" onclick="pressKey('3')" class="touch-pad-btn py-3 sm:py-3.5 rounded-xl bg-carbon-900 hover:bg-carbon-750 border border-carbon-700 text-lg sm:text-xl font-bold font-mono text-white transition-all shadow-sm">3</button>
                                    <button type="button" onclick="pressKey('4')" class="touch-pad-btn py-3 sm:py-3.5 rounded-xl bg-carbon-900 hover:bg-carbon-750 border border-carbon-700 text-lg sm:text-xl font-bold font-mono text-white transition-all shadow-sm">4</button>
                                    <button type="button" onclick="pressKey('5')" class="touch-pad-btn py-3 sm:py-3.5 rounded-xl bg-carbon-900 hover:bg-carbon-750 border border-carbon-700 text-lg sm:text-xl font-bold font-mono text-white transition-all shadow-sm">5</button>
                                    <button type="button" onclick="pressKey('6')" class="touch-pad-btn py-3 sm:py-3.5 rounded-xl bg-carbon-900 hover:bg-carbon-750 border border-carbon-700 text-lg sm:text-xl font-bold font-mono text-white transition-all shadow-sm">6</button>
                                    <button type="button" onclick="pressKey('7')" class="touch-pad-btn py-3 sm:py-3.5 rounded-xl bg-carbon-900 hover:bg-carbon-750 border border-carbon-700 text-lg sm:text-xl font-bold font-mono text-white transition-all shadow-sm">7</button>
                                    <button type="button" onclick="pressKey('8')" class="touch-pad-btn py-3 sm:py-3.5 rounded-xl bg-carbon-900 hover:bg-carbon-750 border border-carbon-700 text-lg sm:text-xl font-bold font-mono text-white transition-all shadow-sm">8</button>
                                    <button type="button" onclick="pressKey('9')" class="touch-pad-btn py-3 sm:py-3.5 rounded-xl bg-carbon-900 hover:bg-carbon-750 border border-carbon-700 text-lg sm:text-xl font-bold font-mono text-white transition-all shadow-sm">9</button>
                                    <button type="button" onclick="pressKey('CLEAR')" class="touch-pad-btn py-3 sm:py-3.5 rounded-xl bg-carbon-900/80 hover:bg-rose-950/50 border border-carbon-700 text-xs sm:text-sm font-bold text-rose-400 transition-all uppercase">CLR</button>
                                    <button type="button" onclick="pressKey('0')" class="touch-pad-btn py-3 sm:py-3.5 rounded-xl bg-carbon-900 hover:bg-carbon-750 border border-carbon-700 text-lg sm:text-xl font-bold font-mono text-white transition-all shadow-sm">0</button>
                                    <button type="button" onclick="pressKey('BACK')" class="touch-pad-btn py-3 sm:py-3.5 rounded-xl bg-carbon-900/80 hover:bg-carbon-750 border border-carbon-700 text-sm font-bold text-silver-300 transition-all">
                                        <i class="fa-solid fa-delete-left"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Remember & Status -->
                        <div class="flex items-center justify-between text-xs pt-1">
                            <label class="flex items-center gap-2 cursor-pointer select-none text-silver-300 hover:text-white">
                                <input
                                    type="checkbox"
                                    name="remember"
                                    id="remember"
                                    class="w-4 h-4 rounded border-carbon-700 bg-carbon-800 text-brand-600 focus:ring-brand-500 focus:ring-offset-carbon-900"
                                    checked
                                >
                                <span class="text-[12px]">Stay signed in on this tablet</span>
                            </label>

                            <span class="text-silver-400 hover:text-silver-200 text-[11px] flex items-center gap-1">
                                <i class="fa-solid fa-shield-halved text-brand-500"></i>
                                <span>Secured</span>
                            </span>
                        </div>

                        <!-- Primary Sign In Button (Touch Friendly 52px Target) -->
                        <div class="pt-2">
                            <button
                                type="submit"
                                id="submitBtn"
                                class="w-full min-h-[50px] sm:min-h-[54px] py-3.5 px-4 rounded-xl bg-gradient-to-r from-brand-600 via-brand-700 to-rose-700 hover:from-brand-500 hover:to-rose-600 active:scale-[0.98] text-white font-bold text-sm sm:text-base tracking-wide shadow-xl shadow-brand-600/35 flex items-center justify-center gap-3 border border-brand-500/40 transition-all focus:outline-none focus:ring-2 focus:ring-brand-500 touch-pad-btn"
                            >
                                <i class="fa-solid fa-right-to-bracket text-base"></i>
                                <span>Sign In to Register</span>
                            </button>
                        </div>
                    </form>

                    <!-- Fast Operator Quick-Fill Demo Chips (Finger Friendly) -->
                    <div class="mt-6 pt-5 border-t border-carbon-800">
                        <div class="flex items-center justify-between mb-2.5">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-silver-400 flex items-center gap-1.5">
                                <i class="fa-solid fa-bolt text-brand-400"></i>
                                <span>Quick Role Switch</span>
                            </span>
                            <span class="text-[10px] text-silver-500">1-Tap fill</span>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <!-- Admin -->
                            <button
                                type="button"
                                onclick="fillCredentials('admin', 'admin123')"
                                class="touch-pad-btn py-2 px-2 rounded-xl bg-carbon-800/90 hover:bg-carbon-750 border border-carbon-700 hover:border-brand-500/60 transition-all text-center group"
                            >
                                <div class="text-xs font-bold text-brand-400 group-hover:text-white flex items-center justify-center gap-1">
                                    <i class="fa-solid fa-shield-halved text-[10px]"></i>
                                    <span>Admin</span>
                                </div>
                                <div class="text-[9px] text-silver-400 font-mono mt-0.5 truncate">admin123</div>
                            </button>

                            <!-- Manager -->
                            <button
                                type="button"
                                onclick="fillCredentials('manager', 'manager123')"
                                class="touch-pad-btn py-2 px-2 rounded-xl bg-carbon-800/90 hover:bg-carbon-750 border border-carbon-700 hover:border-silver-400/60 transition-all text-center group"
                            >
                                <div class="text-xs font-bold text-silver-200 group-hover:text-white flex items-center justify-center gap-1">
                                    <i class="fa-solid fa-briefcase text-[10px]"></i>
                                    <span>Manager</span>
                                </div>
                                <div class="text-[9px] text-silver-400 font-mono mt-0.5 truncate">manager123</div>
                            </button>

                            <!-- Cashier -->
                            <button
                                type="button"
                                onclick="fillCredentials('cashier', 'cashier123')"
                                class="touch-pad-btn py-2 px-2 rounded-xl bg-carbon-800/90 hover:bg-carbon-750 border border-carbon-700 hover:border-rose-400/60 transition-all text-center group"
                            >
                                <div class="text-xs font-bold text-rose-300 group-hover:text-white flex items-center justify-center gap-1">
                                    <i class="fa-solid fa-cash-register text-[10px]"></i>
                                    <span>Cashier</span>
                                </div>
                                <div class="text-[9px] text-silver-400 font-mono mt-0.5 truncate">cashier123</div>
                            </button>

                            <!-- Head Office -->
                            <button
                                type="button"
                                onclick="fillCredentials('headoffice', 'head123')"
                                class="touch-pad-btn py-2 px-2 rounded-xl bg-carbon-800/90 hover:bg-carbon-750 border border-carbon-700 hover:border-sky-400/60 transition-all text-center group"
                            >
                                <div class="text-xs font-bold text-sky-400 group-hover:text-white flex items-center justify-center gap-1">
                                    <i class="fa-solid fa-building text-[10px]"></i>
                                    <span>Head Office</span>
                                </div>
                                <div class="text-[9px] text-silver-400 font-mono mt-0.5 truncate">head123</div>
                            </button>

                            <!-- Inventory Staff -->
                            <button
                                type="button"
                                onclick="fillCredentials('inventory', 'inv123')"
                                class="touch-pad-btn py-2 px-2 rounded-xl bg-carbon-800/90 hover:bg-carbon-750 border border-carbon-700 hover:border-emerald-400/60 transition-all text-center group"
                            >
                                <div class="text-xs font-bold text-emerald-400 group-hover:text-white flex items-center justify-center gap-1">
                                    <i class="fa-solid fa-boxes-stacked text-[10px]"></i>
                                    <span>Inventory</span>
                                </div>
                                <div class="text-[9px] text-silver-400 font-mono mt-0.5 truncate">inv123</div>
                            </button>

                            <!-- Warehouse Manager -->
                            <button
                                type="button"
                                onclick="fillCredentials('warehouse', 'wh123')"
                                class="touch-pad-btn py-2 px-2 rounded-xl bg-carbon-800/90 hover:bg-carbon-750 border border-carbon-700 hover:border-amber-400/60 transition-all text-center group"
                            >
                                <div class="text-xs font-bold text-amber-400 group-hover:text-white flex items-center justify-center gap-1">
                                    <i class="fa-solid fa-warehouse text-[10px]"></i>
                                    <span>Warehouse</span>
                                </div>
                                <div class="text-[9px] text-silver-400 font-mono mt-0.5 truncate">wh123</div>
                            </button>

                            <!-- Accountant -->
                            <button
                                type="button"
                                onclick="fillCredentials('accountant', 'acc123')"
                                class="touch-pad-btn py-2 px-2 rounded-xl bg-carbon-800/90 hover:bg-carbon-750 border border-carbon-700 hover:border-indigo-400/60 transition-all text-center group sm:col-span-2"
                            >
                                <div class="text-xs font-bold text-indigo-400 group-hover:text-white flex items-center justify-center gap-1">
                                    <i class="fa-solid fa-calculator text-[10px]"></i>
                                    <span>Accountant</span>
                                </div>
                                <div class="text-[9px] text-silver-400 font-mono mt-0.5 truncate">acc123</div>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </main>

    <!-- App Bottom Bar with Safe Area -->
    <footer class="w-full border-t border-carbon-800 bg-carbon-900/70 backdrop-blur px-4 sm:px-6 py-2.5 sm:py-3 safe-bottom text-center text-xs text-silver-400">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-1.5">
            <div class="flex items-center gap-2">
                <img src="{{ asset('images/psi-logo.png') }}" alt="PSI" class="w-3.5 h-3.5 object-contain">
                <span>&copy; {{ date('Y') }} <strong class="text-white">Psi One Global POS</strong></span>
            </div>
            <div class="flex items-center gap-3 text-[11px] font-mono text-silver-400">
                <span class="flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>Ready for Checkout</span>
                </span>
                <span>•</span>
                <span>Mobile & Tablet POS</span>
            </div>
        </div>
    </footer>

    <!-- Interactive Mobile App & Touch Scripts -->
    <script>
        // Password Visibility Toggle
        const toggleBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');

        toggleBtn.addEventListener('click', function () {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            toggleIcon.classList.toggle('fa-eye', !isPassword);
            toggleIcon.classList.toggle('fa-eye-slash', isPassword);
        });

        // Switch between Standard Login and Touch PIN Pad
        function switchAuthMode(mode) {
            const tabKeyboard = document.getElementById('tabKeyboard');
            const tabPinPad = document.getElementById('tabPinPad');
            const virtualPinPad = document.getElementById('virtualPinPad');

            if (mode === 'pinpad') {
                tabPinPad.className = "flex-1 py-2 sm:py-2.5 px-3 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all bg-gradient-to-r from-brand-600 to-rose-700 text-white shadow-md";
                tabKeyboard.className = "flex-1 py-2 sm:py-2.5 px-3 rounded-xl text-xs sm:text-sm font-semibold text-silver-400 hover:text-white flex items-center justify-center gap-2 transition-all";
                virtualPinPad.classList.remove('hidden');
            } else {
                tabKeyboard.className = "flex-1 py-2 sm:py-2.5 px-3 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all bg-gradient-to-r from-brand-600 to-rose-700 text-white shadow-md";
                tabPinPad.className = "flex-1 py-2 sm:py-2.5 px-3 rounded-xl text-xs sm:text-sm font-semibold text-silver-400 hover:text-white flex items-center justify-center gap-2 transition-all";
                virtualPinPad.classList.add('hidden');
            }
        }

        // Virtual Touch Keypad Engine
        function pressKey(key) {
            const passEl = document.getElementById('password');
            if (key === 'CLEAR') {
                passEl.value = '';
            } else if (key === 'BACK') {
                passEl.value = passEl.value.slice(0, -1);
            } else {
                passEl.value += key;
            }
            passEl.focus();

            // Provide visual click feedback on input
            passEl.classList.add('ring-2', 'ring-brand-500');
            setTimeout(() => passEl.classList.remove('ring-2', 'ring-brand-500'), 150);
        }

        // Fast Preset Credentials
        function fillCredentials(login, password) {
            document.getElementById('login').value = login;
            document.getElementById('password').value = password;
            document.getElementById('password').setAttribute('type', 'password');
            toggleIcon.className = 'fa-solid fa-eye text-sm';
            
            const form = document.getElementById('loginForm');
            form.classList.add('ring-2', 'ring-brand-500/50', 'rounded-2xl', 'p-2', 'transition-all');
            setTimeout(() => {
                form.classList.remove('ring-2', 'ring-brand-500/50', 'rounded-2xl', 'p-2');
            }, 500);
        }

        // Live Clock
        function updateClock() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            const clockEl = document.getElementById('clock');
            if (clockEl) {
                clockEl.textContent = timeStr;
            }
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Submit Button Loading state
        document.getElementById('loginForm').addEventListener('submit', function () {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin text-base"></i><span>Verifying Operator...</span>`;
            btn.classList.add('opacity-80', 'cursor-not-allowed');
        });
    </script>
</body>
</html>
