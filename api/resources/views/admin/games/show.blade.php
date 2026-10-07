@extends('admin.layout')

@section('content')
<div class="mb-8 flex justify-between items-center">
    <div>
        <h1 class="text-3xl font-black text-white italic tracking-wider mb-2 flex items-center gap-2">
            <span class="bg-neon-cyan/20 w-2.5 h-8 rounded-full inline-block shadow-[0_0_15px_rgba(0,242,254,0.5)]"></span>
            {{ $game->name }} OVERVIEW
        </h1>
        <p class="text-slate-400 text-sm">Manage settings, configuration, and performance metrics for this game.</p>
    </div>
    <a href="{{ route('admin.games') }}" class="text-xs font-black text-slate-400 uppercase tracking-widest hover:text-white transition flex items-center gap-2">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Games
    </a>
</div>

@if(session('success'))
<div class="mb-6 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl px-4 py-3 text-sm font-bold flex items-center gap-2 shadow-[0_0_15px_rgba(16,185,129,0.1)]">
    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-400"></i>
    {{ session('success') }}
</div>
@endif

<!-- Analytics Performance Row -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <!-- Total Bets -->
    <div class="bg-[#121623]/60 rounded-2xl border border-white/5 p-6 flex flex-col justify-center relative overflow-hidden backdrop-blur-md">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-neon-cyan/5 rounded-full blur-2xl pointer-events-none"></div>
        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 flex items-center gap-2">
            <i data-lucide="coins" class="w-3.5 h-3.5 text-neon-cyan"></i> Total Bets Placed
        </div>
        <div class="text-2xl font-black text-white">
            <span class="text-neon-cyan text-sm mr-1">Rs.</span>{{ number_format($total_bet, 2) }}
        </div>
    </div>

    <!-- Total Winnings Paid -->
    <div class="bg-[#121623]/60 rounded-2xl border border-white/5 p-6 flex flex-col justify-center relative overflow-hidden backdrop-blur-md">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-amber-500/5 rounded-full blur-2xl pointer-events-none"></div>
        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 flex items-center gap-2">
            <i data-lucide="trophy" class="w-3.5 h-3.5 text-amber-500"></i> Total Won by Users
        </div>
        <div class="text-2xl font-black text-white">
            <span class="text-amber-500 text-sm mr-1">Rs.</span>{{ number_format($total_won, 2) }}
        </div>
    </div>

    <!-- Commission Earned -->
    <div class="bg-[#121623]/60 rounded-2xl border border-white/5 p-6 flex flex-col justify-center relative overflow-hidden backdrop-blur-md">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-500/5 rounded-full blur-2xl pointer-events-none"></div>
        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 flex items-center gap-2">
            <i data-lucide="percent" class="w-3.5 h-3.5 text-emerald-500"></i> Commission Earned
        </div>
        <div class="text-2xl font-black text-white">
            <span class="text-emerald-500 text-sm mr-1">Rs.</span>{{ number_format($total_commission, 2) }}
        </div>
    </div>

    <!-- Net Balance -->
    <div class="bg-[#121623]/60 rounded-2xl border border-white/5 p-6 flex flex-col justify-center relative overflow-hidden backdrop-blur-md">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-purple-500/5 rounded-full blur-2xl pointer-events-none"></div>
        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 flex items-center gap-2">
            <i data-lucide="wallet" class="w-3.5 h-3.5 text-purple-500"></i> Net Balance (Profit)
        </div>
        <div class="text-2xl font-black text-white">
            <span class="text-purple-500 text-sm mr-1">Rs.</span>{{ number_format($net_balance, 2) }}
        </div>
    </div>
</div>

