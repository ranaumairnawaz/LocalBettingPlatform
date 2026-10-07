@extends('admin.layout')
@section('content')
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-white italic tracking-wider mb-2 flex items-center gap-2">
                <span class="bg-neon-cyan/20 w-2.5 h-8 rounded-full inline-block shadow-[0_0_15px_rgba(0,242,254,0.5)]"></span>
                DEPOSIT METHODS
            </h1>
            <p class="text-slate-400 text-sm">Manage payment gateways, accounts, and receiving limits.</p>
        </div>

        <!-- Add Method Form -->
        <form action="{{ route('admin.deposit_methods.store') }}" method="POST"
            class="flex gap-2 bg-[#121623]/60 p-2 rounded-[16px] border border-white/10 backdrop-blur-md">
            @csrf
            <div class="relative flex-1">
                <i data-lucide="plus-circle" class="w-4 h-4 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="name"
                    class="w-full bg-[#090a0f] border border-white/5 rounded-xl py-2 pl-9 pr-3 text-sm focus:outline-none focus:ring-1 focus:ring-neon-cyan focus:border-neon-cyan transition-all text-slate-200 font-semibold"
                    placeholder="New Method (e.g. JazzCash)" required>
            </div>
            <button
                class="bg-neon-cyan hover:bg-neon-cyan/95 text-black px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all whitespace-nowrap shadow-[0_0_15px_rgba(0,242,254,0.25)] cursor-pointer">
                Add Method
            </button>
        </form>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        @foreach($methods as $method)
            <div class="bg-[#121623]/60 rounded-[20px] border border-white/10 shadow-sm flex flex-col overflow-hidden backdrop-blur-md">
                <div class="bg-[#090a0f]/50 border-b border-white/5 p-5 flex justify-between items-center">
                    <h3 class="font-black text-white italic text-lg flex items-center gap-2 uppercase tracking-wide">
                        <i data-lucide="wallet" class="w-5 h-5 text-neon-cyan animate-pulse"></i>
                        {{ $method->name }}
                    </h3>
                    <span
                        class="bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider shadow-[0_0_10px_rgba(16,185,129,0.1)]">
                        Active
                    </span>
                </div>
                <div class="p-6 flex-1 flex flex-col">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Attached Accounts</h4>
                        <span
                            class="bg-white/5 border border-white/5 text-slate-300 px-2 py-0.5 rounded text-[10px] font-bold">
                            {{ $method->accounts->count() }} Accounts
                        </span>
                    </div>

                    <div class="space-y-3 mb-6 flex-1">
                        @forelse($method->accounts as $account)
                            <details
                                class="group bg-[#090a0f]/40 rounded-xl border border-white/5 overflow-hidden hover:border-white/10 transition-colors [&_summary::-webkit-details-marker]:hidden">
                                <summary
                                    class="flex justify-between items-center cursor-pointer p-4 select-none hover:bg-white/5 transition-colors">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-white">Account #{{ $account->id }}</span>
                                        <span class="text-xs text-slate-400 mt-1 font-medium">
                                            Limit: <strong class="text-neon-cyan font-mono">Rs. {{ number_format($account->receiving_limit) }}</strong>
                                        </span>
                                    </div>
                                    <span class="transition group-open:rotate-180">
                                        <i data-lucide="chevron-down" class="w-5 h-5 text-slate-500"></i>
                                    </span>
                                </summary>
                                <div class="p-4 border-t border-white/5 bg-[#090a0f]/20">
                                    <ul class="space-y-2 mb-4">
                                        @forelse($account->values as $val)
                                            <li
                                                class="flex justify-between items-center text-xs p-2.5 rounded-lg bg-[#090a0f]/60 border border-white/5">
                                                <span class="text-slate-400 font-bold uppercase tracking-wider text-[9px]">{{ $val->key }}</span>
                                                <strong class="text-white font-mono">{{ $val->value }}</strong>
                                            </li>
                                        @empty
                                            <li
                                                class="text-xs text-slate-500 italic text-center py-3 bg-[#090a0f]/30 rounded-lg border border-dashed border-white/5">
                                                No details added yet.
                                            </li>
                                        @endforelse
                                    </ul>

                                    <!-- Add Value Form -->
                                    <form action="{{ route('admin.account_values.store', $account->id) }}" method="POST"
                                        class="flex gap-2 bg-[#090a0f]/80 p-2 rounded-xl border border-white/5">
                                        @csrf
                                        <input type="text" name="key"
                                            class="w-1/3 bg-[#090a0f] border border-white/10 rounded-lg py-1.5 px-3 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-neon-cyan focus:border-neon-cyan font-semibold"
                                            placeholder="Key (e.g. Title)" required>
                                        <input type="text" name="value"
                                            class="w-full bg-[#090a0f] border border-white/10 rounded-lg py-1.5 px-3 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-neon-cyan focus:border-neon-cyan font-semibold"
                                            placeholder="Value (e.g. John Doe)" required>
                                        <button
                                            class="bg-white/5 hover:bg-white/10 border border-white/10 text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer">
                                            <i data-lucide="plus" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </details>
                        @empty
                            <div
                                class="bg-amber-500/5 border border-amber-500/10 text-amber-400 p-6 rounded-xl text-xs text-center flex flex-col items-center justify-center h-28">
                                <i data-lucide="alert-triangle" class="w-6 h-6 mb-2 opacity-50 text-amber-400"></i>
                                No accounts setup for this method.
                            </div>
                        @endforelse
                    </div>

                    <!-- Add Account Form -->
                    <div class="mt-auto pt-4 border-t border-white/5">
                        <form action="{{ route('admin.method_accounts.store', $method->id) }}" method="POST" class="flex gap-2">
                            @csrf
                            <div class="relative flex-1">
                                <span
                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-neon-cyan text-xs font-black font-mono">Rs.</span>
                                <input type="number" name="receiving_limit"
                                    class="w-full bg-[#090a0f] border border-white/10 rounded-xl py-2.5 pl-9 pr-3 text-sm focus:outline-none focus:ring-1 focus:ring-neon-cyan focus:border-neon-cyan transition-all text-slate-200 font-mono font-bold"
                                    placeholder="Account Limit (e.g. 50000)" required>
                            </div>
                            <button
                                class="bg-neon-pink hover:bg-neon-pink/90 text-white border border-neon-pink/30 px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all whitespace-nowrap flex items-center gap-2 shadow-[0_0_15px_rgba(255,0,127,0.25)] cursor-pointer">
                                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                                New Account
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach

        @if($methods->count() == 0)
            <div
                class="col-span-1 xl:col-span-2 bg-[#121623]/60 rounded-[20px] border border-white/10 shadow-sm p-12 text-center flex flex-col items-center justify-center backdrop-blur-md">
                <div class="bg-[#090a0f] w-20 h-20 rounded-2xl border border-white/10 flex items-center justify-center mb-4 text-slate-600">
                    <i data-lucide="wallet" class="w-10 h-10"></i>
                </div>
                <h4 class="text-xl font-bold text-white mb-2">No Deposit Methods Configured</h4>
                <p class="text-slate-400 text-sm max-w-md">Get started by adding your first payment gateway using the form in the top right corner.</p>
            </div>
        @endif
    </div>
@endsection