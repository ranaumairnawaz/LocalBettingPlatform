@extends('admin.layout')
@section('content')
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-black text-white italic tracking-wider mb-2 flex items-center gap-2">
                <span class="bg-neon-pink/20 w-2.5 h-8 rounded-full inline-block shadow-[0_0_15px_rgba(255,0,127,0.5)]"></span>
                PENDING WITHDRAWALS
            </h1>
            <p class="text-slate-400 text-sm">Review, copy credentials, and approve player payouts.</p>
        </div>
        
        <!-- Search and Filters Form -->
        <form action="{{ route('admin.transactions.pending_withdrawals') }}" method="GET" class="flex gap-2 w-full md:w-auto bg-[#121623]/60 p-3 rounded-[20px] border border-white/10 backdrop-blur-md">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search player, method..." class="bg-[#090a0f] border border-white/5 rounded-xl py-2 px-3 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-neon-pink min-w-[200px] flex-1">
            <button type="submit" class="bg-neon-pink hover:bg-neon-pink/95 text-white px-5 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all shadow-[0_0_10px_rgba(255,0,127,0.2)] cursor-pointer">Filter</button>
            <a href="{{ route('admin.transactions.pending_withdrawals') }}" class="bg-white/5 hover:bg-white/10 text-slate-300 px-5 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex items-center justify-center cursor-pointer">Reset</a>
        </form>
    </div>

    <div class="bg-[#121623]/60 rounded-[20px] border border-white/10 shadow-sm overflow-hidden backdrop-blur-md">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-[#090a0f]/60 border-b border-white/10 text-slate-400">
                        <th class="py-4 px-6 text-[10px] font-black uppercase tracking-widest text-slate-400">User</th>
                        <th class="py-4 px-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Amount</th>
                        <th class="py-4 px-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Method</th>
                        <th class="py-4 px-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Bound Credentials (Click to Copy)</th>
                        <th class="py-4 px-6 text-[10px] font-black uppercase tracking-widest text-slate-400 font-mono">Date</th>
                        <th class="py-4 px-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($transactions as $tx)
                        <tr class="hover:bg-white/5 transition-colors border-b border-white/5">
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($tx->user->username) }}&background=121623&color=ff007f"
                                        class="w-8 h-8 rounded-full border border-white/10">
                                    <span class="font-black text-white italic uppercase tracking-wider text-xs">{{ $tx->user->username }}</span>
                                </div>
                            </td>
                            <td class="py-4 px-6 font-black text-neon-pink font-mono italic text-sm">
                                -Rs. {{ number_format($tx->amount) }}
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-300 font-semibold uppercase tracking-wide">
                                {{ $tx->method }}
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-300 font-semibold">
                                @if($tx->withdrawal_details)
                                    <div class="p-3 bg-[#090a0f]/80 rounded-xl border border-white/5 space-y-1.5 min-w-[200px] w-max">
                                        @foreach($tx->withdrawal_details as $k => $v)
                                            <div class="text-[9px] text-slate-400 font-bold uppercase flex justify-between items-center gap-4">
                                                <span>{{ $k }}</span>
                                                <strong class="text-white font-mono lowercase select-all bg-[#090a0f] border border-white/10 px-1.5 py-0.5 rounded shadow-sm hover:border-neon-pink/40 cursor-pointer" onclick="navigator.clipboard.writeText('{{ $v }}'); alert('Copied: {{ $v }}');">{{ $v }}</strong>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-[9px] text-rose-400 font-bold uppercase tracking-wider bg-rose-500/5 border border-rose-500/15 px-2.5 py-1 rounded">MISSING DETAILS</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-400 font-mono">
                                {{ $tx->created_at->format('d M Y, h:i A') }}
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="flex justify-end gap-2">
                                    <form action="{{ route('admin.transactions.update', $tx->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="status" value="completed">
                                        <button
                                            class="bg-emerald-500 hover:bg-emerald-600 text-black px-3.5 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all cursor-pointer shadow-[0_0_10px_rgba(16,185,129,0.25)] flex items-center gap-1">
                                            <i data-lucide="check" class="w-3.5 h-3.5"></i> Paid
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.transactions.update', $tx->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="status" value="rejected">
                                        <button
                                            class="bg-rose-500/10 text-rose-400 hover:bg-rose-500/20 border border-rose-500/25 px-3.5 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all cursor-pointer flex items-center gap-1">
                                            <i data-lucide="x" class="w-3.5 h-3.5"></i> Decline
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-16 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i data-lucide="check-circle" class="w-12 h-12 text-slate-700 mb-3 animate-pulse"></i>
                                    <p class="text-base font-black text-white uppercase italic tracking-wider">All clear!</p>
                                    <p class="text-xs text-slate-400 mt-1">No pending withdrawal requests in queue.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())
            <div class="p-4 border-t border-white/5 bg-[#090a0f]/60">
                {{ $transactions->links('pagination::tailwind') }}
            </div>
        @endif
    </div>
@endsection