<div class="bg-[#121623]/60 rounded-[20px] border border-white/10 shadow-sm overflow-hidden backdrop-blur-md">
    <div class="p-6 border-b border-white/5 bg-[#090a0f]/60 flex justify-between items-center">
        <div>
            <h3 class="text-base font-black text-white italic uppercase tracking-wider mb-1">Game Configuration</h3>
            <p class="text-xs text-slate-400">Update general settings for {{ $game->name }}</p>
        </div>
        <div class="flex gap-2">
            <!-- Toggles -->
            <form action="{{ route('admin.games.toggle', $game->id) }}" method="POST">
                @csrf
                <button type="submit"
                    class="relative inline-flex items-center gap-2 px-3 py-2 rounded-xl text-[10px] font-black transition-all duration-300 shadow-sm w-32 justify-center cursor-pointer
                            {{ $game->is_active
                            ? 'bg-emerald-500/10 text-emerald-400 hover:bg-rose-500/10 hover:text-rose-400 border border-emerald-500/20 hover:border-rose-500/20 shadow-[0_0_10px_rgba(16,185,129,0.1)]'
                            : 'bg-rose-500/10 text-rose-400 hover:bg-emerald-500/10 hover:text-emerald-400 border border-rose-500/20 hover:border-emerald-500/20' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $game->is_active ? 'bg-emerald-400' : 'bg-rose-400' }}"></span>
                    <span>{{ $game->is_active ? 'LIVE' : 'OFFLINE' }}</span>
                </button>
            </form>
            <form action="{{ route('admin.games.toggle-featured', $game->id) }}" method="POST">
                @csrf
                <button type="submit"
                    class="relative inline-flex items-center gap-2 px-3 py-2 rounded-xl text-[10px] font-black transition-all duration-300 shadow-sm w-32 justify-center cursor-pointer
                            {{ $game->is_featured
                            ? 'bg-amber-500/10 text-amber-400 hover:bg-slate-500/15 hover:text-slate-400 border border-amber-500/20 hover:border-white/10 shadow-[0_0_10px_rgba(245,158,11,0.15)]'
                            : 'bg-slate-500/15 text-slate-400 hover:bg-amber-500/10 hover:text-amber-400 border border-white/10 hover:border-amber-500/20' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $game->is_featured ? 'bg-amber-400' : 'bg-slate-600' }}"></span>
                    <span>{{ $game->is_featured ? 'FEATURED' : 'STANDARD' }}</span>
                </button>
            </form>
        </div>
    </div>

    <form action="{{ route('admin.games.update', $game->id) }}" method="POST" enctype="multipart/form-data" class="p-8 space-y-8">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="flex gap-6">
                <!-- Logos -->
                <div class="flex flex-col items-center">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider mb-2">Game Logo</span>
                    <div class="w-24 h-24 bg-[#090a0f] rounded-2xl border border-white/10 flex items-center justify-center overflow-hidden">
                        @if($game->image)
                            <img src="{{ asset('storage/' . $game->image) }}" class="w-full h-full object-cover">
                        @else
                            <i data-lucide="image" class="w-8 h-8 text-slate-600"></i>
                        @endif
                    </div>
                    <input type="file" name="image" accept="image/*" class="w-full text-[9px] text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:bg-white/5 file:text-slate-200 mt-2 cursor-pointer">
                </div>
                <div class="flex flex-col items-center">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider mb-2">Thumbnail</span>
                    <div class="w-24 h-24 bg-[#090a0f] rounded-2xl border border-white/10 flex items-center justify-center overflow-hidden">
                        @if($game->thumbnail)
                            <img src="{{ asset('storage/' . $game->thumbnail) }}" class="w-full h-full object-cover">
                        @else
                            <i data-lucide="image" class="w-8 h-8 text-slate-600"></i>
                        @endif
                    </div>
                    <input type="file" name="thumbnail" accept="image/*" class="w-full text-[9px] text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:bg-white/5 file:text-slate-200 mt-2 cursor-pointer">
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Game Name</label>
                    <input type="text" name="name" value="{{ $game->name }}" class="w-full bg-[#090a0f] border border-white/10 rounded-xl py-2.5 px-4 focus:ring-1 focus:ring-neon-cyan focus:border-neon-cyan focus:outline-none font-semibold mt-1.5 text-slate-200 text-sm">
                </div>
                <div>
                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Commission Rate (%)</label>
                    <input type="number" step="0.1" name="commission_rate" value="{{ $game->commission_rate }}" class="w-full bg-[#090a0f] border border-white/10 rounded-xl py-2.5 px-4 focus:ring-1 focus:ring-neon-cyan focus:border-neon-cyan focus:outline-none font-semibold mt-1.5 text-slate-200 text-sm">
                    <p class="mt-1 text-[9px] text-slate-500">Platform cut from the winning pot.</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Minimum Bet Limit (Rs)</label>
                <input type="number" step="0.1" name="min_bet" value="{{ $game->min_bet }}" class="w-full bg-[#090a0f] border border-white/10 rounded-xl py-2.5 px-4 focus:ring-1 focus:ring-neon-cyan focus:border-neon-cyan focus:outline-none font-semibold mt-1.5 text-slate-200 text-sm">
            </div>
            <div>
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Maximum Bet Limit (Rs)</label>
                <input type="number" step="0.1" name="max_bet" value="{{ $game->max_bet }}" class="w-full bg-[#090a0f] border border-white/10 rounded-xl py-2.5 px-4 focus:ring-1 focus:ring-neon-cyan focus:border-neon-cyan focus:outline-none font-semibold mt-1.5 text-slate-200 text-sm">
            </div>
        </div>

        <!-- Dynamic Game Specific Settings -->
        @if($game->slug === 'teen_patti')
        <div class="p-6 border border-amber-500/20 bg-amber-500/5 rounded-2xl relative overflow-hidden">
            <h4 class="text-sm font-black text-amber-500 italic uppercase tracking-wider mb-2 flex items-center gap-2">
                <i data-lucide="layers" class="w-4 h-4"></i> Teen Patti Specific Settings
            </h4>
            <div class="mt-4">
                <label class="text-[9px] font-black text-amber-400/80 uppercase tracking-widest">Boot Values (Comma Separated Array)</label>
                <input type="text" name="three_patti_boots" value="{{ $specific_settings['three_patti_boots'] }}" class="w-full bg-[#090a0f] border border-amber-500/20 rounded-xl py-2.5 px-4 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 focus:outline-none font-semibold mt-1.5 text-amber-100 text-sm">
                <p class="mt-1 text-[9px] text-amber-500/60">Enter the boot (ante) amounts available in the lobby, separated by commas. E.g., 10, 50, 100</p>
            </div>
        </div>
        @endif

        <div>
            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Game Rules & Guidelines</label>
            <textarea name="guidelines" rows="4" class="w-full bg-[#090a0f] border border-white/10 rounded-xl py-3 px-4 focus:ring-1 focus:ring-neon-cyan focus:border-neon-cyan focus:outline-none mt-1.5 font-sans text-slate-200 text-sm leading-relaxed">{{ $game->guidelines }}</textarea>
        </div>

        <!-- Hidden toggles state so update doesn't clear them if not passed -->
        <input type="hidden" name="is_active" value="{{ $game->is_active ? '1' : '0' }}">
        <input type="hidden" name="is_featured" value="{{ $game->is_featured ? '1' : '0' }}">

        <div class="pt-4 border-t border-white/5 flex justify-end">
            <button class="bg-neon-pink text-white border border-neon-pink/30 hover:scale-[1.02] shadow-[0_0_15px_rgba(255,0,127,0.3)] px-8 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all cursor-pointer">
                Save Changes
            </button>
        </div>
    </form>
</div>
@endsection
