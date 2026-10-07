@extends('admin.layout')
@section('content')
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-black text-white italic tracking-wider mb-2 flex items-center gap-2">
                <span
                    class="bg-emerald-500/20 w-2.5 h-8 rounded-full inline-block shadow-[0_0_15px_rgba(16,185,129,0.5)]"></span>
                PENDING DEPOSITS
            </h1>
            <p class="text-slate-400 text-sm">Verify and approve incoming player payments.</p>
        </div>

        <!-- Search and Filters Form -->
        <form action="{{ route('admin.transactions.pending_deposits') }}" method="GET"
            class="flex gap-2 w-full md:w-auto bg-[#121623]/60 p-3 rounded-[20px] border border-white/10 backdrop-blur-md">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search depositor, ref..."
                class="bg-[#090a0f] border border-white/5 rounded-xl py-2 px-3 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-emerald-500 min-w-[200px] flex-1">
            <button type="submit"
                class="bg-emerald-500 hover:bg-emerald-600 text-black px-5 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all shadow-[0_0_10px_rgba(16,185,129,0.2)] cursor-pointer">Filter</button>
            <a href="{{ route('admin.transactions.pending_deposits') }}"
                class="bg-white/5 hover:bg-white/10 text-slate-300 px-5 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex items-center justify-center cursor-pointer">Reset</a>
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
                        <th class="py-4 px-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Ref / Proof
                        </th>
                        <th class="py-4 px-6 text-[10px] font-black uppercase tracking-widest text-slate-400 font-mono">Date
                        </th>
                        <th class="py-4 px-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">
                            Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($transactions as $tx)
                        <tr class="hover:bg-white/5 transition-colors border-b border-white/5">
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($tx->user->username) }}&background=121623&color=10b981"
                                        class="w-8 h-8 rounded-full border border-white/10">
                                    <span
                                        class="font-black text-white italic uppercase tracking-wider text-xs">{{ $tx->user->username }}</span>
                                </div>
                            </td>
                            <td class="py-4 px-6 font-black text-emerald-400 font-mono italic text-sm">
                                +Rs. {{ number_format($tx->amount) }}
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-300 font-semibold uppercase tracking-wide">
                                {{ $tx->method }}
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-300 font-semibold">
                                <span
                                    class="font-mono bg-[#090a0f] border border-white/5 px-2 py-1 rounded text-slate-400 text-[10px] select-all block w-max">{{ $tx->reference ?? 'NO REF PROVIDED' }}</span>
                                @if($tx->proof_image)
                                    <button
                                        @click="$dispatch('open-modal', { image: '{{ asset('storage/' . $tx->proof_image) }}', id: {{ $tx->id }} })"
                                        class="block mt-2 text-[9px] text-neon-cyan font-black uppercase tracking-widest hover:text-neon-cyan/80 transition-colors flex items-center gap-1 cursor-pointer">
                                        <i data-lucide="image" class="w-3 h-3"></i> View Proof Screenshot
                                    </button>
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
                                            <i data-lucide="check" class="w-3.5 h-3.5"></i> Approve
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.transactions.update', $tx->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="status" value="rejected">
                                        <button
                                            class="bg-rose-500/10 text-rose-400 hover:bg-rose-500/20 border border-rose-500/25 px-3.5 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all cursor-pointer flex items-center gap-1">
                                            <i data-lucide="x" class="w-3.5 h-3.5"></i> Reject
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
                                    <p class="text-base font-black text-white uppercase italic tracking-wider">All caught up!
                                    </p>
                                    <p class="text-xs text-slate-400 mt-1">No pending deposit approvals in queue.</p>
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

    <!-- Alpine Modal -->
    <div x-data="{ open: false, imageUrl: '', txId: null }"
        @open-modal.window="open = true; imageUrl = $event.detail.image; txId = $event.detail.id"
        @keydown.escape.window="open = false" x-show="open"
        class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-black/80 backdrop-blur-sm"
        style="display: none;">

        <div @click.outside="open = false" class="relative w-full max-w-2xl max-h-full p-4">
            <div
                class="relative bg-[#121623] border border-white/10 rounded-2xl shadow-[0_0_50px_rgba(0,0,0,0.8)] flex flex-col max-h-[90vh] text-white">
                <div class="flex justify-between items-center p-4 border-b border-white/5 bg-[#090a0f]/80 rounded-t-2xl">
                    <h3 class="text-lg font-black text-white italic uppercase tracking-wider">Deposit Proof</h3>
                    <button @click="open = false"
                        class="text-slate-400 bg-transparent hover:bg-white/5 hover:text-white rounded-xl text-sm p-1.5 ml-auto inline-flex items-center transition-all cursor-pointer">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <div class="p-6 flex-1 overflow-auto flex justify-center bg-[#090a0f]/40 min-h-[300px]">
                    <img :src="imageUrl" alt="Proof"
                        class="max-w-full rounded-xl object-contain shadow-2xl border border-white/10">
                </div>

                <div class="flex items-center justify-end p-4 border-t border-white/5 bg-[#090a0f]/80 gap-3 rounded-b-2xl">
                    <button @click="open = false"
                        class="text-slate-300 bg-white/5 hover:bg-white/10 border border-white/5 font-black uppercase tracking-widest text-[10px] px-5 py-2.5 rounded-xl transition-all cursor-pointer">Close</button>

                    <form :action="'{{ url('admin/transactions') }}/' + txId + '/update'" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="status" value="rejected">
                        <button
                            class="bg-rose-500/10 text-rose-400 border border-rose-500/25 hover:bg-rose-500/20 font-black uppercase tracking-widest text-[10px] px-5 py-2.5 rounded-xl flex items-center gap-1.5 transition-all cursor-pointer">
                            <i data-lucide="x-circle" class="w-4 h-4"></i> Reject
                        </button>
                    </form>

                    <form :action="'{{ url('admin/transactions') }}/' + txId + '/update'" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="status" value="completed">
                        <button
                            class="bg-emerald-500 hover:bg-emerald-600 text-black font-black uppercase tracking-widest text-[10px] px-5 py-2.5 rounded-xl flex items-center gap-1.5 transition-all cursor-pointer shadow-[0_0_15px_rgba(16,185,129,0.3)]">
                            <i data-lucide="check-circle" class="w-4 h-4"></i> Approve
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection