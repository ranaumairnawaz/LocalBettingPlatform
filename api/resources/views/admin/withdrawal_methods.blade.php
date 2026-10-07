@extends('admin.layout')
@section('content')
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-white italic tracking-wider mb-2 flex items-center gap-2">
                <span class="bg-neon-pink/20 w-2.5 h-8 rounded-full inline-block shadow-[0_0_15px_rgba(255,0,127,0.5)]"></span>
                WITHDRAWAL METHODS
            </h1>
            <p class="text-slate-400 text-sm">Manage gateways and credentials required for user payouts.</p>
        </div>

        <!-- Add Method Form -->
        <form action="{{ route('admin.withdrawal_methods.store') }}" method="POST"
            class="flex flex-col sm:flex-row gap-2 bg-[#121623]/60 p-3 rounded-[20px] border border-white/10 backdrop-blur-md w-full md:w-auto">
            @csrf
            <div class="relative flex-1 min-w-[200px]">
                <i data-lucide="plus-circle" class="w-4 h-4 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="name"
                    class="w-full bg-[#090a0f] border border-white/5 rounded-xl py-2 pl-9 pr-3 text-sm focus:outline-none focus:ring-1 focus:ring-neon-pink focus:border-neon-pink transition-all text-slate-200 font-semibold"
                    placeholder="Method (e.g. EasyPaisa)" required>
            </div>
            <div class="relative flex-1 min-w-[250px]">
                <input type="text" name="required_fields"
                    class="w-full bg-[#090a0f] border border-white/5 rounded-xl py-2 px-3 text-sm focus:outline-none focus:ring-1 focus:ring-neon-pink focus:border-neon-pink transition-all text-slate-200 font-semibold"
                    placeholder="Fields (e.g. Account Name, Number)" required>
            </div>
            <button
                class="bg-neon-pink hover:bg-neon-pink/95 text-white px-5 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all whitespace-nowrap shadow-[0_0_15px_rgba(255,0,127,0.25)] cursor-pointer">
                Add Method
            </button>
        </form>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        @foreach($methods as $method)
            <div class="bg-[#121623]/60 rounded-[20px] border border-white/10 shadow-sm flex flex-col overflow-hidden backdrop-blur-md">
                <div class="bg-[#090a0f]/50 border-b border-white/5 p-5 flex justify-between items-center">
                    <h3 class="font-black text-white italic text-lg flex items-center gap-2 uppercase tracking-wide">
                        <i data-lucide="wallet" class="w-5 h-5 text-neon-pink animate-pulse"></i>
                        {{ $method->name }}
                    </h3>
                    <div class="flex items-center gap-2">
                        @if($method->is_active)
                            <span class="bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider shadow-[0_0_10px_rgba(16,185,129,0.1)]">
                                Active
                            </span>
                        @else
                            <span class="bg-rose-500/10 text-rose-400 border border-rose-500/20 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider">
                                Inactive
                            </span>
                        @endif
                    </div>
                </div>
                
                <div class="p-6 flex-1 flex flex-col justify-between gap-6">
                    <div>
                        <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Required Credentials</h4>
                        <div class="flex flex-wrap gap-2">
                            @foreach($method->required_fields as $field)
                                <span class="bg-white/5 border border-white/10 text-slate-300 px-3 py-1.5 rounded-xl text-xs font-mono font-bold flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-neon-pink"></span>
                                    {{ $field }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <div class="pt-4 border-t border-white/5 flex justify-end items-center gap-3">
                        <!-- Toggle active status -->
                        <form action="{{ route('admin.withdrawal_methods.toggle', $method->id) }}" method="POST">
                            @csrf
                            <button class="bg-white/5 hover:bg-white/10 text-slate-300 hover:text-white border border-white/5 px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-colors cursor-pointer">
                                @if($method->is_active)
                                    Deactivate
                                @else
                                    Activate
                                @endif
                            </button>
                        </form>

                        <!-- Delete method -->
                        <form action="{{ route('admin.withdrawal_methods.delete', $method->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this withdrawal method? This will unbind all associated user accounts.');">
                            @csrf
                            <button class="bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/25 px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-colors cursor-pointer">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach

        @if($methods->count() == 0)
            <div class="col-span-1 xl:col-span-2 bg-[#121623]/60 rounded-[20px] border border-white/10 shadow-sm p-12 text-center flex flex-col items-center justify-center backdrop-blur-md">
                <div class="bg-[#090a0f] w-20 h-20 rounded-2xl border border-white/10 flex items-center justify-center mb-4 text-slate-600">
                    <i data-lucide="wallet" class="w-10 h-10"></i>
                </div>
                <h4 class="text-xl font-bold text-white mb-2">No Withdrawal Methods Configured</h4>
                <p class="text-slate-400 text-sm max-w-md">Add your first withdrawal gate (e.g. EasyPaisa) and required fields (e.g. Account Title, Account Number) in the top right to start receiving requests.</p>
            </div>
        @endif
    </div>
@endsection
