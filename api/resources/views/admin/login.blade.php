<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>666Win - Admin Login Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        'neon-cyan': '#00f2fe',
                        'neon-pink': '#ff007f',
                        'bg-dark': '#090a0f',
                    }
                }
            }
        }
    </script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-[#090a0f] text-slate-200 font-sans min-h-screen flex items-center justify-center p-4 antialiased overflow-hidden relative">

    <!-- Cyberpunk ambient glows -->
    <div class="absolute w-[500px] h-[500px] rounded-full bg-neon-cyan/5 blur-[120px] -top-40 -left-40 animate-pulse pointer-events-none"></div>
    <div class="absolute w-[500px] h-[500px] rounded-full bg-neon-pink/5 blur-[120px] -bottom-40 -right-40 animate-pulse pointer-events-none"></div>

    <div class="w-full max-w-md bg-[#121623]/60 rounded-[30px] border border-white/10 backdrop-blur-md p-8 md:p-10 relative overflow-hidden shadow-[0_0_50px_rgba(0,0,0,0.5)]">
        
        <!-- Subtle glow border effect -->
        <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-neon-cyan to-transparent opacity-50"></div>

        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 bg-neon-cyan/15 rounded-2xl border border-neon-cyan/20 text-neon-cyan mb-4 shadow-[0_0_15px_rgba(0,242,254,0.15)] animate-pulse">
                <i data-lucide="shield-alert" class="w-7 h-7"></i>
            </div>
            <h2 class="text-2xl font-black text-white italic tracking-wider uppercase mb-1 flex items-center justify-center gap-1.5">
                666WIN <span class="text-neon-cyan">ADMIN</span>
            </h2>
            <p class="text-xs text-slate-400 font-semibold uppercase tracking-widest">Secure Control Portal</p>
        </div>

        @if($errors->any())
            <div class="mb-6 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl px-4 py-3 text-xs font-bold flex items-center gap-2">
                <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-400 shrink-0"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form action="{{ route('admin.login.submit') }}" method="POST" class="space-y-6">
            @csrf
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Username</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500">
                        <i data-lucide="user" class="w-4 h-4"></i>
                    </span>
                    <input type="text" name="username" required
                        class="w-full bg-[#090a0f] border border-white/10 rounded-2xl py-3.5 pl-11 pr-4 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-neon-cyan focus:border-neon-cyan focus:shadow-[0_0_15px_rgba(0,242,254,0.15)] transition-all font-semibold"
                        placeholder="Admin username">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Password</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500">
                        <i data-lucide="lock" class="w-4 h-4"></i>
                    </span>
                    <input type="password" name="password" required
                        class="w-full bg-[#090a0f] border border-white/10 rounded-2xl py-3.5 pl-11 pr-4 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-neon-cyan focus:border-neon-cyan focus:shadow-[0_0_15px_rgba(0,242,254,0.15)] transition-all font-semibold"
                        placeholder="••••••••">
                </div>
            </div>

            <button type="submit"
                class="w-full bg-neon-pink text-white border border-neon-pink/30 hover:scale-[1.02] shadow-[0_0_20px_rgba(255,0,127,0.3)] hover:shadow-[0_0_30px_rgba(255,0,127,0.5)] font-black uppercase tracking-widest py-3.5 rounded-2xl text-xs transition-all duration-300 flex items-center justify-center gap-2 cursor-pointer mt-8">
                <i data-lucide="log-in" class="w-4 h-4"></i>
                Sign In to Panel
            </button>
        </form>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
    </script>
</body>
</html>
