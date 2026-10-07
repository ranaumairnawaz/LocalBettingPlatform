@extends('admin.layout')
@section('content')
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-black text-white italic tracking-wider mb-2 flex items-center gap-2">
                <span class="bg-neon-cyan/20 w-2.5 h-8 rounded-full inline-block shadow-[0_0_15px_rgba(0,242,254,0.5)]"></span>
                SUPER AGENTS
            </h1>
            <p class="text-slate-400 text-sm">Manage elite users, promotions, and performance.</p>
        </div>

        <form action="{{ route('admin.super_agents.index') }}" method="GET" class="flex flex-wrap gap-2 w-full md:w-auto bg-[#121623]/60 p-3 rounded-[20px] border border-white/10 backdrop-blur-md">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search username, team..." class="bg-[#090a0f] border border-white/5 rounded-xl py-2 px-3 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-neon-cyan min-w-[150px] flex-1">
            
            <select name="rank" class="bg-[#090a0f] border border-white/5 rounded-xl py-2 px-3 text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-neon-cyan font-semibold">
                <option value="">All Ranks</option>
                <option value="bronze" {{ request('rank') == 'bronze' ? 'selected' : '' }}>Bronze</option>
                <option value="silver" {{ request('rank') == 'silver' ? 'selected' : '' }}>Silver</option>
                <option value="gold" {{ request('rank') == 'gold' ? 'selected' : '' }}>Gold</option>
                <option value="diamond" {{ request('rank') == 'diamond' ? 'selected' : '' }}>Diamond</option>
                <option value="platinum" {{ request('rank') == 'platinum' ? 'selected' : '' }}>Platinum</option>
            </select>

            <button type="submit" class="bg-neon-cyan hover:bg-neon-cyan/95 text-black px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all shadow-[0_0_10px_rgba(0,242,254,0.2)] cursor-pointer">Filter</button>
            <a href="{{ route('admin.super_agents.index') }}" class="bg-white/5 hover:bg-white/10 text-slate-300 px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex items-center justify-center cursor-pointer">Reset</a>
        </form>
    </div>

    <div class="bg-[#121623]/60 rounded-[20px] border border-white/10 shadow-sm overflow-hidden backdrop-blur-md">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-[#090a0f]/60 border-b border-white/10 text-slate-400">
                        <th class="py-4 px-6 text-[10px] font-black uppercase tracking-widest text-slate-400">User</th>
                        <th class="py-4 px-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Team Name</th>
                        <th class="py-4 px-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Rank</th>
                        <th class="py-4 px-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Team Size</th>
                        <th class="py-4 px-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Earnings</th>
                        <th class="py-4 px-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Joined</th>
                        <th class="py-4 px-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($superAgents as $agent)
                        <tr class="hover:bg-white/5 transition-colors border-b border-white/5">
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($agent->username) }}&background=121623&color=00f2fe"
                                        class="w-8 h-8 rounded-full border border-white/10">
                                    <span class="font-black text-white italic uppercase tracking-wider text-xs">{{ $agent->username }}</span>
                                </div>
                            </td>
                            <td class="py-4 px-6 text-sm text-slate-300 font-semibold">
                                {{ $agent->super_agent_team_name ?? '-' }}
                            </td>
                            <td class="py-4 px-6">
                                <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest border 
                                    {{ $agent->super_agent_rank === 'diamond' ? 'bg-blue-500/10 text-blue-400 border-blue-500/20' : '' }}
                                    {{ $agent->super_agent_rank === 'platinum' ? 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20' : '' }}
                                    {{ $agent->super_agent_rank === 'gold' ? 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20' : '' }}
                                    {{ $agent->super_agent_rank === 'silver' ? 'bg-gray-500/10 text-gray-400 border-gray-500/20' : '' }}
                                    {{ $agent->super_agent_rank === 'bronze' ? 'bg-orange-500/10 text-orange-400 border-orange-500/20' : '' }}
                                ">
                                    {{ ucfirst($agent->super_agent_rank) }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-sm font-black text-neon-cyan">
                                {{ \App\Models\User::where('referred_by', $agent->id)->count() }}
                            </td>
                            <td class="py-4 px-6 font-black text-neon-cyan font-mono italic text-sm">
                                Rs. {{ number_format($agent->superAgentCommissions()->sum('commission_amount') ?? 0, 2) }}
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-400 font-semibold">
                                {{ $agent->super_agent_approved_at ? $agent->super_agent_approved_at->format('M d, Y') : '-' }}
                            </td>
                            <td class="py-4 px-6 text-right space-x-2">
                                <a href="{{ route('admin.super_agents.show', $agent->id) }}" class="inline-block bg-neon-cyan/20 text-neon-cyan hover:bg-neon-cyan/40 px-3 py-1 rounded-lg text-xs font-black uppercase tracking-wider transition-all">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 px-6 text-center text-slate-400">
                                <p class="text-sm">No Super Agents found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($superAgents->hasPages())
        <div class="mt-6 flex justify-center">
            {{ $superAgents->links() }}
        </div>
    @endif
@endsection
