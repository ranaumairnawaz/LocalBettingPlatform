@extends('admin.layout')
@section('content')
    <div class="mb-8">
        <a href="{{ route('admin.super_agents.challenges.index') }}" class="text-neon-cyan hover:text-neon-cyan/80 text-sm font-black uppercase tracking-wider mb-4 inline-flex items-center gap-2 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Back to Challenges
        </a>

        <h1 class="text-3xl font-black text-white italic tracking-wider mt-4">
            {{ isset($challenge) ? 'Edit Challenge' : 'Create New Challenge' }}
        </h1>
    </div>

    <div class="bg-[#121623]/60 rounded-[20px] border border-white/10 backdrop-blur-md p-8 max-w-2xl">
        <form action="{{ isset($challenge) ? route('admin.super_agents.challenges.update', $challenge->id) : route('admin.super_agents.challenges.store') }}" method="POST" class="space-y-6">
            @csrf
            @if(isset($challenge))
                @method('PUT')
            @endif

            <div>
                <label class="block text-sm font-black text-white uppercase tracking-wider mb-2">Challenge Title</label>
                <input type="text" name="title" value="{{ $challenge->title ?? '' }}" required 
                    class="w-full bg-[#090a0f] border border-white/5 rounded-xl py-3 px-4 text-white focus:outline-none focus:ring-1 focus:ring-neon-cyan font-semibold">
                @error('title')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-black text-white uppercase tracking-wider mb-2">Description</label>
                <textarea name="description" rows="3" required 
                    class="w-full bg-[#090a0f] border border-white/5 rounded-xl py-3 px-4 text-white focus:outline-none focus:ring-1 focus:ring-neon-cyan font-semibold">{{ $challenge->description ?? '' }}</textarea>
                @error('description')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-black text-white uppercase tracking-wider mb-2">Challenge Type</label>
                <select name="challenge_type" required class="w-full bg-[#090a0f] border border-white/5 rounded-xl py-3 px-4 text-white focus:outline-none focus:ring-1 focus:ring-neon-cyan font-semibold">
                    <option value="">Select Type</option>
                    <option value="team_signups" {{ ($challenge->challenge_type ?? '') === 'team_signups' ? 'selected' : '' }}>Team Signups</option>
                    <option value="team_deposits" {{ ($challenge->challenge_type ?? '') === 'team_deposits' ? 'selected' : '' }}>Team Deposits</option>
                    <option value="team_games_played" {{ ($challenge->challenge_type ?? '') === 'team_games_played' ? 'selected' : '' }}>Team Games Played</option>
                </select>
                @error('challenge_type')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-black text-white uppercase tracking-wider mb-2">Target Value</label>
                    <input type="number" name="target_value" value="{{ $challenge->target_value ?? '' }}" required 
                        class="w-full bg-[#090a0f] border border-white/5 rounded-xl py-3 px-4 text-white focus:outline-none focus:ring-1 focus:ring-neon-cyan font-semibold">
                    @error('target_value')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-black text-white uppercase tracking-wider mb-2">Reward Amount (Rs.)</label>
                    <input type="number" name="reward_amount" value="{{ $challenge->reward_amount ?? '' }}" step="0.01" required 
                        class="w-full bg-[#090a0f] border border-white/5 rounded-xl py-3 px-4 text-white focus:outline-none focus:ring-1 focus:ring-neon-cyan font-semibold">
                    @error('reward_amount')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-black text-white uppercase tracking-wider mb-2">Start Date</label>
                    <input type="datetime-local" name="starts_at" value="{{ isset($challenge) ? $challenge->starts_at->format('Y-m-d\TH:i') : '' }}" required 
                        class="w-full bg-[#090a0f] border border-white/5 rounded-xl py-3 px-4 text-white focus:outline-none focus:ring-1 focus:ring-neon-cyan font-semibold">
                    @error('starts_at')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-black text-white uppercase tracking-wider mb-2">End Date</label>
                    <input type="datetime-local" name="ends_at" value="{{ isset($challenge) ? $challenge->ends_at->format('Y-m-d\TH:i') : '' }}" required 
                        class="w-full bg-[#090a0f] border border-white/5 rounded-xl py-3 px-4 text-white focus:outline-none focus:ring-1 focus:ring-neon-cyan font-semibold">
                    @error('ends_at')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ (isset($challenge) && $challenge->is_active) ? 'checked' : '' }}
                        class="w-5 h-5 rounded border border-white/20 bg-[#090a0f] accent-neon-cyan cursor-pointer">
                    <span class="text-sm font-black text-white uppercase tracking-wider">Active Challenge</span>
                </label>
            </div>

            <div class="flex gap-3 pt-6 border-t border-white/10">
                <button type="submit" class="flex-1 bg-neon-cyan hover:bg-neon-cyan/95 text-black px-6 py-3 rounded-xl font-black uppercase tracking-widest transition-all shadow-[0_0_10px_rgba(0,242,254,0.2)]">
                    {{ isset($challenge) ? 'Update Challenge' : 'Create Challenge' }}
                </button>
                <a href="{{ route('admin.super_agents.challenges.index') }}" class="flex-1 bg-white/5 hover:bg-white/10 text-slate-300 px-6 py-3 rounded-xl font-black uppercase tracking-widest transition-all text-center">
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection
