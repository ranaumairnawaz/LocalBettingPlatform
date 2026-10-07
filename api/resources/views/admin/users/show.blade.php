@extends('admin.layout')

@section('content')
    <div class="mb-8">
        <a href="{{ route('admin.users') }}"
            class="inline-flex items-center gap-2 text-slate-400 hover:text-neon-cyan transition-all mb-4 text-xs font-black uppercase tracking-wider">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Users
        </a>
        <div class="flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-black text-white italic tracking-wider mb-2 flex items-center gap-2">
                    <span
                        class="bg-neon-cyan/20 w-2.5 h-8 rounded-full inline-block shadow-[0_0_15px_rgba(0,242,254,0.5)]"></span>
                    USER PROFILE
                </h1>
                <p class="text-slate-400 text-sm">Detailed overview of {{ $user->username }}'s account.</p>
            </div>
            <form action="{{ route('admin.users.ban', $user->id) }}" method="POST">
                @csrf
                @if($user->is_banned)
                    <button
                        class="bg-white/5 border border-white/10 hover:bg-white/10 text-white px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all cursor-pointer">
                        <i data-lucide="check-circle" class="w-4 h-4 inline-block align-middle mr-1"></i> Unban User
                    </button>
                @else
                    <button
                        class="bg-rose-500/10 text-rose-400 hover:bg-rose-500/20 border border-rose-500/25 px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all cursor-pointer shadow-[0_0_15px_rgba(239,68,68,0.15)]"
                        onclick="return confirm('Are you sure you want to ban this user?');">
                        <i data-lucide="ban" class="w-4 h-4 inline-block align-middle mr-1"></i> Ban User
                    </button>
                @endif
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
        <!-- Profile Card -->
        <div class="bg-[#121623]/60 rounded-[20px] p-6 border border-white/10 backdrop-blur-md flex items-center gap-4">
            <img src="{{ $user->avatar ? asset('storage/' . $user->avatar) : 'https://api.dicebear.com/7.x/initials/svg?seed=' . urlencode($user->username) }}"
                class="w-20 h-20 rounded-full border border-white/10 shrink-0">
            <div>
                <h2 class="text-xl font-black text-white italic uppercase tracking-wider">{{ $user->username }}</h2>
                <p class="text-xs text-slate-400 font-semibold mb-2.5">{{ $user->email ?? 'No email provided' }}</p>
                <div
                    class="text-[9px] font-mono bg-[#090a0f] border border-white/5 px-2.5 py-1 rounded-lg text-neon-cyan font-bold tracking-wider inline-flex items-center gap-1">
                    <i data-lucide="monitor" class="w-3 h-3"></i> IP: {{ $user->last_login_ip ?? 'Unknown' }}
                </div>
            </div>
        </div>

        <!-- Balance Card -->
        <div
            class="bg-[#121623]/60 rounded-[20px] p-6 border border-white/10 shadow-[0_0_20px_rgba(0,242,254,0.05)] relative overflow-hidden backdrop-blur-md flex items-center gap-4">
            <div class="absolute -right-4 -bottom-4 opacity-5 text-neon-cyan">
                <i data-lucide="wallet" class="w-24 h-24"></i>
            </div>
            <div
                class="bg-neon-cyan/15 w-12 h-12 rounded-xl flex items-center justify-center border border-neon-cyan/20 text-neon-cyan shrink-0">
                <i data-lucide="wallet" class="w-6 h-6 animate-pulse"></i>
            </div>
            <div>
                <p class="text-slate-400 font-bold uppercase tracking-wider text-[10px] mb-1">Current Balance</p>
                <h2 class="text-2xl font-black text-neon-cyan font-mono italic">Rs. {{ number_format($user->balance) }}</h2>
            </div>
        </div>

        <!-- Stats Card -->
        <div
            class="bg-[#121623]/60 rounded-[20px] p-6 border border-white/10 backdrop-blur-md flex flex-col justify-center gap-3">
            <div class="flex justify-between items-center border-b border-white/5 pb-2">
                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Total Won</span>
                <span class="text-sm font-black text-emerald-400 font-mono italic">+Rs.
                    {{ number_format($total_won) }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Total Lost</span>
                <span class="text-sm font-black text-rose-400 font-mono italic">-Rs. {{ number_format($total_lost) }}</span>
            </div>
        </div>

        <!-- Balance Control Card -->
        <div
            class="bg-[#121623]/60 rounded-[20px] p-6 border border-white/10 shadow-[0_0_20px_rgba(255,0,127,0.05)] backdrop-blur-md flex flex-col justify-center gap-3">
            <form action="{{ route('admin.users.adjust_balance', $user->id) }}" method="POST" class="space-y-2">
                @csrf
                <div class="flex gap-2">
                    <select name="action"
                        class="bg-[#090a0f] border border-white/10 rounded-xl py-2 px-2 text-xs text-slate-200 font-bold focus:outline-none focus:ring-1 focus:ring-neon-pink">
                        <option value="credit">CREDIT</option>
                        <option value="debit">DEBIT</option>
                    </select>
                    <div class="relative flex-1">
                        <span
                            class="absolute left-2.5 top-1/2 -translate-y-1/2 text-neon-pink text-xs font-black font-mono">Rs.</span>
                        <input type="number" step="0.01" min="0.01" name="amount" required
                            class="w-full bg-[#090a0f] border border-white/10 rounded-xl py-2 pl-7 pr-2 text-xs text-slate-200 font-mono font-bold focus:outline-none focus:ring-1 focus:ring-neon-pink"
                            placeholder="0">
                    </div>
                </div>
                <input type="text" name="admin_note" required
                    class="w-full bg-[#090a0f] border border-white/10 rounded-xl py-2 px-3 text-[10px] text-slate-200 focus:outline-none focus:ring-1 focus:ring-neon-pink"
                    placeholder="Adjustment reason">
                <button type="submit"
                    class="w-full bg-neon-pink hover:bg-neon-pink/90 text-white py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shadow-[0_0_10px_rgba(255,0,127,0.2)] cursor-pointer">
                    Apply
                </button>
            </form>
        </div>
    </div>

    <!-- Referral Downline Statistics Card -->
    <div
        class="bg-[#121623]/60 rounded-[20px] p-6 border border-white/10 shadow-[0_0_20px_rgba(0,242,254,0.05)] backdrop-blur-md mb-8">
        <div class="flex items-center gap-2 border-b border-white/5 pb-3 mb-4">
            <i data-lucide="users" class="w-5 h-5 text-neon-cyan animate-pulse"></i>
            <h3 class="font-black text-white italic uppercase tracking-wider text-sm">Referral Downline Network</h3>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
            <div class="bg-[#090a0f]/60 p-4 rounded-xl border border-white/5 text-center">
                <span class="block text-[9px] uppercase tracking-wider text-slate-400 font-bold mb-1">Level 1</span>
                <span class="text-xl font-black text-neon-cyan font-mono italic">{{ $team_stats['l1'] }}</span>
            </div>
            <div class="bg-[#090a0f]/60 p-4 rounded-xl border border-white/5 text-center">
                <span class="block text-[9px] uppercase tracking-wider text-slate-400 font-bold mb-1">Level 2</span>
                <span class="text-xl font-black text-white font-mono italic">{{ $team_stats['l2'] }}</span>
            </div>
            <div class="bg-[#090a0f]/60 p-4 rounded-xl border border-white/5 text-center">
                <span class="block text-[9px] uppercase tracking-wider text-slate-400 font-bold mb-1">Level 3</span>
                <span class="text-xl font-black text-white font-mono italic">{{ $team_stats['l3'] }}</span>
            </div>
            <div class="bg-[#090a0f]/60 p-4 rounded-xl border border-white/5 text-center">
                <span class="block text-[9px] uppercase tracking-wider text-slate-400 font-bold mb-1">Level 4</span>
                <span class="text-xl font-black text-white font-mono italic">{{ $team_stats['l4'] }}</span>
            </div>
            <div class="bg-[#090a0f]/60 p-4 rounded-xl border border-white/5 text-center">
                <span class="block text-[9px] uppercase tracking-wider text-slate-400 font-bold mb-1">Level 5</span>
                <span class="text-xl font-black text-white font-mono italic">{{ $team_stats['l5'] }}</span>
            </div>
            <div
                class="bg-[#090a0f]/10 p-4 rounded-xl border border-neon-cyan/20 text-center shadow-[0_0_10px_rgba(0,242,254,0.05)]">
                <span class="block text-[9px] uppercase tracking-wider text-neon-cyan font-bold mb-1">Total Team</span>
                <span class="text-xl font-black text-neon-cyan font-mono italic">{{ $team_stats['total'] }}</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Transactions -->
        <div class="bg-[#121623]/60 rounded-[20px] border border-white/10 shadow-sm overflow-hidden backdrop-blur-md">
            <div class="p-5 border-b border-white/5 bg-[#090a0f]/60">
                <h3 class="font-black text-white italic uppercase tracking-wider text-sm">Recent Transactions</h3>
            </div>
            <div class="p-0">
                <ul class="divide-y divide-white/5">
                    @forelse($user->transactions as $tx)
                        <li class="p-4 flex justify-between items-center hover:bg-white/5 transition-all">
                            <div class="flex items-center gap-3">
                                <div
                                    class="{{ $tx->type === 'deposit' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/25' : 'bg-neon-pink/10 text-neon-pink border border-neon-pink/25' }} w-10 h-10 rounded-xl flex items-center justify-center shrink-0">
                                    <i data-lucide="{{ $tx->type === 'deposit' ? 'arrow-down-to-line' : 'arrow-up-from-line' }}"
                                        class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <p class="font-black text-white italic uppercase tracking-wider text-xs">{{ $tx->type }}</p>
                                    <p class="text-[10px] text-slate-400 font-medium mt-0.5">
                                        {{ $tx->created_at->diffForHumans() }} &middot; {{ $tx->method }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-black text-white font-mono italic text-xs mb-1">Rs.
                                    {{ number_format($tx->amount) }}</p>
                                <span
                                    class="text-[8px] uppercase font-black px-2 py-0.5 rounded border {{ $tx->status === 'completed' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : ($tx->status === 'pending' ? 'bg-neon-gold/10 text-neon-gold border-neon-gold/20' : 'bg-rose-500/10 text-rose-400 border-rose-500/20') }}">{{ $tx->status }}</span>
                            </div>
                        </li>
                    @empty
                        <li class="p-8 text-center text-slate-500 text-xs font-semibold">No recent transactions.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <!-- Recent Gameplay -->
        <div class="bg-[#121623]/60 rounded-[20px] border border-white/10 shadow-sm overflow-hidden backdrop-blur-md">
            <div class="p-5 border-b border-white/5 bg-[#090a0f]/60">
                <h3 class="font-black text-white italic uppercase tracking-wider text-sm">Recent Gameplay</h3>
            </div>
            <div class="p-0">
                <ul class="divide-y divide-white/5">
                    @forelse($user->gameLogs as $log)
                        <li class="p-4 flex justify-between items-center hover:bg-white/5 transition-all">
                            <div class="flex items-center gap-3">
                                <div
                                    class="bg-white/5 border border-white/10 text-neon-cyan w-10 h-10 rounded-xl flex items-center justify-center shrink-0">
                                    <i data-lucide="gamepad-2" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <p class="font-black text-white italic uppercase tracking-wider text-xs">
                                        {{ $log->game->name ?? 'Unknown Game' }}</p>
                                    <p class="text-[10px] text-slate-400 font-medium mt-0.5">
                                        {{ $log->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            <div class="text-right flex flex-col items-end gap-1">
                                <span class="text-[10px] text-slate-400 font-bold uppercase">Bet: <strong
                                        class="text-white font-mono">Rs. {{ number_format($log->bet_amount) }}</strong></span>
                                @if($log->result === 'win')
                                    <span class="font-black text-emerald-400 font-mono text-xs">+Rs.
                                        {{ number_format($log->payout_amount) }}</span>
                                @elseif($log->result === 'lose')
                                    <span class="font-black text-rose-400 font-mono text-xs">-Rs.
                                        {{ number_format($log->bet_amount) }}</span>
                                @else
                                    <span class="font-black text-slate-500 font-mono text-xs">Pending</span>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="p-8 text-center text-slate-500 text-xs font-semibold">No recent gameplay.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
@endsection