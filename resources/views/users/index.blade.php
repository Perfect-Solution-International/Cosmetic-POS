@extends('layouts.pos')

@section('title', 'User Management & Roles')

@section('content')
<div class="h-full flex flex-col overflow-hidden bg-carbon-950">

    <!-- Header -->
    <div class="bg-carbon-900 border-b border-carbon-800 px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4 shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-brand-600/20 border border-brand-500/30 flex items-center justify-center text-brand-500">
                <i class="fa-solid fa-users-gear text-base"></i>
            </div>
            <div>
                <h2 class="text-base sm:text-lg font-extrabold text-white">Staff & Role Permissions</h2>
                <p class="text-xs text-silver-400">Complete user management, staff credentials, role-based access & branch routing</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button 
                type="button" 
                onclick="openPermissionsGuideModal()"
                class="px-3.5 py-2 rounded-xl bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 text-silver-300 hover:text-white text-xs font-bold transition flex items-center gap-2"
            >
                <i class="fa-solid fa-shield-halved text-brand-400"></i>
                <span>Role Permissions Matrix</span>
            </button>

            <button 
                type="button" 
                onclick="openNewUserModal()"
                class="px-4 py-2 rounded-xl bg-gradient-to-r from-brand-600 to-rose-700 hover:from-brand-500 hover:to-rose-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-brand-600/30 transition flex items-center gap-2 touch-action-btn"
            >
                <i class="fa-solid fa-user-plus"></i>
                <span>Add Staff Member</span>
            </button>
        </div>
    </div>

    <!-- Quick Metrics Bar -->
    <div class="bg-carbon-900/60 border-b border-carbon-800 px-4 sm:px-6 py-3 shrink-0">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
            <div class="p-2.5 rounded-xl bg-carbon-850/80 border border-carbon-750 flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold uppercase text-silver-400">Total Staff</span>
                    <div class="text-base sm:text-lg font-black font-mono text-white">{{ $metrics['total_users'] }}</div>
                </div>
                <div class="w-8 h-8 rounded-lg bg-carbon-800 text-silver-300 flex items-center justify-center">
                    <i class="fa-solid fa-users text-xs"></i>
                </div>
            </div>

            <div class="p-2.5 rounded-xl bg-carbon-850/80 border border-carbon-750 flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold uppercase text-silver-400">Active Accounts</span>
                    <div class="text-base sm:text-lg font-black font-mono text-emerald-400">{{ $metrics['active_users'] }}</div>
                </div>
                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center">
                    <i class="fa-solid fa-user-check text-xs"></i>
                </div>
            </div>

            <div class="p-2.5 rounded-xl bg-carbon-850/80 border border-carbon-750 flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold uppercase text-silver-400">POS Cashiers</span>
                    <div class="text-base sm:text-lg font-black font-mono text-rose-300">{{ $metrics['cashiers_count'] }}</div>
                </div>
                <div class="w-8 h-8 rounded-lg bg-rose-500/10 text-rose-400 flex items-center justify-center">
                    <i class="fa-solid fa-cash-register text-xs"></i>
                </div>
            </div>

            <div class="p-2.5 rounded-xl bg-carbon-850/80 border border-carbon-750 flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold uppercase text-silver-400">Managers & Ops</span>
                    <div class="text-base sm:text-lg font-black font-mono text-blue-300">{{ $metrics['managers_count'] }}</div>
                </div>
                <div class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 flex items-center justify-center">
                    <i class="fa-solid fa-user-tie text-xs"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="bg-carbon-900/40 border-b border-carbon-800 px-4 sm:px-6 py-2.5 shrink-0">
        <form action="{{ route('users.index') }}" method="GET" class="flex flex-wrap items-center gap-2.5 text-xs">
            <div class="flex-1 min-w-[200px] max-w-md">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-silver-400 text-xs"></i>
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}"
                        placeholder="Search by name, username, email, phone..." 
                        class="w-full pl-9 pr-3 py-1.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white placeholder-silver-500 text-xs focus:outline-none focus:border-brand-500"
                    >
                </div>
            </div>

            <div class="w-44">
                <select name="role" onchange="this.form.submit()" class="w-full px-3 py-1.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                    <option value="">All Roles (7 Roles)</option>
                    @foreach($roles as $key => $label)
                        <option value="{{ $key }}" {{ request('role') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-40">
                <select name="branch_id" onchange="this.form.submit()" class="w-full px-3 py-1.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-32">
                <select name="status" onchange="this.form.submit()" class="w-full px-3 py-1.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active Only</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Suspended</option>
                </select>
            </div>

            <button type="submit" class="px-3 py-1.5 rounded-xl bg-carbon-800 hover:bg-carbon-750 text-white text-xs font-semibold border border-carbon-700">
                Filter
            </button>
            @if(request()->hasAny(['search', 'role', 'branch_id', 'status']))
                <a href="{{ route('users.index') }}" class="text-xs text-rose-400 hover:underline">Reset Filters</a>
            @endif
        </form>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4">

        @if(session('status'))
            <div class="p-3.5 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 text-xs font-semibold flex items-center gap-2 shadow-lg">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="p-3.5 rounded-xl bg-rose-500/20 border border-rose-500/40 text-rose-300 text-xs font-semibold flex items-center gap-2 shadow-lg">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="p-3.5 rounded-xl bg-rose-500/20 border border-rose-500/40 text-rose-300 text-xs font-semibold space-y-1">
                <div class="flex items-center gap-2 font-bold text-rose-200">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Please correct the errors below:</span>
                </div>
                <ul class="list-disc list-inside pl-4 space-y-0.5 text-xs text-rose-300">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-carbon-900 border border-carbon-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-silver-300">
                    <thead class="bg-carbon-850 border-b border-carbon-800 text-[11px] font-bold uppercase tracking-wider text-silver-400">
                        <tr>
                            <th class="py-3 px-4">Staff Member</th>
                            <th class="py-3 px-4">Assigned Role</th>
                            <th class="py-3 px-4">Branch Location</th>
                            <th class="py-3 px-4">Contact Phone</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4">Last Activity</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-carbon-800/60 font-mono">
                        @forelse($users as $u)
                            <tr class="hover:bg-carbon-850/60 transition-colors font-sans">
                                <!-- Name & Username -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-brand-600/30 to-carbon-750 border border-brand-500/30 text-brand-400 flex items-center justify-center font-bold text-xs shrink-0 shadow-sm">
                                            {{ substr($u->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-white text-xs flex items-center gap-1.5">
                                                <span>{{ $u->name }}</span>
                                                @if($u->id === auth()->id())
                                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-mono font-bold bg-brand-500/20 text-brand-400 border border-brand-500/30">You</span>
                                                @endif
                                            </div>
                                            <div class="text-[10px] text-silver-500 font-mono">@<span>{{ $u->username }}</span> &bull; {{ $u->email }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Role Badge -->
                                <td class="py-3.5 px-4">
                                    @php
                                        $badgeClass = match($u->role) {
                                            'admin', 'super_admin' => 'bg-brand-600/20 text-brand-400 border-brand-500/30',
                                            'manager', 'branch_manager' => 'bg-blue-500/20 text-blue-300 border-blue-500/30',
                                            'head_office_manager' => 'bg-purple-500/20 text-purple-300 border-purple-500/30',
                                            'cashier' => 'bg-rose-500/20 text-rose-300 border-rose-500/30',
                                            'inventory_staff', 'inventory' => 'bg-amber-500/20 text-amber-300 border-amber-500/30',
                                            'warehouse_manager', 'warehouse' => 'bg-teal-500/20 text-teal-300 border-teal-500/30',
                                            'accountant' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
                                            default => 'bg-carbon-800 text-silver-300 border-carbon-700',
                                        };
                                    @endphp
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border uppercase tracking-wider {{ $badgeClass }}">
                                        {{ $u->role_display_name }}
                                    </span>
                                </td>

                                <!-- Branch -->
                                <td class="py-3.5 px-4 font-sans text-xs">
                                    <div class="text-white font-medium">{{ $u->branch->name ?? 'Head Office (All Branches)' }}</div>
                                    @if($u->branch)
                                        <div class="text-[10px] text-silver-500 font-mono">Branch: {{ $u->branch->code }}</div>
                                    @endif
                                </td>

                                <!-- Phone -->
                                <td class="py-3.5 px-4 font-mono text-xs text-silver-300">
                                    {{ $u->phone ?? '—' }}
                                </td>

                                <!-- Status -->
                                <td class="py-3.5 px-4 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $u->status === 'active' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-400 border border-rose-500/30' }}">
                                        {{ $u->status }}
                                    </span>
                                </td>

                                <!-- Last Login Activity -->
                                <td class="py-3.5 px-4 font-mono text-[11px] text-silver-400">
                                    {{ $u->last_login_at ? $u->last_login_at->format('M d, H:i') : 'No logins yet' }}
                                </td>

                                <!-- Action Buttons -->
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Edit User Button -->
                                        <button 
                                            type="button" 
                                            onclick='openEditUserModal(@json($u))'
                                            class="w-7 h-7 rounded-lg bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 text-silver-300 hover:text-white flex items-center justify-center transition"
                                            title="Edit Staff Member"
                                        >
                                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                                        </button>

                                        <!-- Reset Password Button -->
                                        <button 
                                            type="button" 
                                            onclick="openResetPasswordModal('{{ $u->id }}', '{{ addslashes($u->name) }}', '{{ $u->username }}')"
                                            class="w-7 h-7 rounded-lg bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 text-amber-400 hover:text-amber-300 flex items-center justify-center transition"
                                            title="Reset Password"
                                        >
                                            <i class="fa-solid fa-key text-xs"></i>
                                        </button>

                                        <!-- Toggle Status (Activate / Deactivate) -->
                                        @if($u->id !== auth()->id())
                                            <form action="{{ route('users.toggle-status', $u->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button 
                                                    type="submit" 
                                                    class="w-7 h-7 rounded-lg border transition flex items-center justify-center {{ $u->status === 'active' ? 'bg-carbon-800 border-carbon-700 text-rose-400 hover:bg-rose-500/20' : 'bg-carbon-800 border-carbon-700 text-emerald-400 hover:bg-emerald-500/20' }}"
                                                    title="{{ $u->status === 'active' ? 'Deactivate Account' : 'Activate Account' }}"
                                                >
                                                    <i class="fa-solid {{ $u->status === 'active' ? 'fa-user-slash' : 'fa-user-check' }} text-xs"></i>
                                                </button>
                                            </form>

                                            <!-- Delete User -->
                                            <form action="{{ route('users.destroy', $u->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete staff account {{ $u->name }}?');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button 
                                                    type="submit" 
                                                    class="w-7 h-7 rounded-lg bg-carbon-800 hover:bg-rose-950/60 border border-carbon-700 hover:border-rose-800 text-silver-400 hover:text-rose-400 flex items-center justify-center transition"
                                                    title="Delete Staff Member"
                                                >
                                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-silver-500 font-sans">
                                    <i class="fa-solid fa-users-slash text-3xl mb-2 text-carbon-700"></i>
                                    <p class="text-xs font-semibold text-silver-400">No staff members found matching criteria</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($users->hasPages())
                <div class="p-3 border-t border-carbon-800 bg-carbon-850/50">
                    {{ $users->links() }}
                </div>
            @endif
        </div>

    </div>
</div>

<!-- Modal 1: Add New Staff Member -->
<div id="newUserModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-xl overflow-hidden shadow-2xl p-6 space-y-4 max-h-[92vh] flex flex-col">
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-600/20 border border-brand-500/30 flex items-center justify-center text-brand-500">
                    <i class="fa-solid fa-user-plus text-base"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-white">Add New Staff Member</h3>
                    <p class="text-xs text-silver-400">Create login credentials and assign role permissions</p>
                </div>
            </div>
            <button type="button" onclick="closeNewUserModal()" class="w-8 h-8 rounded-lg bg-carbon-800 text-silver-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('users.store') }}" method="POST" class="space-y-3.5 overflow-y-auto flex-1 pr-1 text-xs">
            @csrf

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Full Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Ruwan Silva" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white focus:outline-none focus:border-brand-500">
                </div>

                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Username *</label>
                    <input type="text" name="username" value="{{ old('username') }}" required placeholder="e.g. ruwan_pos" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white font-mono font-bold focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Email Address *</label>
                    <input type="email" name="email" value="{{ old('email') }}" required placeholder="ruwan@psipos.com" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white focus:outline-none focus:border-brand-500">
                </div>

                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Mobile Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" placeholder="+94 77 123 4567" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white font-mono focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Assigned Role *</label>
                    <select name="role" required class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white focus:outline-none focus:border-brand-500">
                        @foreach($roles as $key => $label)
                            <option value="{{ $key }}" {{ old('role') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Assigned Branch</label>
                    <select name="branch_id" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white focus:outline-none focus:border-brand-500">
                        <option value="">Head Office / All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ old('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ $b->code }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Password *</label>
                    <input type="password" name="password" required minlength="6" placeholder="Min. 6 characters" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white focus:outline-none focus:border-brand-500">
                </div>

                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Status *</label>
                    <select name="status" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white focus:outline-none focus:border-brand-500">
                        <option value="active" {{ old('status') != 'inactive' ? 'selected' : '' }}>Active Account</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive / Suspended</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-brand-600 to-rose-700 hover:from-brand-500 hover:to-rose-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-brand-600/30 transition mt-2">
                Register Staff Account
            </button>
        </form>
    </div>
</div>

<!-- Modal 2: Edit Staff Member -->
<div id="editUserModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-xl overflow-hidden shadow-2xl p-6 space-y-4 max-h-[92vh] flex flex-col">
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-500/20 border border-blue-500/30 flex items-center justify-center text-blue-400">
                    <i class="fa-solid fa-user-pen text-base"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-white">Edit Staff Member</h3>
                    <p class="text-xs text-silver-400">Update account details, role assignment, and branch</p>
                </div>
            </div>
            <button type="button" onclick="closeEditUserModal()" class="w-8 h-8 rounded-lg bg-carbon-800 text-silver-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="editUserForm" method="POST" action="" class="space-y-3.5 overflow-y-auto flex-1 pr-1 text-xs">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Full Name *</label>
                    <input type="text" name="name" id="edit_name" required class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white focus:outline-none focus:border-brand-500">
                </div>

                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Username *</label>
                    <input type="text" name="username" id="edit_username" required class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white font-mono font-bold focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Email Address *</label>
                    <input type="email" name="email" id="edit_email" required class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white focus:outline-none focus:border-brand-500">
                </div>

                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Mobile Phone</label>
                    <input type="text" name="phone" id="edit_phone" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white font-mono focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Assigned Role *</label>
                    <select name="role" id="edit_role" required class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white focus:outline-none focus:border-brand-500">
                        @foreach($roles as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Assigned Branch</label>
                    <select name="branch_id" id="edit_branch_id" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white focus:outline-none focus:border-brand-500">
                        <option value="">Head Office / All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }} ({{ $b->code }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Status *</label>
                    <select name="status" id="edit_status" required class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white focus:outline-none focus:border-brand-500">
                        <option value="active">Active Account</option>
                        <option value="inactive">Inactive / Suspended</option>
                    </select>
                </div>

                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">New Password (Optional)</label>
                    <input type="password" name="password" minlength="6" placeholder="Leave empty to keep unchanged" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-blue-600/30 transition mt-2">
                Update Staff Account
            </button>
        </form>
    </div>
</div>

<!-- Modal 3: Reset Password -->
<div id="resetPasswordModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-md overflow-hidden shadow-2xl p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-amber-400">
                    <i class="fa-solid fa-key text-base"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-white">Reset Staff Password</h3>
                    <p class="text-xs text-silver-400" id="resetUserTargetText">Staff Member</p>
                </div>
            </div>
            <button type="button" onclick="closeResetPasswordModal()" class="w-8 h-8 rounded-lg bg-carbon-800 text-silver-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="resetPasswordForm" method="POST" action="" class="space-y-3.5 text-xs">
            @csrf

            <div>
                <label class="font-bold text-silver-300 uppercase block mb-1">New Password *</label>
                <input type="password" name="new_password" required minlength="6" placeholder="Min. 6 characters" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white focus:outline-none focus:border-brand-500">
            </div>

            <div>
                <label class="font-bold text-silver-300 uppercase block mb-1">Confirm New Password *</label>
                <input type="password" name="new_password_confirmation" required minlength="6" placeholder="Re-type new password" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white focus:outline-none focus:border-brand-500">
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-amber-600/30 transition mt-2">
                Set New Password
            </button>
        </form>
    </div>
</div>

<!-- Modal 4: Role Permissions Matrix Guide -->
<div id="permissionsGuideModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-3xl overflow-hidden shadow-2xl p-6 space-y-4 max-h-[92vh] flex flex-col">
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-600/20 border border-brand-500/30 flex items-center justify-center text-brand-500">
                    <i class="fa-solid fa-shield-halved text-base"></i>
                </div>
                <div>
                    <h3 class="text-sm sm:text-base font-extrabold text-white">System Roles & Permissions Matrix</h3>
                    <p class="text-xs text-silver-400">Overview of operational boundaries across all 7 retail user roles</p>
                </div>
            </div>
            <button type="button" onclick="closePermissionsGuideModal()" class="w-8 h-8 rounded-lg bg-carbon-800 text-silver-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="overflow-y-auto space-y-3 flex-1 pr-1 text-xs">
            @foreach($permissionsGuide as $roleKey => $info)
                <div class="p-3.5 rounded-2xl bg-carbon-850 border border-carbon-800 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-sm text-white flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-brand-500"></span>
                            <span>{{ $info['title'] }}</span>
                        </span>
                        <span class="font-mono text-[10px] text-silver-500 uppercase bg-carbon-800 px-2 py-0.5 rounded border border-carbon-700">
                            {{ $roleKey }}
                        </span>
                    </div>
                    <p class="text-silver-400 text-xs">{{ $info['description'] }}</p>
                    <div class="flex flex-wrap gap-1.5 pt-1">
                        @foreach($info['features'] as $feat)
                            <span class="px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-carbon-800 text-silver-300 border border-carbon-700">
                                &check; {{ $feat }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openNewUserModal() {
        const modal = document.getElementById('newUserModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => {
                const input = modal.querySelector('input[name="name"]');
                if (input) input.focus();
            }, 100);
        }
    }

    function closeNewUserModal() {
        const modal = document.getElementById('newUserModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function openEditUserModal(user) {
        const modal = document.getElementById('editUserModal');
        const form = document.getElementById('editUserForm');
        
        if (modal && form) {
            form.action = `/users/${user.id}`;
            document.getElementById('edit_name').value = user.name || '';
            document.getElementById('edit_username').value = user.username || '';
            document.getElementById('edit_email').value = user.email || '';
            document.getElementById('edit_phone').value = user.phone || '';
            document.getElementById('edit_role').value = user.role || 'cashier';
            document.getElementById('edit_branch_id').value = user.branch_id || '';
            document.getElementById('edit_status').value = user.status || 'active';

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    function closeEditUserModal() {
        const modal = document.getElementById('editUserModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function openResetPasswordModal(userId, userName, username) {
        const modal = document.getElementById('resetPasswordModal');
        const form = document.getElementById('resetPasswordForm');
        const targetText = document.getElementById('resetUserTargetText');

        if (modal && form) {
            form.action = `/users/${userId}/reset-password`;
            if (targetText) {
                targetText.textContent = `${userName} (@${username})`;
            }
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    function closeResetPasswordModal() {
        const modal = document.getElementById('resetPasswordModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function openPermissionsGuideModal() {
        const modal = document.getElementById('permissionsGuideModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    function closePermissionsGuideModal() {
        const modal = document.getElementById('permissionsGuideModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeNewUserModal();
            closeEditUserModal();
            closeResetPasswordModal();
            closePermissionsGuideModal();
        }
    });
</script>
@endsection
