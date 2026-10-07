@extends('admin.layout')

@section('content')
    <div class="mb-8">
        <h1 class="text-3xl font-black text-white italic tracking-wider mb-2 flex items-center gap-2">
            <span class="bg-neon-cyan/20 w-2.5 h-8 rounded-full inline-block shadow-[0_0_15px_rgba(0,242,254,0.5)]"></span>
            DASHBOARD
        </h1>
        <p class="text-slate-400 text-sm">Monitor platform metrics, manage transactions, and accomplish your tasks with ease.</p>
    </div>

    <!-- Key Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Metric 1: Total Deposits -->
        <div class="bg-[#121623]/60 backdrop-blur-md rounded-[20px] p-6 border border-white/5 border-l-4 border-l-neon-cyan shadow-[0_0_20px_rgba(0,242,254,0.05)] relative overflow-hidden group hover:scale-[1.02] transition-all duration-300">
            <div class="absolute -right-4 -bottom-4 opacity-5 text-neon-cyan group-hover:scale-110 transition-transform duration-300">
                <i data-lucide="arrow-down-circle" class="w-24 h-24"></i>
            </div>
            <div class="absolute right-6 top-6 bg-neon-cyan/15 w-8 h-8 rounded-xl flex items-center justify-center border border-neon-cyan/20">
                <i data-lucide="trending-up" class="w-4 h-4 text-neon-cyan animate-pulse"></i>
            </div>
            <p class="text-slate-400 font-bold uppercase tracking-wider text-xs mb-3">Total Deposits</p>
            <h2 class="text-3xl font-black text-neon-cyan font-mono italic tracking-wide mb-4">Rs. {{ number_format($stats['total_deposits']) }}</h2>
            <div class="flex items-center gap-2 text-xs">
                <span class="bg-neon-cyan/20 px-2 py-0.5 rounded text-neon-cyan font-bold flex items-center gap-1">
                    +12%
                </span>
                <span class="text-slate-400 font-medium">from last month</span>
            </div>
        </div>

        <!-- Metric 2: Total Withdrawals -->
        <div class="bg-[#121623]/60 backdrop-blur-md rounded-[20px] p-6 border border-white/5 border-l-4 border-l-neon-pink shadow-[0_0_20px_rgba(255,0,127,0.05)] relative overflow-hidden group hover:scale-[1.02] transition-all duration-300">
            <div class="absolute -right-4 -bottom-4 opacity-5 text-neon-pink group-hover:scale-110 transition-transform duration-300">
                <i data-lucide="arrow-up-circle" class="w-24 h-24"></i>
            </div>
            <div class="absolute right-6 top-6 bg-neon-pink/15 w-8 h-8 rounded-xl flex items-center justify-center border border-neon-pink/20">
                <i data-lucide="trending-down" class="w-4 h-4 text-neon-pink animate-pulse"></i>
            </div>
            <p class="text-slate-400 font-bold uppercase tracking-wider text-xs mb-3">Total Withdrawals</p>
            <h2 class="text-3xl font-black text-neon-pink font-mono italic tracking-wide mb-4">Rs. {{ number_format($stats['total_withdrawals']) }}</h2>
            <div class="flex items-center gap-2 text-xs">
                <span class="bg-neon-pink/20 px-2 py-0.5 rounded text-neon-pink font-bold flex items-center gap-1">
                    -4%
                </span>
                <span class="text-slate-400 font-medium">stable since last week</span>
            </div>
        </div>

        <!-- Metric 3: Commission Earned -->
        <div class="bg-[#121623]/60 backdrop-blur-md rounded-[20px] p-6 border border-white/5 border-l-4 border-l-neon-gold shadow-[0_0_20px_rgba(255,183,0,0.05)] relative overflow-hidden group hover:scale-[1.02] transition-all duration-300">
            <div class="absolute -right-4 -bottom-4 opacity-5 text-neon-gold group-hover:scale-110 transition-transform duration-300">
                <i data-lucide="dollar-sign" class="w-24 h-24"></i>
            </div>
            <div class="absolute right-6 top-6 bg-neon-gold/15 w-8 h-8 rounded-xl flex items-center justify-center border border-neon-gold/20">
                <i data-lucide="dollar-sign" class="w-4 h-4 text-neon-gold animate-pulse"></i>
            </div>
            <p class="text-slate-400 font-bold uppercase tracking-wider text-xs mb-3">Commission Earned</p>
            <h2 class="text-3xl font-black text-neon-gold font-mono italic tracking-wide mb-4">Rs. {{ number_format($stats['total_commission']) }}</h2>
            <div class="flex items-center gap-2 text-xs">
                <span class="bg-neon-gold/20 px-2 py-0.5 rounded text-neon-gold font-bold flex items-center gap-1">
                    +8%
                </span>
                <span class="text-slate-400 font-medium">from last month</span>
            </div>
        </div>

        <!-- Metric 4: Active Users -->
        <div class="bg-[#121623]/60 backdrop-blur-md rounded-[20px] p-6 border border-white/5 border-l-4 border-l-[#39ff14] shadow-[0_0_20px_rgba(57,255,20,0.05)] relative overflow-hidden group hover:scale-[1.02] transition-all duration-300">
            <div class="absolute -right-4 -bottom-4 opacity-5 text-[#39ff14] group-hover:scale-110 transition-transform duration-300">
                <i data-lucide="users" class="w-24 h-24"></i>
            </div>
            <div class="absolute right-6 top-6 bg-[#39ff14]/15 w-8 h-8 rounded-xl flex items-center justify-center border border-[#39ff14]/20">
                <i data-lucide="users" class="w-4 h-4 text-[#39ff14] animate-pulse"></i>
            </div>
            <p class="text-slate-400 font-bold uppercase tracking-wider text-xs mb-3">Active Users</p>
            <h2 class="text-3xl font-black text-[#39ff14] font-mono italic tracking-wide mb-4">{{ $stats['active_users'] }}</h2>
            <div class="flex items-center gap-2 text-xs">
                <span class="w-2 h-2 bg-[#39ff14] rounded-full animate-ping"></span>
                <span class="text-slate-400 font-semibold tracking-wide uppercase text-[10px]">Currently Engaging</span>
            </div>
        </div>
    </div>

    <!-- Secondary Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-[#121623]/60 border border-white/10 backdrop-blur-md rounded-[20px] p-6 text-white flex flex-col justify-between">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-black text-sm uppercase tracking-widest italic text-neon-cyan">Platform Financials</h3>
                <span class="text-[9px] bg-neon-cyan/20 border border-neon-cyan/30 px-3 py-1 rounded-xl font-black uppercase tracking-wider text-neon-cyan shadow-[0_0_10px_rgba(0,242,254,0.15)] animate-pulse">
                    LIVE DATA
                </span>
            </div>

            <!-- Chart Canvas -->
            <div class="relative w-full h-64">
                <canvas id="analyticsChart"></canvas>
            </div>
        </div>

        <div class="flex flex-col gap-6">
            <!-- Reminders / Quick Actions -->
            <div class="bg-[#121623]/60 border border-white/10 backdrop-blur-md rounded-[20px] p-6 text-white flex-1 flex flex-col justify-between">
                <div>
                    <h3 class="font-black text-sm uppercase tracking-widest italic text-neon-pink mb-4">Quick Actions</h3>
                    <div class="mb-4 bg-[#090a0f]/60 p-4 rounded-2xl border border-white/5">
                        <h4 class="text-sm font-black text-white italic uppercase tracking-wider">Review Transactions</h4>
                        <p class="text-xs text-slate-400 mt-1">Approvals and payout requests are waiting in the queue.</p>
                    </div>
                </div>
                <a href="{{ route('admin.transactions') }}"
                    class="mt-4 w-full bg-neon-pink text-white border border-neon-pink/30 hover:scale-[1.02] shadow-[0_0_15px_rgba(255,0,127,0.3)] hover:shadow-[0_0_25px_rgba(255,0,127,0.5)] font-black uppercase tracking-wider py-3 rounded-xl flex items-center justify-center gap-2 transition-all cursor-pointer text-xs">
                    <i data-lucide="file-check-2" class="w-4 h-4"></i>
                    Review Now
                </a>
            </div>

            <!-- Project Progress (Mock Cyberpunk Donut) -->
            <div class="bg-[#121623]/60 border border-white/10 backdrop-blur-md rounded-[20px] p-6 text-white flex-1">
                <h3 class="font-black text-sm uppercase tracking-widest italic text-neon-cyan mb-4">Platform Health</h3>
                <div class="relative w-32 h-32 mx-auto">
                    <!-- SVG Donut Chart Mock -->
                    <svg viewBox="0 0 36 36" class="w-full h-full transform -rotate-90">
                        <!-- Background Circle -->
                        <path class="text-white/5" stroke-width="4.5" stroke="currentColor" fill="none"
                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <!-- Neon Cyan Segment -->
                        <path class="text-neon-cyan drop-shadow-[0_0_5px_rgba(0,242,254,0.5)]" stroke-dasharray="99, 100" stroke-width="4.5" stroke-linecap="round"
                            stroke="currentColor" fill="none"
                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-2xl font-black text-neon-cyan font-mono italic">99%</span>
                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">Uptime</span>
                    </div>
                </div>
                <div class="flex justify-center gap-4 mt-5 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-neon-cyan shadow-[0_0_10px_rgba(0,242,254,0.8)] animate-pulse"></span>
                        Online
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-white/10"></span>
                        Offline
                    </div>
            </div>
        </div>
    </div>

    <!-- Chart.js CDN & Neon Area Config -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const ctx = document.getElementById('analyticsChart').getContext('2d');
            
            // Neon Cyan Gradient
            const depositGradient = ctx.createLinearGradient(0, 0, 0, 240);
            depositGradient.addColorStop(0, 'rgba(0, 242, 254, 0.25)');
            depositGradient.addColorStop(1, 'rgba(0, 242, 254, 0.0)');
            
            // Neon Pink Gradient
            const withdrawalGradient = ctx.createLinearGradient(0, 0, 0, 240);
            withdrawalGradient.addColorStop(0, 'rgba(255, 0, 127, 0.25)');
            withdrawalGradient.addColorStop(1, 'rgba(255, 0, 127, 0.0)');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($chart_labels) !!},
                    datasets: [
                        {
                            label: 'Deposits (Rs.)',
                            data: {!! json_encode($chart_deposits) !!},
                            borderColor: '#00f2fe',
                            borderWidth: 3,
                            backgroundColor: depositGradient,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#00f2fe',
                            pointBorderColor: '#090a0f',
                            pointHoverRadius: 6,
                            pointRadius: 4
                        },
                        {
                            label: 'Withdrawals (Rs.)',
                            data: {!! json_encode($chart_withdrawals) !!},
                            borderColor: '#ff007f',
                            borderWidth: 3,
                            backgroundColor: withdrawalGradient,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#ff007f',
                            pointBorderColor: '#090a0f',
                            pointHoverRadius: 6,
                            pointRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: {
                                color: '#94a3b8',
                                boxWidth: 12,
                                boxHeight: 4,
                                padding: 15,
                                font: {
                                    family: 'Inter',
                                    size: 10,
                                    weight: '800'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: '#121623',
                            titleColor: '#00f2fe',
                            titleFont: {
                                weight: 'bold',
                                family: 'Inter'
                            },
                            bodyColor: '#ffffff',
                            bodyFont: {
                                family: 'Inter',
                                size: 11
                            },
                            borderColor: 'rgba(255, 255, 255, 0.05)',
                            borderWidth: 1,
                            padding: 10,
                            displayColors: true,
                            callbacks: {
                                label: function(context) {
                                    return ' ' + context.dataset.label.split(' ')[0] + ': Rs. ' + Number(context.raw).toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.04)',
                            },
                            ticks: {
                                color: '#64748b',
                                font: {
                                    family: 'Inter',
                                    size: 9,
                                    weight: 'bold'
                                }
                            }
                        },
                        y: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.04)',
                            },
                            ticks: {
                                color: '#64748b',
                                font: {
                                    family: 'Inter',
                                size: 9,
                                    weight: 'bold'
                                },
                                callback: function(value) {
                                    return 'Rs. ' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
@endsection