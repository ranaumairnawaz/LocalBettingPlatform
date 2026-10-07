<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>666EGame Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        'neon-cyan': '#00f2fe',
                        'neon-pink': '#ff007f',
                        'neon-gold': '#ffb700',
                        'bg-dark': '#090a0f',
                        'glass': 'rgba(18, 22, 35, 0.6)'
                    }
                }
            }
        }
    </script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-[#090a0f] text-slate-100 font-sans antialiased overflow-hidden flex h-screen">

    <!-- Mobile Header -->
    <div
        class="md:hidden flex items-center justify-between bg-[#121623]/80 border-b border-white/5 p-4 fixed top-0 w-full z-20 backdrop-blur-md">
        <div class="flex items-center gap-2 text-neon-cyan font-black text-xl italic tracking-wider">
            <i data-lucide="gamepad-2" class="w-6 h-6"></i>
            666Win
        </div>
        <button id="mobileMenuBtn" class="text-slate-400 hover:text-slate-200">
            <i data-lucide="menu" class="w-6 h-6"></i>
        </button>
    </div>

    <!-- Sidebar -->
    <aside id="sidebar"
        class="bg-[#121623]/70 border-r border-white/5 w-64 flex-shrink-0 flex flex-col transition-transform duration-300 z-10 absolute md:relative h-full -translate-x-full md:translate-x-0 pt-16 md:pt-0 backdrop-blur-md">
        <div
            class="p-6 flex items-center gap-2 text-neon-cyan font-black text-2xl italic tracking-wider hidden md:flex">
            <i data-lucide="gamepad-2" class="w-8 h-8 text-neon-cyan animate-pulse"></i>
            666WIN
        </div>

        <div class="px-4 py-2">
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 ml-2">Main Menu</p>
            <nav class="space-y-1.5">
                <a href="{{ route('admin.dashboard') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-200 hover:bg-white/5 {{ request()->routeIs('admin.dashboard') ? 'text-neon-cyan bg-white/5 border-l-2 border-neon-cyan shadow-[0_0_15px_rgba(0,242,254,0.15)]' : 'text-slate-400 hover:text-slate-200 border-l-2 border-transparent' }}">
                    <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                    Dashboard
                </a>
                <a href="{{ route('admin.transactions') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-200 hover:bg-white/5 {{ request()->routeIs('admin.transactions') ? 'text-neon-cyan bg-white/5 border-l-2 border-neon-cyan shadow-[0_0_15px_rgba(0,242,254,0.15)]' : 'text-slate-400 hover:text-slate-200 border-l-2 border-transparent' }}">
                    <i data-lucide="list-todo" class="w-4 h-4"></i>
                    Transactions
                </a>
                <a href="{{ route('admin.transactions.pending_deposits') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-200 hover:bg-white/5 {{ request()->routeIs('admin.transactions.pending_deposits') ? 'text-neon-cyan bg-white/5 border-l-2 border-neon-cyan shadow-[0_0_15px_rgba(0,242,254,0.15)]' : 'text-slate-400 hover:text-slate-200 border-l-2 border-transparent' }}">
                    <i data-lucide="arrow-down-to-line" class="w-4 h-4"></i>
                    Pending Deposits
                </a>
                <a href="{{ route('admin.transactions.pending_withdrawals') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-200 hover:bg-white/5 {{ request()->routeIs('admin.transactions.pending_withdrawals') ? 'text-neon-cyan bg-white/5 border-l-2 border-neon-cyan shadow-[0_0_15px_rgba(0,242,254,0.15)]' : 'text-slate-400 hover:text-slate-200 border-l-2 border-transparent' }}">
                    <i data-lucide="arrow-up-from-line" class="w-4 h-4"></i>
                    Pending Withdrawals
                </a>
                <a href="{{ route('admin.deposit_methods') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-200 hover:bg-white/5 {{ request()->routeIs('admin.deposit_methods') ? 'text-neon-cyan bg-white/5 border-l-2 border-neon-cyan shadow-[0_0_15px_rgba(0,242,254,0.15)]' : 'text-slate-400 hover:text-slate-200 border-l-2 border-transparent' }}">
                    <i data-lucide="wallet" class="w-4 h-4"></i>
                    Deposit Methods
                </a>
                <a href="{{ route('admin.withdrawal_methods') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-200 hover:bg-white/5 {{ request()->routeIs('admin.withdrawal_methods') ? 'text-neon-cyan bg-white/5 border-l-2 border-neon-cyan shadow-[0_0_15px_rgba(0,242,254,0.15)]' : 'text-slate-400 hover:text-slate-200 border-l-2 border-transparent' }}">
                    <i data-lucide="wallet-cards" class="w-4 h-4"></i>
                    Withdrawal Methods
                </a>
                <a href="{{ route('admin.users') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-200 hover:bg-white/5 {{ request()->routeIs('admin.users*') ? 'text-neon-cyan bg-white/5 border-l-2 border-neon-cyan shadow-[0_0_15px_rgba(0,242,254,0.15)]' : 'text-slate-400 hover:text-slate-200 border-l-2 border-transparent' }}">
                    <i data-lucide="users" class="w-4 h-4"></i>
                    Users
                </a>
                <a href="{{ route('admin.fraud_detect') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-200 hover:bg-white/5 {{ request()->routeIs('admin.fraud_detect') ? 'text-neon-cyan bg-white/5 border-l-2 border-neon-cyan shadow-[0_0_15px_rgba(0,242,254,0.15)]' : 'text-slate-400 hover:text-slate-200 border-l-2 border-transparent' }}">
                    <i data-lucide="shield-alert" class="w-4 h-4"></i>
                    Security Audit
                </a>
                <a href="{{ route('admin.games') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-200 hover:bg-white/5 {{ request()->routeIs('admin.games*') ? 'text-neon-cyan bg-white/5 border-l-2 border-neon-cyan shadow-[0_0_15px_rgba(0,242,254,0.15)]' : 'text-slate-400 hover:text-slate-200 border-l-2 border-transparent' }}">
                    <i data-lucide="gamepad-2" class="w-4 h-4"></i>
                    Games
                </a>
            </nav>
        </div>

        <div class="px-4 py-2">
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 ml-2">Tournaments</p>
            <nav class="space-y-1.5">
                <a href="{{ route('admin.tournaments.scheduler') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-200 hover:bg-white/5 {{ request()->routeIs('admin.tournaments.scheduler') ? 'text-neon-cyan bg-white/5 border-l-2 border-neon-cyan shadow-[0_0_15px_rgba(0,242,254,0.15)]' : 'text-slate-400 hover:text-slate-200 border-l-2 border-transparent' }}">
                    <i data-lucide="trophy" class="w-4 h-4"></i>
                    Tournaments
                </a>
                <a href="{{ route('admin.tournaments.audit') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-200 hover:bg-white/5 {{ request()->routeIs('admin.tournaments.audit') ? 'text-neon-cyan bg-white/5 border-l-2 border-neon-cyan shadow-[0_0_15px_rgba(0,242,254,0.15)]' : 'text-slate-400 hover:text-slate-200 border-l-2 border-transparent' }}">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                    Tournament Audit
                </a>
            </nav>
        </div>

        <div class="px-4 py-2">
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 ml-2">Main Menu</p>
            <nav class="space-y-1.5">
                <a href="{{ route('admin.settings') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-200 hover:bg-white/5 {{ request()->routeIs('admin.settings*') ? 'text-neon-cyan bg-white/5 border-l-2 border-neon-cyan shadow-[0_0_15px_rgba(0,242,254,0.15)]' : 'text-slate-400 hover:text-slate-200 border-l-2 border-transparent' }}">
                    <i data-lucide="settings" class="w-4 h-4"></i>
                    Settings
                </a>
            </nav>
        </div>

        <div class="px-4 py-2">
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 ml-2">Super Agents</p>
            <nav class="space-y-1.5">
                <a href="{{ route('admin.super_agents.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-200 hover:bg-white/5 {{ request()->routeIs('admin.super_agents.index') ? 'text-neon-cyan bg-white/5 border-l-2 border-neon-cyan shadow-[0_0_15px_rgba(0,242,254,0.15)]' : 'text-slate-400 hover:text-slate-200 border-l-2 border-transparent' }}">
                    <i data-lucide="star" class="w-4 h-4"></i>
                    Super Agents
                </a>
                <a href="{{ route('admin.super_agents.applications') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-200 hover:bg-white/5 {{ request()->routeIs('admin.super_agents.applications') ? 'text-neon-cyan bg-white/5 border-l-2 border-neon-cyan shadow-[0_0_15px_rgba(0,242,254,0.15)]' : 'text-slate-400 hover:text-slate-200 border-l-2 border-transparent' }}">
                    <i data-lucide="inbox" class="w-4 h-4"></i>
                    Applications
                </a>
                <a href="{{ route('admin.super_agents.challenges.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-200 hover:bg-white/5 {{ request()->routeIs('admin.super_agents.challenges*') ? 'text-neon-cyan bg-white/5 border-l-2 border-neon-cyan shadow-[0_0_15px_rgba(0,242,254,0.15)]' : 'text-slate-400 hover:text-slate-200 border-l-2 border-transparent' }}">
                    <i data-lucide="trophy" class="w-4 h-4"></i>
                    Challenges
                </a>
                <a href="{{ route('admin.super_agents.content_requests') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-200 hover:bg-white/5 {{ request()->routeIs('admin.super_agents.content_requests') ? 'text-neon-cyan bg-white/5 border-l-2 border-neon-cyan shadow-[0_0_15px_rgba(0,242,254,0.15)]' : 'text-slate-400 hover:text-slate-200 border-l-2 border-transparent' }}">
                    <i data-lucide="video" class="w-4 h-4"></i>
                    Content Bonuses
                </a>
                <a href="{{ route('admin.super_agents.settings') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-200 hover:bg-white/5 {{ request()->routeIs('admin.super_agents.settings') ? 'text-neon-cyan bg-white/5 border-l-2 border-neon-cyan shadow-[0_0_15px_rgba(0,242,254,0.15)]' : 'text-slate-400 hover:text-slate-200 border-l-2 border-transparent' }}">
                    <i data-lucide="sliders" class="w-4 h-4"></i>
                    SA Settings
                </a>
            </nav>
        </div>

        <div class="px-4 py-4 mt-auto">
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 ml-2">General</p>
            <nav class="space-y-1.5">
                <a href="/" target="_blank"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:text-slate-100 text-xs font-black uppercase tracking-wider transition-all duration-200 hover:bg-white/5">
                    <i data-lucide="external-link" class="w-4 h-4"></i>
                    View Site
                </a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:text-neon-pink text-xs font-black uppercase tracking-wider transition-all duration-200 hover:bg-neon-pink/10 cursor-pointer">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                        Logout
                    </button>
                </form>
            </nav>

            <div class="mt-8 bg-white/5 border border-white/5 rounded-2xl p-4 text-white relative overflow-hidden">
                <div class="absolute -right-4 -bottom-4 opacity-5 text-neon-cyan">
                    <i data-lucide="shield-check" class="w-24 h-24"></i>
                </div>
                <div class="relative z-10">
                    <div class="bg-neon-cyan/20 w-8 h-8 rounded-lg flex items-center justify-center mb-3">
                        <i data-lucide="user-cog" class="w-4 h-4 text-neon-cyan"></i>
                    </div>
                    <h4 class="font-black mb-1 text-xs text-slate-200">Admin Control</h4>
                    <p class="text-[9px] text-slate-400 uppercase tracking-wider mb-3">Super user active</p>
                    <a href="{{ route('admin.settings') }}"
                        class="block text-center bg-neon-cyan text-black py-2 rounded-xl text-xs font-black transition-colors shadow-[0_0_15px_rgba(0,242,254,0.3)]">
                        Settings
                    </a>
                </div>
            </div>
        </div>
    </aside>

    <!-- Overlay for mobile -->
    <div id="mobileOverlay" class="fixed inset-0 bg-[#090a0f]/80 z-0 hidden md:hidden backdrop-blur-sm"></div>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full overflow-hidden w-full pt-16 md:pt-0">
        <!-- Top Navigation -->
        <header
            class="bg-[#121623]/70 border-b border-white/5 p-4 flex justify-between items-center z-10 hidden md:flex backdrop-blur-md">
            <div class="relative w-96">
                <i data-lucide="search" class="w-4 h-4 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2"></i>
                <input type="text" placeholder="Search logs..."
                    class="w-full bg-[#090a0f] border border-white/5 rounded-full py-2 pl-10 pr-4 text-sm text-slate-200 focus:outline-none focus:border-neon-cyan/50 transition-all">
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center gap-3 pl-4">
                    <div class="text-right hidden lg:block">
                        <p class="text-sm font-black text-slate-200 italic tracking-wider">
                            {{ auth()->user()->username ?? 'Admin User' }}
                        </p>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">
                            {{ auth()->user()->email ?? 'admin@666egame.com' }}
                        </p>
                    </div>
                    <div
                        class="w-10 h-10 rounded-xl bg-neon-cyan/20 border border-neon-cyan/30 flex items-center justify-center font-black text-neon-cyan">
                        {{ strtoupper(substr(auth()->user()->username ?? 'A', 0, 1)) }}
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content Area -->
        <div class="flex-1 overflow-y-auto p-4 md:p-8 bg-[#090a0f]">
            @if(session('success'))
                <div
                    class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl p-4 mb-6 flex items-center gap-3">
                    <i data-lucide="check-circle" class="w-5 h-5 text-emerald-400"></i>
                    <p class="text-sm font-bold">{{ session('success') }}</p>
                </div>
            @endif
            @if($errors->any())
                <div class="bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl p-4 mb-6">
                    <div class="flex items-center gap-3 mb-2">
                        <i data-lucide="alert-circle" class="w-5 h-5 text-rose-400"></i>
                        <p class="text-sm font-black">Please fix the following errors:</p>
                    </div>
                    <ul class="list-disc list-inside text-sm text-rose-300 ml-8 font-semibold">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Mobile Menu Toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobileOverlay');

        mobileMenuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        });

        overlay.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        });
    </script>
</body>

</html>