@extends('admin.layout')
@section('content')
    <div class="mb-8">
        <a href="{{ route('admin.super_agents.index') }}" class="text-neon-cyan hover:text-neon-cyan/80 text-sm font-black uppercase tracking-wider mb-4 inline-flex items-center gap-2 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Back to Super Agents
        </a>

        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mt-4">
            <div class="flex items-center gap-4">
                <img src="https://ui-avatars.com/api/?name={{ urlencode($superAgent->username) }}&background=121623&color=00f2fe"
                    class="w-16 h-16 rounded-2xl border-2 border-neon-cyan/30">
                <div>
                    <h1 class="text-3xl font-black text-white italic tracking-wider">{{ $superAgent->username }}</h1>
                    <p class="text-slate-400 text-sm">Team: {{ $superAgent->super_agent_team_name ?? 'Not Set' }}</p>
                    <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-widest border mt-2 inline-block
                        {{ $superAgent->super_agent_rank === 'diamond' ? 'bg-blue-500/10 text-blue-400 border-blue-500/20' : '' }}
                        {{ $superAgent->super_agent_rank === 'platinum' ? 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20' : '' }}
                        {{ $superAgent->super_agent_rank === 'gold' ? 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20' : '' }}
                        {{ $superAgent->super_agent_rank === 'silver' ? 'bg-gray-500/10 text-gray-400 border-gray-500/20' : '' }}
                        {{ $superAgent->super_agent_rank === 'bronze' ? 'bg-orange-500/10 text-orange-400 border-orange-500/20' : '' }}
                    ">
                        {{ ucfirst($superAgent->super_agent_rank) }} Level
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-[#121623]/60 rounded-[20px] border border-white/10 backdrop-blur-md p-6">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Team Size</p>
            <p class="text-3xl font-black text-neon-cyan">{{ $stats['team_size'] }}</p>
        </div>
        <div class="bg-[#121623]/60 rounded-[20px] border border-white/10 backdrop-blur-md p-6">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Total Earnings</p>
            <p class="text-2xl font-black text-neon-cyan font-mono">Rs. {{ number_format($stats['total_earnings'] ?? 0, 2) }}</p>
        </div>
        <div class="bg-[#121623]/60 rounded-[20px] border border-white/10 backdrop-blur-md p-6">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">This Month</p>
            <p class="text-2xl font-black text-emerald-400 font-mono">Rs. {{ number_format($stats['this_month_earnings'] ?? 0, 2) }}</p>
        </div>
        <div class="bg-[#121623]/60 rounded-[20px] border border-white/10 backdrop-blur-md p-6">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Approved Date</p>
            <p class="text-sm font-black text-slate-200">{{ $superAgent->super_agent_approved_at ? $superAgent->super_agent_approved_at->format('M d, Y') : '-' }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Team Members -->
        <div class="lg:col-span-2 bg-[#121623]/60 rounded-[20px] border border-white/10 backdrop-blur-md p-6">
            <h2 class="text-lg font-black text-white italic tracking-wider mb-4 uppercase flex items-center gap-2">
                <span class="bg-neon-cyan/20 w-1.5 h-6 rounded-full"></span>
                Team Members
            </h2>
            <div class="space-y-3 max-h-96 overflow-y-auto">
                @forelse($recruits as $member)
                    <div class="flex items-center justify-between p-3 bg-[#090a0f]/40 rounded-lg">
                        <div class="flex items-center gap-3">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($member->username) }}&background=121623&color=ff007f"
                                class="w-8 h-8 rounded-full border border-white/10">
                            <div>
                                <p class="text-sm font-black text-white uppercase">{{ $member->username }}</p>
                                <p class="text-[10px] text-slate-400">Joined {{ $member->created_at->format('M d, Y') }}</p>
                            </div>
                        </div>
                        <span class="px-2 py-1 rounded-full text-[9px] font-black uppercase tracking-wider border border-neon-cyan/20 bg-neon-cyan/10 text-neon-cyan">Member</span>
                    </div>
                @empty
                    <p class="text-slate-400 text-sm text-center py-4">No team members yet.</p>
                @endforelse
            </div>
        </div>

        <!-- Actions -->
        <div class="bg-[#121623]/60 rounded-[20px] border border-white/10 backdrop-blur-md p-6">
            <h2 class="text-lg font-black text-white italic tracking-wider mb-4 uppercase flex items-center gap-2">
                <span class="bg-neon-pink/20 w-1.5 h-6 rounded-full"></span>
                Actions
            </h2>
            <div class="space-y-3">
                <form action="{{ route('admin.super_agents.demote', $superAgent->id) }}" method="POST" onsubmit="return confirm('Demote this Super Agent?');">
                    @csrf
                    <button type="submit" class="w-full bg-rose-500/20 hover:bg-rose-500/40 text-rose-400 px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all border border-rose-500/20 cursor-pointer">
                        Revoke Super Agent Status
                    </button>
                </form>

                <form action="{{ route('admin.super_agents.regenerate_code', $superAgent->id) }}" method="POST" onsubmit="return confirm('Regenerate referral code?');">
                    @csrf
                    <button type="submit" class="w-full bg-neon-cyan/20 hover:bg-neon-cyan/40 text-neon-cyan px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all border border-neon-cyan/20 cursor-pointer">
                        Regenerate Code
                    </button>
                </form>
            </div>

            <div class="mt-6 p-4 bg-[#090a0f]/40 rounded-lg border border-white/5">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Referral Code</p>
                <p class="text-sm font-mono text-neon-cyan font-black break-all">{{ $superAgent->super_agent_referral_code ?? '-' }}</p>
            </div>
        </div>
    </div>

    <!-- Recent Commissions -->
    <div class="bg-[#121623]/60 rounded-[20px] border border-white/10 backdrop-blur-md p-6">
        <h2 class="text-lg font-black text-white italic tracking-wider mb-4 uppercase flex items-center gap-2">
            <span class="bg-neon-gold/20 w-1.5 h-6 rounded-full"></span>
            Recent Commissions
        </h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-white/10 text-slate-400">
                        <th class="py-3 px-4 font-black uppercase tracking-wider">Source</th>
                        <th class="py-3 px-4 font-black uppercase tracking-wider">Type</th>
                        <th class="py-3 px-4 font-black uppercase tracking-wider">Amount</th>
                        <th class="py-3 px-4 font-black uppercase tracking-wider">Level</th>
                        <th class="py-3 px-4 font-black uppercase tracking-wider">Status</th>
                        <th class="py-3 px-4 font-black uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($superAgent->superAgentCommissions()->latest()->limit(10)->get() as $comm)
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3 px-4">{{ $comm->sourceUser?->username ?? 'N/A' }}</td>
                            <td class="py-3 px-4 capitalize">{{ $comm->commission_type }}</td>
                            <td class="py-3 px-4 font-mono font-black text-neon-cyan">Rs. {{ number_format($comm->commission_amount, 2) }}</td>
                            <td class="py-3 px-4 font-black">L{{ $comm->referral_level }}</td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 rounded-full text-[9px] font-black uppercase tracking-wider border {{ $comm->is_paid ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20' }}">
                                    {{ $comm->is_paid ? 'Paid' : 'Pending' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-slate-400">{{ $comm->created_at->format('M d, H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 px-4 text-center text-slate-400">No commissions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
