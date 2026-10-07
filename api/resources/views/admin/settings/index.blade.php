@extends('admin.layout')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-black text-white italic tracking-wider mb-2 flex items-center gap-2">
        <span class="bg-neon-cyan/20 w-2.5 h-8 rounded-full inline-block shadow-[0_0_15px_rgba(0,242,254,0.5)]"></span>
        PLATFORM SETTINGS
    </h1>
    <p class="text-slate-400 text-sm">Configure global variables, maintenance mode, and branding.</p>
</div>

<form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" class="bg-[#121623]/60 rounded-[20px] border border-white/10 shadow-sm overflow-hidden backdrop-blur-md">
    @csrf
    
    <div class="p-6 border-b border-white/5 bg-[#090a0f]/60">
        <h3 class="text-base font-black text-white italic uppercase tracking-wider mb-1">General Identity</h3>
        <p class="text-xs text-slate-400">The basic details of your application.</p>
    </div>
    
    <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
        <div>
            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Site Name</label>
            <input type="text" name="site_name" value="{{ $settings['site_name'] ?? '666EGame' }}" class="w-full bg-[#090a0f] border border-white/10 rounded-xl py-2.5 px-4 focus:outline-none focus:ring-1 focus:ring-neon-cyan focus:border-neon-cyan text-slate-200 font-semibold text-sm">
            <p class="mt-2 text-[10px] text-slate-400 font-medium tracking-wide">This will be displayed in the header and metadata.</p>
        </div>

        <div>
            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Site Logo</label>
            <div class="flex items-center gap-4">
                @if(isset($settings['site_logo']))
                    <div class="w-16 h-16 rounded-2xl bg-[#090a0f] border border-white/10 flex items-center justify-center overflow-hidden shrink-0 hover:border-neon-cyan/50 transition-colors duration-300">
                        <img src="{{ asset('storage/' . $settings['site_logo']) }}" class="w-full h-full object-contain p-2">
                    </div>
                @endif
                <input type="file" name="logo" accept="image/*" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-white/5 file:text-slate-200 hover:file:bg-white/10 cursor-pointer border border-white/10 rounded-xl p-1 bg-[#090a0f]">
            </div>
        </div>
    </div>

    <div class="p-6 border-y border-white/5 bg-[#090a0f]/60">
        <h3 class="text-base font-black text-white italic uppercase tracking-wider mb-1">Platform Operations</h3>
        <p class="text-xs text-slate-400">Configure financial limits and global states.</p>
    </div>

    <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
        <div>
            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Currency Symbol</label>
            <input type="text" name="currency_symbol" value="{{ $settings['currency_symbol'] ?? 'Rs.' }}" class="w-full bg-[#090a0f] border border-white/10 rounded-xl py-2.5 px-4 focus:outline-none focus:ring-1 focus:ring-neon-cyan focus:border-neon-cyan text-slate-200 font-semibold text-sm">
        </div>

        <div>
            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Minimum Withdrawal Limit</label>
            <input type="number" name="min_withdrawal" value="{{ $settings['min_withdrawal'] ?? '500' }}" class="w-full bg-[#090a0f] border border-white/10 rounded-xl py-2.5 px-4 focus:outline-none focus:ring-1 focus:ring-neon-cyan focus:border-neon-cyan text-slate-200 font-semibold text-sm">
        </div>

        <div>
            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Ludo Platform Fee (%)</label>
            <input type="number" name="ludo_platform_fee" value="{{ $settings['ludo_platform_fee'] ?? '10' }}" class="w-full bg-[#090a0f] border border-white/10 rounded-xl py-2.5 px-4 focus:outline-none focus:ring-1 focus:ring-neon-cyan focus:border-neon-cyan text-slate-200 font-semibold text-sm">
            <p class="mt-2 text-[10px] text-slate-400 font-medium tracking-wide">Percentage taken from the prize pool on win.</p>
        </div>

        <div>
            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Matchmaking Minimum Bet %</label>
            <input type="number" name="matching_min_percent" value="{{ $settings['matching_min_percent'] ?? '50' }}" class="w-full bg-[#090a0f] border border-white/10 rounded-xl py-2.5 px-4 focus:outline-none focus:ring-1 focus:ring-neon-cyan focus:border-neon-cyan text-slate-200 font-semibold text-sm">
            <p class="mt-2 text-[10px] text-slate-400 font-medium tracking-wide">Minimum percentage of bet amount to match against (e.g. 50%).</p>
        </div>

        <div>
            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Matchmaking Maximum Bet %</label>
            <input type="number" name="matching_max_percent" value="{{ $settings['matching_max_percent'] ?? '200' }}" class="w-full bg-[#090a0f] border border-white/10 rounded-xl py-2.5 px-4 focus:outline-none focus:ring-1 focus:ring-neon-cyan focus:border-neon-cyan text-slate-200 font-semibold text-sm">
            <p class="mt-2 text-[10px] text-slate-400 font-medium tracking-wide">Maximum percentage of bet amount to match against (e.g. 200%).</p>
        </div>

        <div class="md:col-span-2">
            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Global Announcement (Broadcast)</label>
            <textarea name="global_announcement" rows="3" class="w-full bg-[#090a0f] border border-white/10 rounded-xl py-2.5 px-4 focus:outline-none focus:ring-1 focus:ring-neon-cyan focus:border-neon-cyan text-slate-200 font-medium text-sm leading-relaxed" placeholder="Welcome to the new platform!">{{ $settings['global_announcement'] ?? '' }}</textarea>
            <p class="mt-2 text-[10px] text-slate-400 font-medium tracking-wide">Leave blank to disable the announcement banner for users.</p>
        </div>
    </div>

    <div class="p-6 border-y border-white/5 bg-[#090a0f]/60">
        <h3 class="text-base font-black text-white italic uppercase tracking-wider mb-1">Referral Team Commissions</h3>
        <p class="text-xs text-slate-400">Configure downline commission percentages (Level 1 to 5).</p>
    </div>

    <div class="p-8 grid grid-cols-1 md:grid-cols-5 gap-6">
        <div>
            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Level 1 (%)</label>
            <input type="number" step="0.1" name="referral_commission_l1" value="{{ $settings['referral_commission_l1'] ?? '0' }}" class="w-full bg-[#090a0f] border border-white/10 rounded-xl py-2.5 px-4 focus:outline-none focus:ring-1 focus:ring-neon-cyan focus:border-neon-cyan text-slate-200 font-mono font-bold text-sm">
        </div>
        <div>
            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Level 2 (%)</label>
            <input type="number" step="0.1" name="referral_commission_l2" value="{{ $settings['referral_commission_l2'] ?? '0' }}" class="w-full bg-[#090a0f] border border-white/10 rounded-xl py-2.5 px-4 focus:outline-none focus:ring-1 focus:ring-neon-cyan focus:border-neon-cyan text-slate-200 font-mono font-bold text-sm">
        </div>
        <div>
            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Level 3 (%)</label>
            <input type="number" step="0.1" name="referral_commission_l3" value="{{ $settings['referral_commission_l3'] ?? '0' }}" class="w-full bg-[#090a0f] border border-white/10 rounded-xl py-2.5 px-4 focus:outline-none focus:ring-1 focus:ring-neon-cyan focus:border-neon-cyan text-slate-200 font-mono font-bold text-sm">
        </div>
        <div>
            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Level 4 (%)</label>
            <input type="number" step="0.1" name="referral_commission_l4" value="{{ $settings['referral_commission_l4'] ?? '0' }}" class="w-full bg-[#090a0f] border border-white/10 rounded-xl py-2.5 px-4 focus:outline-none focus:ring-1 focus:ring-neon-cyan focus:border-neon-cyan text-slate-200 font-mono font-bold text-sm">
        </div>
        <div>
            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Level 5 (%)</label>
            <input type="number" step="0.1" name="referral_commission_l5" value="{{ $settings['referral_commission_l5'] ?? '0' }}" class="w-full bg-[#090a0f] border border-white/10 rounded-xl py-2.5 px-4 focus:outline-none focus:ring-1 focus:ring-neon-cyan focus:border-neon-cyan text-slate-200 font-mono font-bold text-sm">
        </div>
    </div>

    <div class="p-6 border-y border-white/5 bg-[#090a0f]/60">
        <h3 class="text-base font-black text-white italic uppercase tracking-wider mb-1">Maintenance</h3>
    </div>

    <div class="p-8">
        <label class="flex items-center gap-4 cursor-pointer p-4 border border-rose-500/20 bg-rose-500/5 rounded-xl hover:bg-rose-500/10 transition-colors">
            <input type="hidden" name="maintenance_mode" value="0">
            <input type="checkbox" name="maintenance_mode" value="1" {{ (isset($settings['maintenance_mode']) && $settings['maintenance_mode'] == '1') ? 'checked' : '' }} class="w-5 h-5 text-rose-500 bg-[#090a0f] rounded border-white/10 focus:ring-rose-500 focus:ring-offset-0 cursor-pointer">
            <div>
                <span class="block text-xs font-black text-white uppercase tracking-wider">Enable Maintenance Mode</span>
                <span class="block text-[10px] text-rose-400 font-bold tracking-wide mt-1">When enabled, regular users will see a maintenance screen. Admins can still access the dashboard.</span>
            </div>
        </label>
    </div>

    <div class="p-6 bg-[#090a0f]/60 border-t border-white/5 flex justify-end">
        <button type="submit" class="bg-neon-pink text-white border border-neon-pink/30 hover:scale-[1.02] shadow-[0_0_15px_rgba(255,0,127,0.3)] px-8 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all cursor-pointer">
            <i data-lucide="save" class="w-4 h-4 inline-block align-middle mr-1"></i> Save Settings
        </button>
    </div>
</form>
@endsection
