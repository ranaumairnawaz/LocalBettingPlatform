@extends('admin.layout')
@section('content')
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-black text-white italic tracking-wider mb-2 flex items-center gap-2">
                <span class="bg-rose-500/20 w-2.5 h-8 rounded-full inline-block shadow-[0_0_15px_rgba(244,63,94,0.5)] animate-pulse"></span>
                SECURITY AUDITING
            </h1>
            <p class="text-slate-400 text-sm">Scan and mitigate multi-account registration and referral program exploitation.</p>
        </div>
        
        <div class="bg-rose-500/10 border border-rose-500/20 px-4 py-2 rounded-2xl flex items-center gap-3 shadow-[0_0_15px_rgba(244,63,94,0.05)]">
            <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-ping"></span>
            <span class="text-xs text-rose-400 font-black uppercase tracking-widest">Real-time IP matching active</span>
        </div>
    </div>

    @if(count($ipGroups) > 0)
        <div class="space-y-6">
            @foreach($ipGroups as $group)
                <div class="bg-[#121623]/60 rounded-[20px] border border-white/10 overflow-hidden backdrop-blur-md shadow-lg group hover:border-rose-500/30 transition-all duration-300">
                    <!-- Group Header -->
                    <div class="bg-[#090a0f]/80 px-6 py-4 border-b border-white/10 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-rose-500/15 border border-rose-500/20 text-rose-400 flex items-center justify-center">
                                <i data-lucide="shield-alert" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <span class="font-mono text-white font-black tracking-wide text-sm select-all">{{ $group['ip'] }}</span>
                                <span class="block text-[9px] font-bold text-slate-500 uppercase tracking-widest mt-0.5">Shared Login Address</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-end">
                            <span class="bg-rose-500/10 text-rose-400 border border-rose-500/20 px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider">
                                {{ $group['count'] }} MATCHED USERS
                            </span>
                            
                            <form action="{{ route('admin.fraud_detect.batch_ban') }}" method="POST" onsubmit="return confirm('Are you sure you want to ban ALL users sharing IP {{ $group['ip'] }}?');">
                                @csrf
                                <input type="hidden" name="ip" value="{{ $group['ip'] }}">
                                <button type="submit" class="bg-rose-500 hover:bg-rose-600 text-white font-black text-[10px] uppercase tracking-widest px-4 py-2 rounded-xl transition-all cursor-pointer shadow-[0_0_15px_rgba(244,63,94,0.3)] hover:scale-[1.02] flex items-center gap-1.5">
                                    <i data-lucide="ban" class="w-3.5 h-3.5"></i> Ban IP Group
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Group Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-[#090a0f]/30 border-b border-white/5 text-slate-400">
                                    <th class="py-3 px-6 text-[9px] font-black uppercase tracking-widest">Player</th>
                                    <th class="py-3 px-6 text-[9px] font-black uppercase tracking-widest">Current Balance</th>
                                    <th class="py-3 px-6 text-[9px] font-black uppercase tracking-widest">Referral Link</th>
                                    <th class="py-3 px-6 text-[9px] font-black uppercase tracking-widest font-mono">Registered</th>
                                    <th class="py-3 px-6 text-[9px] font-black uppercase tracking-widest">Status</th>
                                    <th class="py-3 px-6 text-[9px] font-black uppercase tracking-widest text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                @foreach($group['users'] as $user)
                                    <tr class="hover:bg-white/5 transition-colors">
                                        <td class="py-4 px-6">
                                            <div class="flex items-center gap-3">
                                                <img src="https://ui-avatars.com/api/?name={{ urlencode($user->username) }}&background=121623&color=f43f5e"
                                                    class="w-7 h-7 rounded-full border border-white/10">
                                                <div>
                                                    <a href="{{ route('admin.users.show', $user->id) }}" class="font-black text-white italic uppercase tracking-wider text-xs hover:text-rose-400 transition-colors">{{ $user->username }}</a>
                                                    <span class="block text-[9px] text-slate-500 font-semibold mt-0.5">{{ $user->email ?? 'No Email' }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6 font-black text-rose-400 font-mono italic text-xs">
                                            Rs. {{ number_format($user->balance) }}
                                        </td>
                                        <td class="py-4 px-6">
                                            <span class="font-mono bg-[#090a0f] border border-white/5 px-2 py-0.5 rounded text-slate-400 text-[10px] select-all w-max block">
                                                {{ $user->referral_code ?? 'NONE' }}
                                            </span>
                                            @if($user->referred_by)
                                                <span class="block text-[8px] text-slate-500 uppercase font-black tracking-widest mt-1">Referred By ID: {{ $user->referred_by }}</span>
                                            @endif
                                        </td>
                                        <td class="py-4 px-6 text-xs text-slate-400 font-mono">
                                            {{ $user->created_at->format('d M Y, h:i A') }}
                                        </td>
                                        <td class="py-4 px-6">
                                            @if($user->is_banned)
                                                <span class="text-[8px] font-black bg-rose-500/10 text-rose-400 border border-rose-500/20 px-2 py-0.5 rounded uppercase tracking-wider">Banned</span>
                                            @else
                                                <span class="text-[8px] font-black bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-2 py-0.5 rounded uppercase tracking-wider">Active</span>
                                            @endif
                                        </td>
                                        <td class="py-4 px-6 text-right">
                                            <form action="{{ route('admin.users.ban', $user->id) }}" method="POST" class="inline">
                                                @csrf
                                                @if($user->is_banned)
                                                    <button type="submit" class="bg-white/5 border border-white/10 hover:bg-white/10 text-white px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all cursor-pointer">
                                                        Unban
                                                    </button>
                                                @else
                                                    <button type="submit" class="bg-rose-500/10 text-rose-400 border border-rose-500/25 hover:bg-rose-500/20 px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all cursor-pointer">
                                                        Ban Player
                                                    </button>
                                                @endif
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <!-- Security Audit Empty State -->
        <div class="bg-[#121623]/60 rounded-[20px] border border-white/10 p-16 text-center backdrop-blur-md">
            <div class="flex flex-col items-center justify-center">
                <div class="w-16 h-16 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center mb-4 shadow-[0_0_20px_rgba(16,185,129,0.15)] animate-pulse">
                    <i data-lucide="shield-check" class="w-8 h-8"></i>
                </div>
                <h2 class="text-xl font-black text-white italic uppercase tracking-wider">No Threats Detected</h2>
                <p class="text-xs text-slate-400 mt-2 max-w-md mx-auto">All active player logins are operating under unique network addresses. No duplicate IP groupings found in the player database.</p>
            </div>
        </div>
    @endif
@endsection
