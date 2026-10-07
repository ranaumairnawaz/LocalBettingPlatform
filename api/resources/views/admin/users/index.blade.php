@extends('admin.layout')

@section('content')
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-black text-white italic tracking-wider mb-2 flex items-center gap-2">
                <span
                    class="bg-neon-cyan/20 w-2.5 h-8 rounded-full inline-block shadow-[0_0_15px_rgba(0,242,254,0.5)]"></span>
                USERS DIRECTORY
            </h1>
            <p class="text-slate-400 text-sm">View and manage platform users, their balances, and access status.</p>
        </div>
    </div>

    <div class="bg-[#121623]/60 rounded-[20px] border border-white/10 shadow-sm overflow-hidden backdrop-blur-md">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-[#090a0f]/60 border-b border-white/10 text-slate-400">
                        <th class="py-4 px-6 text-[10px] font-black uppercase tracking-widest text-slate-400">User</th>
                        <th class="py-4 px-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Balance</th>
                        <th class="py-4 px-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Activity</th>
                        <th class="py-4 px-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Status</th>
                        <th class="py-4 px-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Joined</th>
                        <th class="py-4 px-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">
                            Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($users as $user)
                        <tr class="hover:bg-white/5 transition-colors border-b border-white/5">
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $user->avatar ? asset('storage/' . $user->avatar) : 'https://api.dicebear.com/7.x/initials/svg?seed=' . urlencode($user->username) }}"
                                        class="w-10 h-10 rounded-full border border-white/10" alt="User Avatar">
                                    <div>
                                        <span
                                            class="block font-black text-white italic uppercase tracking-wider text-xs">{{ $user->username }}</span>
                                        <span class="text-xs text-slate-400">{{ $user->email ?? 'No email provided' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6 font-black text-white font-mono italic text-sm">
                                Rs. {{ number_format($user->balance) }}
                            </td>
                            <td class="py-4 px-6">
                                <div class="text-xs text-slate-300 font-semibold space-y-0.5">
                                    <div><span class="font-bold text-neon-cyan font-mono">{{ $user->transactions_count }}</span>
                                        Tx</div>
                                    <div><span class="font-bold text-neon-pink font-mono">{{ $user->game_logs_count }}</span>
                                        Plays</div>
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                @if($user->is_banned)
                                    <span
                                        class="bg-rose-500/10 text-rose-400 border border-rose-500/20 px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest">Banned</span>
                                @else
                                    <span
                                        class="bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest shadow-[0_0_10px_rgba(16,185,129,0.1)]">Active</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-400 font-mono">
                                {{ $user->created_at->format('M d, Y') }}
                            </td>
                            <td class="py-4 px-6 text-right">
                                <a href="{{ route('admin.users.show', $user->id) }}"
                                    class="inline-flex items-center gap-1 bg-neon-cyan hover:bg-neon-cyan/95 text-black px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all cursor-pointer shadow-[0_0_10px_rgba(0,242,254,0.25)]">
                                    <i data-lucide="eye" class="w-3.5 h-3.5 inline-block align-middle mr-0.5"></i> View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i data-lucide="users" class="w-10 h-10 text-slate-700 mb-3"></i>
                                    <p class="text-sm font-bold">No users found.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div class="p-4 border-t border-white/5 bg-[#090a0f]/60">
                {{ $users->links('pagination::tailwind') }}
            </div>
        @endif
    </div>
@endsection