@extends('admin.layout')
@section('content')
    <div class="mb-8">
        <h1 class="text-3xl font-black text-white italic tracking-wider mb-2 flex items-center gap-2">
            <span class="bg-neon-cyan/20 w-2.5 h-8 rounded-full inline-block shadow-[0_0_15px_rgba(0,242,254,0.5)]"></span>
            SA SETTINGS
        </h1>
        <p class="text-slate-400 text-sm">Configure commission rates, bonuses, and rank thresholds.</p>
    </div>

    <form action="{{ route('admin.super_agents.settings.update') }}" method="POST" class="space-y-8">
        @csrf

        <!-- Commission Rates -->
        <div class="bg-[#121623]/60 rounded-[20px] border border-white/10 backdrop-blur-md p-8">
            <h2 class="text-xl font-black text-white italic tracking-wider mb-6 uppercase flex items-center gap-2">
                <span class="bg-neon-cyan/20 w-1.5 h-6 rounded-full"></span>
                Commission Rates by Rank & Level
            </h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                @foreach(['bronze', 'silver', 'gold', 'diamond', 'platinum'] as $rank)
                    <div class="bg-[#090a0f]/40 rounded-xl p-6 border border-white/5">
                        <h3 class="text-sm font-black text-white uppercase tracking-wider mb-4 capitalize">{{ $rank }} Rank</h3>
                        <div class="space-y-3">
                            @foreach([1, 2, 3, 4, 5] as $level)
                                <div class="flex items-center gap-3">
                                    <label class="text-xs font-black text-slate-400 uppercase tracking-wider min-w-[50px]">Level {{ $level }}</label>
                                    <input type="number" 
                                        name="super_agent_commission_{{ $rank }}_l{{ $level }}"
                                        value="{{ config('system-settings.super_agent_commission_' . $rank . '_l' . $level) ?? 5 }}"
                                        step="0.1" 
                                        class="flex-1 bg-[#121623] border border-white/10 rounded-lg py-2 px-3 text-white focus:outline-none focus:ring-1 focus:ring-neon-cyan text-xs font-semibold">
                                    <span class="text-xs text-slate-400 font-black">%</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Game Commission Rate -->
        <div class="bg-[#121623]/60 rounded-[20px] border border-white/10 backdrop-blur-md p-8">
            <h2 class="text-xl font-black text-white italic tracking-wider mb-6 uppercase flex items-center gap-2">
                <span class="bg-neon-gold/20 w-1.5 h-6 rounded-full"></span>
                Game Commission
            </h2>

            <div class="bg-[#090a0f]/40 rounded-xl p-6 border border-white/5 max-w-xs">
                <div class="flex items-center gap-3">
                    <label class="text-xs font-black text-slate-400 uppercase tracking-wider flex-1">Commission Rate</label>
                    <input type="number" 
                        name="super_agent_game_commission_rate"
                        value="{{ config('system-settings.super_agent_game_commission_rate') ?? 2 }}"
                        step="0.1" 
                        class="w-20 bg-[#121623] border border-white/10 rounded-lg py-2 px-3 text-white focus:outline-none focus:ring-1 focus:ring-neon-cyan text-xs font-semibold">
                    <span class="text-xs text-slate-400 font-black">%</span>
                </div>
                <p class="text-[10px] text-slate-500 mt-2">Applied to all game commissions, regardless of rank.</p>
            </div>
        </div>

        <!-- Content Bonus Rates -->
        <div class="bg-[#121623]/60 rounded-[20px] border border-white/10 backdrop-blur-md p-8">
            <h2 class="text-xl font-black text-white italic tracking-wider mb-6 uppercase flex items-center gap-2">
                <span class="bg-neon-pink/20 w-1.5 h-6 rounded-full"></span>
                Content Bonus by Platform & Milestone
            </h2>

            @foreach(['youtube' => 'YouTube', 'instagram' => 'Instagram', 'tiktok' => 'TikTok'] as $platform => $label)
                <div class="mb-6">
                    <h3 class="text-sm font-black text-white uppercase tracking-wider mb-4">{{ $label }}</h3>
                    <div class="bg-[#090a0f]/40 rounded-xl p-6 border border-white/5">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            @foreach([10000, 50000, 100000, 500000] as $views)
                                <div>
                                    <label class="text-xs font-black text-slate-400 uppercase tracking-wider block mb-2">{{ number_format($views) }} Views</label>
                                    <input type="number" 
                                        name="content_bonus_{{ $platform }}_{{ $views }}"
                                        value="{{ config('system-settings.content_bonus_' . $platform . '_' . $views) ?? 500 }}"
                                        step="100" 
                                        class="w-full bg-[#121623] border border-white/10 rounded-lg py-2 px-3 text-white focus:outline-none focus:ring-1 focus:ring-neon-cyan text-xs font-semibold">
                                    <span class="text-[9px] text-slate-400 font-black">Rs.</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Rank Upgrade Thresholds -->
        <div class="bg-[#121623]/60 rounded-[20px] border border-white/10 backdrop-blur-md p-8">
            <h2 class="text-xl font-black text-white italic tracking-wider mb-6 uppercase flex items-center gap-2">
                <span class="bg-emerald-500/20 w-1.5 h-6 rounded-full"></span>
                Rank Upgrade Thresholds
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-[#090a0f]/40 rounded-xl p-6 border border-white/5">
                    <h3 class="text-sm font-black text-white uppercase tracking-wider mb-4">Team Size</h3>
                    <div class="space-y-3">
                        @foreach(['bronze' => 'Bronze', 'silver' => 'Silver', 'gold' => 'Gold', 'diamond' => 'Diamond', 'platinum' => 'Platinum'] as $rank => $label)
                            <div>
                                <label class="text-xs font-black text-slate-400 uppercase tracking-wider block mb-1">{{ $label }}</label>
                                <input type="number" 
                                    name="rank_upgrade_team_size_{{ $rank }}"
                                    value="{{ config('system-settings.rank_upgrade_team_size_' . $rank) ?? (match($rank) {
                                        'bronze' => 5,
                                        'silver' => 15,
                                        'gold' => 30,
                                        'diamond' => 50,
                                        'platinum' => 100,
                                    }) }}"
                                    step="1" 
                                    class="w-full bg-[#121623] border border-white/10 rounded-lg py-2 px-3 text-white focus:outline-none focus:ring-1 focus:ring-neon-cyan text-xs font-semibold">
                                </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-[#090a0f]/40 rounded-xl p-6 border border-white/5">
                    <h3 class="text-sm font-black text-white uppercase tracking-wider mb-4">Earnings Threshold</h3>
                    <div class="space-y-3">
                        @foreach(['bronze' => 'Bronze', 'silver' => 'Silver', 'gold' => 'Gold', 'diamond' => 'Diamond', 'platinum' => 'Platinum'] as $rank => $label)
                            <div>
                                <label class="text-xs font-black text-slate-400 uppercase tracking-wider block mb-1">{{ $label }}</label>
                                <input type="number" 
                                    name="rank_upgrade_earnings_{{ $rank }}"
                                    value="{{ config('system-settings.rank_upgrade_earnings_' . $rank) ?? (match($rank) {
                                        'bronze' => 50000,
                                        'silver' => 150000,
                                        'gold' => 300000,
                                        'diamond' => 500000,
                                        'platinum' => 1000000,
                                    }) }}"
                                    step="10000" 
                                    class="w-full bg-[#121623] border border-white/10 rounded-lg py-2 px-3 text-white focus:outline-none focus:ring-1 focus:ring-neon-cyan text-xs font-semibold">
                                <span class="text-[9px] text-slate-400 font-black">Rs.</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-[#090a0f]/40 rounded-xl p-6 border border-white/5">
                    <h3 class="text-sm font-black text-white uppercase tracking-wider mb-4">Upgrade Mode</h3>
                    <div class="space-y-4">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="radio" name="rank_upgrade_mode" value="team_size" 
                                {{ (config('system-settings.rank_upgrade_mode') ?? 'team_size') === 'team_size' ? 'checked' : '' }}
                                class="w-4 h-4 accent-neon-cyan cursor-pointer">
                            <span class="text-xs font-black text-white uppercase tracking-wider">Team Size Based</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="radio" name="rank_upgrade_mode" value="earnings" 
                                {{ (config('system-settings.rank_upgrade_mode') ?? 'team_size') === 'earnings' ? 'checked' : '' }}
                                class="w-4 h-4 accent-neon-cyan cursor-pointer">
                            <span class="text-xs font-black text-white uppercase tracking-wider">Earnings Based</span>
                        </label>
                        <p class="text-[10px] text-slate-400 pt-2 border-t border-white/10">Used by nightly upgrade command.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex gap-3 pt-6 border-t border-white/10">
            <button type="submit" class="flex-1 bg-neon-cyan hover:bg-neon-cyan/95 text-black px-6 py-3 rounded-xl font-black uppercase tracking-widest transition-all shadow-[0_0_10px_rgba(0,242,254,0.2)]">
                Save Settings
            </button>
            <a href="{{ route('admin.super_agents.index') }}" class="flex-1 bg-white/5 hover:bg-white/10 text-slate-300 px-6 py-3 rounded-xl font-black uppercase tracking-widest transition-all text-center">
                Cancel
            </a>
        </div>

        @if($errors->any())
            <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 text-red-400 text-sm">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if(session('success'))
            <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-4 text-emerald-400 text-sm">
                {{ session('success') }}
            </div>
        @endif
    </form>
@endsection
