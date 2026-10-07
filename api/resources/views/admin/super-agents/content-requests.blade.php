@extends('admin.layout')
@section('content')
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-black text-white italic tracking-wider mb-2 flex items-center gap-2">
                <span class="bg-neon-gold/20 w-2.5 h-8 rounded-full inline-block shadow-[0_0_15px_rgba(255,183,0,0.5)]"></span>
                CONTENT BONUSES
            </h1>
            <p class="text-slate-400 text-sm">Approve or reject video content submissions.</p>
        </div>

        <form action="{{ route('admin.super_agents.content_requests') }}" method="GET" class="flex flex-wrap gap-2 w-full md:w-auto bg-[#121623]/60 p-3 rounded-[20px] border border-white/10 backdrop-blur-md">
            <select name="status" class="bg-[#090a0f] border border-white/5 rounded-xl py-2 px-3 text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-neon-cyan font-semibold">
                <option value="">All Statuses</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>

            <select name="platform" class="bg-[#090a0f] border border-white/5 rounded-xl py-2 px-3 text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-neon-cyan font-semibold">
                <option value="">All Platforms</option>
                <option value="youtube" {{ request('platform') == 'youtube' ? 'selected' : '' }}>YouTube</option>
                <option value="instagram" {{ request('platform') == 'instagram' ? 'selected' : '' }}>Instagram</option>
                <option value="tiktok" {{ request('platform') == 'tiktok' ? 'selected' : '' }}>TikTok</option>
            </select>

            <button type="submit" class="bg-neon-cyan hover:bg-neon-cyan/95 text-black px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all shadow-[0_0_10px_rgba(0,242,254,0.2)] cursor-pointer">Filter</button>
            <a href="{{ route('admin.super_agents.content_requests') }}" class="bg-white/5 hover:bg-white/10 text-slate-300 px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex items-center justify-center cursor-pointer">Reset</a>
        </form>
    </div>

    <div class="grid gap-6">
        @forelse($contentRequests as $request)
            <div class="bg-[#121623]/60 rounded-[20px] border border-white/10 backdrop-blur-md p-6 hover:border-white/20 transition-all">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 mb-4">
                    <div class="flex gap-4">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($request->user->username) }}&background=121623&color=ffb700"
                            class="w-12 h-12 rounded-xl border border-white/10">
                        <div>
                            <h3 class="text-lg font-black text-white italic tracking-wider uppercase">{{ $request->user->username }}</h3>
                            <p class="text-xs text-slate-400">{{ ucfirst($request->platform) }} • {{ $request->status }}</p>
                            <p class="text-xs text-neon-gold font-black mt-1">
                                <i data-lucide="eye" class="w-3 h-3 inline"></i> 
                                {{ number_format($request->view_count ?? 0) }} views
                            </p>
                        </div>
                    </div>
                    <span class="px-4 py-1 rounded-full text-xs font-black uppercase tracking-widest border w-fit
                        {{ $request->status === 'approved' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : '' }}
                        {{ $request->status === 'rejected' ? 'bg-red-500/10 text-red-400 border-red-500/20' : '' }}
                        {{ $request->status === 'pending' ? 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20' : '' }}
                    ">
                        {{ ucfirst($request->status) }}
                    </span>
                </div>

                <div class="mb-4">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Video URL</p>
                    <p class="text-sm text-slate-300 font-mono break-all">
                        <a href="{{ $request->video_url }}" target="_blank" class="text-neon-cyan hover:text-neon-cyan/80 transition-colors">
                            {{ $request->video_url }}
                            <i data-lucide="external-link" class="w-3 h-3 inline ml-1"></i>
                        </a>
                    </p>
                </div>

                @if($request->screenshot_path)
                    <div class="mb-4">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Screenshot</p>
                        <img src="{{ asset('storage/' . $request->screenshot_path) }}" alt="Screenshot" class="w-full md:w-96 h-auto rounded-xl border border-white/10">
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4 bg-[#090a0f]/40 rounded-xl p-4">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Views</p>
                        <p class="text-2xl font-black text-neon-cyan">{{ number_format($request->view_count ?? 0) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Estimated Bonus</p>
                        <p class="text-2xl font-black text-neon-gold font-mono">Rs. {{ number_format($request->bonus_amount ?? 0, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Submitted</p>
                        <p class="text-sm text-slate-300">{{ $request->created_at->format('M d, Y H:i') }}</p>
                    </div>
                </div>

                @if($request->status === 'pending')
                    <div class="flex gap-3 pt-4 border-t border-white/10">
                        <button onclick="approveContent({{ $request->id }})" class="flex-1 bg-emerald-500/20 hover:bg-emerald-500/40 text-emerald-400 px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all border border-emerald-500/20">Approve</button>
                        <button onclick="rejectContent({{ $request->id }})" class="flex-1 bg-red-500/20 hover:bg-red-500/40 text-red-400 px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all border border-red-500/20">Reject</button>
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-[#121623]/60 rounded-[20px] border border-white/10 backdrop-blur-md p-12 text-center">
                <p class="text-slate-400">No content requests found.</p>
            </div>
        @endforelse
    </div>

    @if($contentRequests->hasPages())
        <div class="mt-6 flex justify-center">
            {{ $contentRequests->links() }}
        </div>
    @endif

    <script>
        function approveContent(id) {
            let amount = prompt('Enter bonus amount (Rs.):');
            if (amount && !isNaN(amount)) {
                fetch(`/admin/super-agents/content/${id}/approve`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ bonus_amount: amount })
                }).then(r => r.json()).then(d => {
                    if (d.success) window.location.reload();
                    else alert('Error: ' + d.message);
                });
            }
        }

        function rejectContent(id) {
            if (confirm('Are you sure you want to reject this content?')) {
                fetch(`/admin/super-agents/content/${id}/reject`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                }).then(r => r.json()).then(d => {
                    if (d.success) window.location.reload();
                    else alert('Error: ' + d.message);
                });
            }
        }
    </script>
@endsection
