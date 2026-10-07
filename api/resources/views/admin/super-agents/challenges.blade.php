@extends('admin.layout')
@section('content')
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-black text-white italic tracking-wider mb-2 flex items-center gap-2">
                <span class="bg-neon-pink/20 w-2.5 h-8 rounded-full inline-block shadow-[0_0_15px_rgba(255,0,127,0.5)]"></span>
                SA CHALLENGES
            </h1>
            <p class="text-slate-400 text-sm">Create and manage Super Agent challenge missions.</p>
        </div>

        <a href="{{ route('admin.super_agents.challenges.create') }}" class="bg-neon-cyan hover:bg-neon-cyan/95 text-black px-6 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all shadow-[0_0_10px_rgba(0,242,254,0.2)] flex items-center gap-2 cursor-pointer">
            + New Challenge
        </a>
    </div>

    @if(session('success'))
        <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-4 text-emerald-400 text-sm mb-6">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 text-red-400 text-sm mb-6">{{ session('error') }}</div>
    @endif

    {{-- Stats Bar --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-[#121623]/60 rounded-[20px] border border-white/10 backdrop-blur-md p-6">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Active Now</p>
            <p class="text-3xl font-black text-emerald-400">{{ $stats['active'] }}</p>
        </div>
        <div class="bg-[#121623]/60 rounded-[20px] border border-white/10 backdrop-blur-md p-6">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Upcoming</p>
            <p class="text-3xl font-black text-yellow-400">{{ $stats['upcoming'] }}</p>
        </div>
        <div class="bg-[#121623]/60 rounded-[20px] border border-white/10 backdrop-blur-md p-6">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Total Prize Pool</p>
            <p class="text-2xl font-black text-neon-gold font-mono">Rs. {{ number_format($challenges->sum('reward_amount'), 2) }}</p>
        </div>
    </div>

    {{-- Active Challenges --}}
    <div class="mb-8">
        <h2 class="text-xl font-black text-white italic tracking-wider mb-4 uppercase flex items-center gap-2">
            <span class="bg-emerald-500/20 w-1.5 h-6 rounded-full"></span>
            Active Challenges
        </h2>
        <div class="grid gap-4">
            @forelse($active as $challenge)
                <div class="bg-[#121623]/60 rounded-[20px] border border-emerald-500/20 backdrop-blur-md p-6 hover:border-emerald-500/40 transition-all">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
                        <div>
                            <h3 class="text-lg font-black text-white italic tracking-wider uppercase">{{ $challenge->title }}</h3>
                            <p class="text-sm text-slate-400 mt-1">{{ $challenge->description }}</p>
                            <p class="text-xs text-slate-500 mt-1">
                                {{ $challenge->starts_at->format('M d') }} → {{ $challenge->ends_at->format('M d, Y H:i') }}
                            </p>
                        </div>
                        <span class="px-4 py-1 rounded-full text-xs font-black uppercase tracking-widest border border-emerald-500/20 bg-emerald-500/10 text-emerald-400 w-fit">
                            Active
                        </span>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4 bg-[#090a0f]/40 rounded-xl p-4">
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Type</p>
                            <p class="text-sm text-white font-semibold capitalize">{{ str_replace('_', ' ', $challenge->challenge_type) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Target</p>
                            <p class="text-sm font-black text-neon-cyan">{{ number_format($challenge->target_value) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Prize</p>
                            <p class="text-sm font-black text-neon-gold font-mono">Rs. {{ number_format($challenge->reward_amount, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Participants</p>
                            <p class="text-sm font-black text-neon-pink">{{ $challenge->entries()->count() }}</p>
                        </div>
                    </div>

                    <div class="flex gap-3 pt-4 border-t border-white/10">
                        <a href="{{ route('admin.super_agents.challenges.edit', $challenge->id) }}"
                           class="flex-1 bg-neon-cyan/20 hover:bg-neon-cyan/40 text-neon-cyan px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all border border-neon-cyan/20 text-center">Edit</a>
                        <form action="{{ route('admin.super_agents.challenges.toggle', $challenge->id) }}" method="POST" class="flex-1">
                            @csrf
                            <button type="submit" class="w-full bg-red-500/20 hover:bg-red-500/40 text-red-400 px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all border border-red-500/20">Deactivate</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="bg-[#121623]/60 rounded-[20px] border border-white/10 backdrop-blur-md p-8 text-center">
                    <p class="text-slate-400">No active challenges.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Upcoming Challenges --}}
    <div class="mb-8">
        <h2 class="text-xl font-black text-white italic tracking-wider mb-4 uppercase flex items-center gap-2">
            <span class="bg-yellow-500/20 w-1.5 h-6 rounded-full"></span>
            Upcoming Challenges
        </h2>
        <div class="grid gap-4">
            @forelse($upcoming as $challenge)
                <div class="bg-[#121623]/60 rounded-[20px] border border-yellow-500/20 backdrop-blur-md p-6 hover:border-yellow-500/40 transition-all">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
                        <div>
                            <h3 class="text-lg font-black text-white italic tracking-wider uppercase">{{ $challenge->title }}</h3>
                            <p class="text-sm text-slate-400 mt-1">Starts: {{ $challenge->starts_at->format('M d, Y H:i') }}</p>
                            <p class="text-xs text-slate-500">Target: {{ number_format($challenge->target_value) }} • Prize: Rs. {{ number_format($challenge->reward_amount, 2) }}</p>
                        </div>
                        <span class="px-4 py-1 rounded-full text-xs font-black uppercase tracking-widest border border-yellow-500/20 bg-yellow-500/10 text-yellow-400 w-fit">
                            Upcoming
                        </span>
                    </div>

                    <div class="flex gap-3 pt-4 border-t border-white/10">
                        <a href="{{ route('admin.super_agents.challenges.edit', $challenge->id) }}"
                           class="flex-1 bg-neon-cyan/20 hover:bg-neon-cyan/40 text-neon-cyan px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all border border-neon-cyan/20 text-center">Edit</a>
                        <form action="{{ route('admin.super_agents.challenges.destroy', $challenge->id) }}" method="POST" class="flex-1" onsubmit="return confirm('Delete this challenge?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full bg-red-500/20 hover:bg-red-500/40 text-red-400 px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all border border-red-500/20">Delete</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="bg-[#121623]/60 rounded-[20px] border border-white/10 backdrop-blur-md p-8 text-center">
                    <p class="text-slate-400">No upcoming challenges.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Completed / Ended Challenges --}}
    <div>
        <h2 class="text-xl font-black text-white italic tracking-wider mb-4 uppercase flex items-center gap-2">
            <span class="bg-slate-500/20 w-1.5 h-6 rounded-full"></span>
            Ended Challenges
        </h2>
        <div class="grid gap-4">
            @forelse($completed as $challenge)
                <div class="bg-[#121623]/60 rounded-[20px] border border-slate-500/20 backdrop-blur-md p-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-black text-white italic tracking-wider uppercase">{{ $challenge->title }}</h3>
                            <p class="text-sm text-slate-400 mt-1">Ended: {{ $challenge->ends_at->format('M d, Y H:i') }}</p>
                            <p class="text-xs text-slate-500">
                                Prize: Rs. {{ number_format($challenge->reward_amount, 2) }} •
                                Participants: {{ $challenge->entries()->count() }} •
                                Completed: {{ $challenge->entries()->whereNotNull('completed_at')->count() }}
                            </p>
                        </div>
                        <span class="px-4 py-1 rounded-full text-xs font-black uppercase tracking-widest border border-slate-500/20 bg-slate-500/10 text-slate-400 w-fit">
                            Ended
                        </span>
                    </div>
                </div>
            @empty
                <div class="bg-[#121623]/60 rounded-[20px] border border-white/10 backdrop-blur-md p-8 text-center">
                    <p class="text-slate-400">No ended challenges yet.</p>
                </div>
            @endforelse
        </div>
    </div>

    @if($challenges->hasPages())
        <div class="mt-6 flex justify-center">
            {{ $challenges->links() }}
        </div>
    @endif
@endsection
