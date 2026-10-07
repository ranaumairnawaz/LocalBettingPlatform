@extends('admin.layout')

@section('content')
<div class="mb-8 flex justify-between items-center">
    <div>
        <h1 class="text-3xl font-black text-white italic tracking-wider mb-2 flex items-center gap-2">
            <span class="bg-neon-cyan/20 w-2.5 h-8 rounded-full inline-block shadow-[0_0_15px_rgba(0,242,254,0.5)]"></span>
            GAMES MANAGEMENT
        </h1>
        <p class="text-slate-400 text-sm">Select a game to configure its settings, betting limits, guidelines, and view its performance.</p>
    </div>
</div>

@if(session('success'))
<div class="mb-6 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl px-4 py-3 text-sm font-bold flex items-center gap-2 shadow-[0_0_15px_rgba(16,185,129,0.1)]">
    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-400"></i>
    {{ session('success') }}
</div>
@endif

<div class="bg-[#121623]/60 rounded-[20px] border border-white/10 shadow-sm overflow-hidden backdrop-blur-md">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-[#090a0f]/80 border-b border-white/5 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                    <th class="py-4 px-6">ID</th>
                    <th class="py-4 px-6">Logo</th>
                    <th class="py-4 px-6">Game Name</th>
                    <th class="py-4 px-6">Status</th>
                    <th class="py-4 px-6 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5 text-sm font-medium text-slate-200">
                @foreach($games as $game)
                <tr class="hover:bg-white/[0.02] transition-colors {{ !$game->is_active ? 'opacity-60' : '' }}">
                    <td class="py-4 px-6 text-slate-400 font-mono">#{{ $game->id }}</td>
                    <td class="py-4 px-6">
                        <div class="w-12 h-12 bg-[#090a0f] rounded-xl border border-white/10 flex items-center justify-center overflow-hidden">
                            @if($game->image)
                                <img src="{{ asset('storage/' . $game->image) }}" class="w-full h-full object-cover">
                            @else
                                <i data-lucide="gamepad-2" class="w-5 h-5 text-slate-600"></i>
                            @endif
                        </div>
                    </td>
                    <td class="py-4 px-6">
                        <div class="font-bold text-white italic tracking-wide uppercase">{{ $game->name }}</div>
                        <div class="text-[10px] text-neon-cyan font-mono tracking-wider">{{ $game->slug }}</div>
                    </td>
                    <td class="py-4 px-6">
                        @if($game->is_active)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-black bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shadow-[0_0_10px_rgba(16,185,129,0.1)]">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                LIVE
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-black bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                OFFLINE
                            </span>
                        @endif
                    </td>
                    <td class="py-4 px-6 text-right">
                        <a href="{{ route('admin.games.show', $game->id) }}" class="inline-flex items-center gap-2 bg-neon-cyan/10 hover:bg-neon-cyan/20 text-neon-cyan border border-neon-cyan/30 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">
                            <i data-lucide="settings-2" class="w-3.5 h-3.5"></i>
                            Settings & Overview
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
