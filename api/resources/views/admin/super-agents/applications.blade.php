@extends('admin.layout')
@section('content')
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-black text-white italic tracking-wider mb-2 flex items-center gap-2">
                <span class="bg-neon-pink/20 w-2.5 h-8 rounded-full inline-block shadow-[0_0_15px_rgba(255,0,127,0.5)]"></span>
                SA APPLICATIONS
            </h1>
            <p class="text-slate-400 text-sm">Review and approve Super Agent applications.</p>
        </div>

        <form action="{{ route('admin.super_agents.applications') }}" method="GET" class="flex flex-wrap gap-2 w-full md:w-auto bg-[#121623]/60 p-3 rounded-[20px] border border-white/10 backdrop-blur-md">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search username..." class="bg-[#090a0f] border border-white/5 rounded-xl py-2 px-3 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-neon-cyan min-w-[150px] flex-1">

            <select name="status" class="bg-[#090a0f] border border-white/5 rounded-xl py-2 px-3 text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-neon-cyan font-semibold">
                <option value="">All Statuses</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>

            <button type="submit" class="bg-neon-cyan hover:bg-neon-cyan/95 text-black px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all shadow-[0_0_10px_rgba(0,242,254,0.2)] cursor-pointer">Filter</button>
            <a href="{{ route('admin.super_agents.applications') }}" class="bg-white/5 hover:bg-white/10 text-slate-300 px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex items-center justify-center cursor-pointer">Reset</a>
        </form>
    </div>

    @if(session('success'))
        <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-4 text-emerald-400 text-sm mb-6">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 text-red-400 text-sm mb-6">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid gap-6">
        @forelse($applications as $user)
            <div class="bg-[#121623]/60 rounded-[20px] border border-white/10 backdrop-blur-md p-6 hover:border-white/20 transition-all">

                {{-- Header row --}}
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                    <div class="flex items-center gap-4">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($user->username) }}&background=121623&color=00f2fe"
                            class="w-14 h-14 rounded-xl border border-white/10">
                        <div>
                            <h3 class="text-lg font-black text-white italic tracking-wider uppercase">{{ $user->username }}</h3>
                            <p class="text-xs text-slate-400">{{ $user->email }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Applied: {{ $user->created_at?->format('M d, Y H:i') ?? 'N/A' }}
                            </p>
                        </div>
                    </div>
                    <span class="px-4 py-1 rounded-full text-xs font-black uppercase tracking-widest border w-fit
                        {{ $user->super_agent_application_status === 'approved' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : '' }}
                        {{ $user->super_agent_application_status === 'rejected' ? 'bg-red-500/10 text-red-400 border-red-500/20' : '' }}
                        {{ $user->super_agent_application_status === 'pending' ? 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20' : '' }}
                    ">
                        {{ ucfirst($user->super_agent_application_status) }}
                    </span>
                </div>

                {{-- Details grid --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 bg-[#090a0f]/40 rounded-xl p-4">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Team Name</p>
                        <p class="text-sm text-white font-semibold">{{ $user->super_agent_team_name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Group Proof Path</p>
                        <p class="text-xs text-slate-300 font-mono break-all">{{ $user->super_agent_group_proof ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Application Note</p>
                        <p class="text-sm text-slate-300">{{ $user->super_agent_application_note ?? '—' }}</p>
                    </div>
                </div>

                {{-- Screenshot preview --}}
                @if($user->super_agent_group_proof)
                    <div class="mb-6">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Group Proof Screenshot</p>
                        <a href="{{ asset('storage/' . $user->super_agent_group_proof) }}" target="_blank">
                            <img src="{{ asset('storage/' . $user->super_agent_group_proof) }}"
                                 alt="Group Proof"
                                 class="w-full md:w-80 h-auto rounded-xl border border-white/10 object-cover hover:opacity-90 transition-opacity cursor-zoom-in">
                        </a>
                        <p class="text-[10px] text-slate-500 mt-1">Click image to open full size</p>
                    </div>
                @endif

                {{-- Approve / Reject actions (pending only) --}}
                @if($user->super_agent_application_status === 'pending')
                    <div class="flex flex-col md:flex-row gap-3 pt-4 border-t border-white/10">
                        {{-- Approve --}}
                        <form action="{{ route('admin.super_agents.applications.approve', $user->id) }}" method="POST" class="flex-1">
                            @csrf
                            <button type="submit"
                                class="w-full bg-emerald-500/20 hover:bg-emerald-500/40 text-emerald-400 px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all border border-emerald-500/20">
                                ✓ Approve &amp; Promote to Super Agent
                            </button>
                        </form>

                        {{-- Reject (inline expand) --}}
                        <div class="flex-1">
                            <details class="group">
                                <summary class="w-full bg-red-500/20 hover:bg-red-500/40 text-red-400 px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all border border-red-500/20 cursor-pointer list-none text-center">
                                    ✕ Reject
                                </summary>
                                <form action="{{ route('admin.super_agents.applications.reject', $user->id) }}" method="POST" class="mt-3 space-y-2">
                                    @csrf
                                    <textarea name="rejection_note" rows="2" required placeholder="Enter rejection reason..."
                                        class="w-full bg-[#090a0f] border border-red-500/20 rounded-xl py-2 px-3 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-red-400 resize-none"></textarea>
                                    <button type="submit"
                                        class="w-full bg-red-500/30 hover:bg-red-500/50 text-red-300 px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all border border-red-500/30">
                                        Confirm Rejection
                                    </button>
                                </form>
                            </details>
                        </div>
                    </div>
                @endif

            </div>
        @empty
            <div class="bg-[#121623]/60 rounded-[20px] border border-white/10 backdrop-blur-md p-12 text-center">
                <p class="text-slate-400">No applications found.</p>
            </div>
        @endforelse
    </div>

    @if($applications->hasPages())
        <div class="mt-6 flex justify-center">
            {{ $applications->links() }}
        </div>
    @endif
@endsection
