@extends('admin.layout')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<div x-data="auditApp()" x-init="init()" class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-white italic tracking-wider mb-2 flex items-center gap-2">
                <span class="bg-neon-pink/20 w-2.5 h-8 rounded-full inline-block shadow-[0_0_15px_rgba(255,0,127,0.5)]"></span>
                TOURNAMENT AUDIT
            </h1>
            <p class="text-slate-400 text-sm">Review platform commissions, prize payouts, bracket match logs, and house profitability.</p>
        </div>
        
        <!-- Tournament Selector -->
        <div class="flex items-center gap-3 w-full md:w-auto">
            <span class="text-slate-400 text-xs font-bold uppercase tracking-wider hidden sm:inline">Select Tournament:</span>
            <select
                x-model="selectedTournamentId"
                @change="changeTournament()"
                class="w-full md:w-64 bg-white/5 border border-white/10 rounded-xl px-4 py-2 text-white text-sm focus:outline-none focus:border-neon-cyan"
            >
                <option class="bg-[#090a0f]" value="">-- Select a tournament --</option>
                <template x-for="t in tournaments" :key="t.id">
                    <option class="bg-[#090a0f] text-white" :value="t.id" x-text="t.title + ' (' + statusLabel(t.status) + ')'"></option>
                </template>
            </select>
        </div>
    </div>

    <!-- Alert System -->
    <template x-if="errorMsg">
        <div class="bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl p-4 flex items-center gap-3">
            <i data-lucide="alert-circle" class="w-5 h-5 text-rose-400"></i>
            <p class="text-sm font-bold" x-text="errorMsg"></p>
        </div>
    </template>

    <!-- Loading State -->
    <div x-show="loading" class="flex flex-col items-center justify-center py-20 gap-3">
        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-neon-pink"></div>
        <span class="text-slate-400 text-xs font-bold uppercase tracking-wider">Loading Audit Data...</span>
    </div>

    <!-- No Tournament Selected -->
    <div x-show="!loading && !selectedTournamentId" class="bg-[#121623]/30 border border-white/5 rounded-3xl p-12 text-center backdrop-blur-md">
        <div class="bg-neon-pink/15 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-neon-pink/20">
            <i data-lucide="file-bar-chart-2" class="w-8 h-8 text-neon-pink animate-pulse"></i>
        </div>
        <h3 class="text-xl font-black text-white italic mb-2">NO TOURNAMENT AUDIT LOADED</h3>
        <p class="text-slate-400 max-w-md mx-auto text-sm">Choose a tournament from the dropdown in the top right to analyze its financial distribution, entry pool, and match results.</p>
    </div>

    <!-- Main Audit Dashboard -->
    <div x-show="!loading && selectedTournamentId && auditData" class="space-y-6" x-cloak>
        
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- 1. Total Pool -->
            <div class="bg-[#121623]/60 backdrop-blur-md rounded-[20px] p-5 border border-white/5 relative overflow-hidden group hover:scale-[1.02] transition-all">
                <div class="absolute -right-4 -bottom-4 opacity-5 text-neon-cyan">
                    <i data-lucide="wallet" class="w-20 h-20"></i>
                </div>
                <span class="text-slate-400 text-xs font-bold uppercase block mb-1">Total Entry Pool</span>
                <span class="text-2xl font-black text-white font-mono" x-text="'Rs. ' + parseFloat(auditData.financial_audit?.total_pool || 0).toLocaleString()">Rs. 0</span>
                <span class="text-[10px] text-slate-500 block mt-2" x-text="(auditData.tournament?.registered_players_count || 0) + ' Players Registered'"></span>
            </div>

            <!-- 2. Platform Commission -->
            <div class="bg-[#121623]/60 backdrop-blur-md rounded-[20px] p-5 border border-white/5 relative overflow-hidden group hover:scale-[1.02] transition-all">
                <div class="absolute -right-4 -bottom-4 opacity-5 text-neon-cyan">
                    <i data-lucide="percent" class="w-20 h-20"></i>
                </div>
                <span class="text-slate-400 text-xs font-bold uppercase block mb-1">Platform Commission</span>
                <span class="text-2xl font-black text-neon-cyan font-mono" x-text="'Rs. ' + parseFloat(auditData.financial_audit?.platform_commission?.amount || 0).toLocaleString()">Rs. 0</span>
                <span class="text-[10px] text-neon-cyan/80 block mt-2" x-text="'Rate: ' + parseFloat(auditData.financial_audit?.platform_commission?.percentage || 0) + '%'"></span>
            </div>

            <!-- 3. Distributed Prizes -->
            <div class="bg-[#121623]/60 backdrop-blur-md rounded-[20px] p-5 border border-white/5 relative overflow-hidden group hover:scale-[1.02] transition-all">
                <div class="absolute -right-4 -bottom-4 opacity-5 text-neon-pink">
                    <i data-lucide="gift" class="w-20 h-20"></i>
                </div>
                <span class="text-slate-400 text-xs font-bold uppercase block mb-1">Distributed Prizes</span>
                <span class="text-2xl font-black text-neon-pink font-mono" x-text="'Rs. ' + parseFloat(auditData.financial_audit?.total_distributed_prizes || 0).toLocaleString()">Rs. 0</span>
                <span class="text-[10px] text-slate-500 block mt-2" x-text="'Prize Pool: Rs. ' + parseFloat(auditData.financial_audit?.prize_pool || 0).toLocaleString()"></span>
            </div>

            <!-- 4. House Profit -->
            <div class="bg-[#121623]/60 backdrop-blur-md rounded-[20px] p-5 border border-white/5 relative overflow-hidden group hover:scale-[1.02] transition-all">
                <div class="absolute -right-4 -bottom-4 opacity-5 text-[#39ff14]">
                    <i data-lucide="trending-up" class="w-20 h-20"></i>
                </div>
                <span class="text-slate-400 text-xs font-bold uppercase block mb-1">Net House Profit</span>
                <span class="text-2xl font-black text-[#39ff14] font-mono animate-pulse" x-text="'Rs. ' + parseFloat(auditData.financial_audit?.house_profit?.total || 0).toLocaleString()">Rs. 0</span>
                <span class="text-[10px] text-[#39ff14]/80 block mt-2" x-text="'Margin: ' + parseFloat(auditData.financial_audit?.profit_margin_percentage || 0).toFixed(2) + '%'"></span>
            </div>
        </div>

        <!-- Details Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left Panel: Tournament info & Payout Distribution -->
            <div class="space-y-6">
                <!-- Tournament Profile -->
                <div class="bg-[#121623]/60 border border-white/10 rounded-[20px] p-6 text-white relative overflow-hidden backdrop-blur-md">
                    <h3 class="font-black text-sm uppercase tracking-widest italic text-neon-cyan mb-4">TOURNAMENT INFORMATION</h3>
                    <div class="space-y-3.5 text-sm">
                        <div class="flex justify-between border-b border-white/5 pb-2">
                            <span class="text-slate-400">Title:</span>
                            <span class="font-bold text-white" x-text="auditData.tournament?.title"></span>
                        </div>
                        <div class="flex justify-between border-b border-white/5 pb-2">
                            <span class="text-slate-400">Game:</span>
                            <span class="font-bold text-white" x-text="auditData.tournament?.game"></span>
                        </div>
                        <div class="flex justify-between border-b border-white/5 pb-2">
                            <span class="text-slate-400">Status:</span>
                            <span class="inline-flex px-2 py-0.5 rounded text-[9px] font-black uppercase border"
                                  :class="statusClasses(auditData.tournament?.status)"
                                  x-text="statusLabel(auditData.tournament?.status)"></span>
                        </div>
                        <div class="flex justify-between border-b border-white/5 pb-2">
                            <span class="text-slate-400">Base Entry Fee:</span>
                            <span class="font-bold text-white font-mono" x-text="'Rs. ' + parseFloat(auditData.tournament?.entry_fee || 0).toLocaleString()"></span>
                        </div>
                        <div class="flex justify-between border-b border-white/5 pb-2">
                            <span class="text-slate-400">Total Players:</span>
                            <span class="font-bold text-white font-mono" x-text="auditData.tournament?.registered_players_count || 0"></span>
                        </div>
                        <div class="flex justify-between border-b border-white/5 pb-2">
                            <span class="text-slate-400">Starts At:</span>
                            <span class="font-bold text-slate-300" x-text="formatDate(auditData.tournament?.starts_at)"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Ends At:</span>
                            <span class="font-bold text-slate-300" x-text="formatDate(auditData.tournament?.ends_at) || 'N/A'"></span>
                        </div>
                    </div>
                </div>

                <!-- Prize Matrix Breakdown -->
                <div class="bg-[#121623]/60 border border-white/10 rounded-[20px] p-6 text-white backdrop-blur-md">
                    <h3 class="font-black text-sm uppercase tracking-widest italic text-neon-pink mb-4">PRIZE DISTRIBUTION RULES</h3>
                    <div class="space-y-4">
                        <div class="bg-white/5 p-4 rounded-xl border border-white/5 flex items-center justify-between">
                            <div>
                                <h4 class="text-xs font-black text-slate-400 uppercase">Winner (1st)</h4>
                                <p class="text-lg font-black text-white mt-1" x-text="parseFloat(auditData.prize_breakdown?.winner?.percentage || 0) + '%'"></p>
                            </div>
                            <div class="text-right">
                                <span class="text-slate-500 text-[10px] block">Est. Payout</span>
                                <span class="text-sm font-bold text-neon-cyan font-mono" x-text="'Rs. ' + parseFloat(auditData.prize_breakdown?.winner?.amount || 0).toLocaleString()"></span>
                            </div>
                        </div>

                        <div class="bg-white/5 p-4 rounded-xl border border-white/5 flex items-center justify-between">
                            <div>
                                <h4 class="text-xs font-black text-slate-400 uppercase">Runner-Up (2nd)</h4>
                                <p class="text-lg font-black text-white mt-1" x-text="parseFloat(auditData.prize_breakdown?.runner_up?.percentage || 0) + '%'"></p>
                            </div>
                            <div class="text-right">
                                <span class="text-slate-500 text-[10px] block">Est. Payout</span>
                                <span class="text-sm font-bold text-neon-pink font-mono" x-text="'Rs. ' + parseFloat(auditData.prize_breakdown?.runner_up?.amount || 0).toLocaleString()"></span>
                            </div>
                        </div>

                        <template x-if="auditData.prize_breakdown?.semifinalists">
                            <div class="bg-white/5 p-4 rounded-xl border border-white/5 flex items-center justify-between">
                                <div>
                                    <h4 class="text-xs font-black text-slate-400 uppercase">Semifinalists (3rd & 4th)</h4>
                                    <p class="text-lg font-black text-white mt-1" x-text="parseFloat(auditData.prize_breakdown?.semifinalists?.percentage || 0) + '%'"></p>
                                </div>
                                <div class="text-right">
                                    <span class="text-slate-500 text-[10px] block">Est. Payout (Per Player)</span>
                                    <span class="text-sm font-bold text-white font-mono" x-text="'Rs. ' + parseFloat(auditData.prize_breakdown?.semifinalists?.per_player || 0).toLocaleString()"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Middle/Right Panels: Match Brackets & Players List -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Matches Log -->
                <div class="bg-[#121623]/60 border border-white/10 rounded-[20px] p-6 text-white backdrop-blur-md">
                    <h3 class="font-black text-sm uppercase tracking-widest italic text-neon-cyan mb-4 flex items-center justify-between">
                        <span>MATCH BRACKETS LOG</span>
                        <span class="text-[9px] bg-neon-cyan/20 border border-neon-cyan/35 px-2 py-0.5 rounded text-neon-cyan uppercase font-bold" x-text="auditData.tournament?.bracket_type === 'double_elimination' ? 'Double Elimination' : 'Single Elimination'"></span>
                    </h3>

                    <template x-if="!auditData.matches || Object.keys(auditData.matches).length === 0">
                        <div class="text-center py-10 text-slate-500 italic text-xs">
                            Matches will populate here once the bracket is generated.
                        </div>
                    </template>

                    <div class="space-y-6 max-h-[500px] overflow-y-auto pr-2">
                        <template x-for="(roundGroup, roundNumber) in auditData.matches" :key="roundNumber">
                            <div class="border border-white/5 rounded-2xl p-4 bg-[#090a0f]/40 space-y-3">
                                <h4 class="text-xs font-black uppercase text-neon-cyan tracking-wider flex items-center gap-1.5">
                                    <span class="w-1.5 h-3 rounded bg-neon-cyan inline-block"></span>
                                    Round <span x-text="roundNumber"></span>
                                </h4>
                                
                                <div class="divide-y divide-white/5 space-y-2.5">
                                    <template x-for="m in roundGroup.matches" :key="m.match_number">
                                        <div class="flex items-center justify-between text-xs pt-2.5">
                                            <div class="space-y-1">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-slate-500 uppercase tracking-widest font-black" x-text="'M' + m.match_number + ':'"></span>
                                                    <span class="font-bold" :class="m.winner === m.player1 ? 'text-green-400' : 'text-slate-300'" x-text="m.player1"></span>
                                                    <span class="text-slate-500 font-bold">vs</span>
                                                    <span class="font-bold" :class="m.winner === m.player2 ? 'text-green-400' : 'text-slate-300'" x-text="m.player2"></span>
                                                </div>
                                                <div class="text-[10px] text-slate-500 font-bold flex gap-2">
                                                    <span x-text="'Status: ' + m.status.toUpperCase()"></span>
                                                    <template x-if="m.result_type">
                                                        <span x-text="'(' + m.result_type + ')'"></span>
                                                    </template>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <template x-if="m.winner">
                                                    <span class="bg-[#39ff14]/15 border border-[#39ff14]/25 text-[#39ff14] text-[9px] px-2 py-0.5 rounded font-black uppercase tracking-wider" x-text="'Winner: ' + m.winner"></span>
                                                </template>
                                                <template x-if="!m.winner">
                                                    <span class="bg-white/5 text-slate-500 text-[9px] px-2 py-0.5 rounded font-black uppercase tracking-wider">Pending</span>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Prize Distributions & Financial ledger -->
                <div class="bg-[#121623]/60 border border-white/10 rounded-[20px] p-6 text-white backdrop-blur-md">
                    <h3 class="font-black text-sm uppercase tracking-widest italic text-neon-pink mb-4">DISTRIBUTION LEDGER (ACTUALS)</h3>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-white/5 text-slate-400 font-bold uppercase tracking-wider border-b border-white/5">
                                <tr>
                                    <th class="px-4 py-3">Player Name</th>
                                    <th class="px-4 py-3">Rank Title</th>
                                    <th class="px-4 py-3 font-mono">Commission Cut</th>
                                    <th class="px-4 py-3 font-mono">Prize Disbursed</th>
                                    <th class="px-4 py-3">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5 text-slate-300">
                                <template x-if="!auditData.prize_distributions || auditData.prize_distributions.length === 0">
                                    <tr>
                                        <td colspan="5" class="text-center py-6 text-slate-500 italic">No prize payouts recorded. Tournament must be completed.</td>
                                    </tr>
                                </template>

                                <template x-for="dist in auditData.prize_distributions">
                                    <tr class="hover:bg-white/5 transition-colors">
                                        <td class="px-4 py-3 font-bold text-white" x-text="dist.user_name"></td>
                                        <td class="px-4 py-3 uppercase font-black" :class="dist.rank === 1 ? 'text-neon-cyan' : 'text-neon-pink'" x-text="dist.rank_label"></td>
                                        <td class="px-4 py-3 font-mono text-slate-500" x-text="parseFloat(dist.percentage_of_pool) + '%'"></td>
                                        <td class="px-4 py-3 font-mono font-black text-green-400" x-text="'Rs. ' + parseFloat(dist.prize_amount).toLocaleString()"></td>
                                        <td class="px-4 py-3 text-slate-500" x-text="formatDate(dist.distributed_at)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Registrations List -->
                <div class="bg-[#121623]/60 border border-white/10 rounded-[20px] p-6 text-white backdrop-blur-md">
                    <h3 class="font-black text-sm uppercase tracking-widest italic text-neon-gold mb-4">PLAYER REGISTRATIONS</h3>

                    <div class="overflow-x-auto max-h-[300px] overflow-y-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-white/5 text-slate-400 font-bold uppercase tracking-wider border-b border-white/5">
                                <tr>
                                    <th class="px-4 py-3">Player</th>
                                    <th class="px-4 py-3 font-mono">Entry Fee Paid</th>
                                    <th class="px-4 py-3">Format Node</th>
                                    <th class="px-4 py-3">Rank (End)</th>
                                    <th class="px-4 py-3">Winnings</th>
                                    <th class="px-4 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5 text-slate-300">
                                <template x-if="!auditData.registrations || auditData.registrations.length === 0">
                                    <tr>
                                        <td colspan="6" class="text-center py-6 text-slate-500 italic">No registrations recorded yet.</td>
                                    </tr>
                                </template>

                                <template x-for="r in auditData.registrations">
                                    <tr class="hover:bg-white/5 transition-colors">
                                        <td class="px-4 py-3 font-bold text-white" x-text="r.user_name"></td>
                                        <td class="px-4 py-3 font-mono" x-text="'Rs. ' + parseFloat(r.entry_fee_paid || 0).toLocaleString()"></td>
                                        <td class="px-4 py-3 font-mono font-semibold text-slate-400" x-text="r.bracket_position ? ('Pos ' + r.bracket_position + (r.received_bye ? ' [BYE]' : '')) : 'Unassigned'"></td>
                                        <td class="px-4 py-3" x-text="r.final_rank ? ('#' + r.final_rank) : '-'"></td>
                                        <td class="px-4 py-3 font-mono font-bold text-green-400" x-text="r.prize_won ? ('Rs. ' + parseFloat(r.prize_won).toLocaleString()) : 'Rs. 0.00'"></td>
                                        <td class="px-4 py-3">
                                            <span 
                                                class="inline-block px-2 py-0.5 rounded text-[8px] font-black uppercase border"
                                                :class="playerStatusClasses(r.status)"
                                                x-text="r.status"
                                            ></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>

    </div>

</div>

<script>
function auditApp() {
    return {
        tournaments: [],
        selectedTournamentId: '',
        auditData: null,
        loading: false,
        errorMsg: '',

        async init() {
            // Get active list first
            await this.fetchTournaments();

            // Check if ?id= is set in query parameter
            const urlParams = new URLSearchParams(window.location.search);
            const id = urlParams.get('id');
            if (id) {
                this.selectedTournamentId = id;
                this.fetchAudit(id);
            }
        },

        async fetchTournaments() {
            try {
                // Get list of tournaments to populate select
                const response = await axios.get('/admin/api/tournaments/list?per_page=100');
                this.tournaments = response.data.tournaments || [];
            } catch (err) {
                console.error(err);
                this.errorMsg = 'Failed to fetch tournament list.';
            }
        },

        async fetchAudit(id) {
            if (!id) {
                this.auditData = null;
                return;
            }
            this.loading = true;
            this.errorMsg = '';
            try {
                const response = await axios.get(`/admin/api/tournaments/${id}/audit`);
                this.auditData = response.data || null;
            } catch (err) {
                console.error(err);
                this.errorMsg = err.response?.data?.error || 'Failed to load audit analysis for this tournament.';
                this.auditData = null;
            } finally {
                this.loading = false;
                this.$nextTick(() => {
                    lucide.createIcons();
                });
            }
        },

        changeTournament() {
            // Update URL query parameters
            const id = this.selectedTournamentId;
            if (id) {
                window.history.pushState(null, '', `?id=${id}`);
                this.fetchAudit(id);
            } else {
                window.history.pushState(null, '', window.location.pathname);
                this.auditData = null;
            }
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        statusClasses(status) {
            switch (status) {
                case 'open':
                    return 'bg-neon-cyan/15 text-neon-cyan border-neon-cyan/25';
                case 'registration_closed':
                    return 'bg-neon-pink/15 text-neon-pink border-neon-pink/25';
                case 'in_progress':
                    return 'bg-green-500/15 text-green-400 border-green-500/25';
                case 'completed':
                    return 'bg-slate-500/15 text-slate-400 border-slate-500/25';
                case 'cancelled':
                    return 'bg-red-500/15 text-red-400 border-red-500/25';
                default:
                    return 'bg-slate-500/15 text-slate-400 border-slate-500/25';
            }
        },

        statusLabel(status) {
            switch (status) {
                case 'open':
                    return 'Open (Registering)';
                case 'registration_closed':
                    return 'Starting Soon';
                case 'in_progress':
                    return 'Live';
                case 'completed':
                    return 'Completed';
                case 'cancelled':
                    return 'Cancelled';
                default:
                    return status;
            }
        },

        playerStatusClasses(status) {
            switch (status) {
                case 'registered':
                    return 'bg-neon-cyan/10 text-neon-cyan border-neon-cyan/20';
                case 'eliminated':
                    return 'bg-rose-500/10 text-rose-400 border-rose-500/20';
                case 'winner':
                    return 'bg-green-500/10 text-green-400 border-green-500/20';
                case 'forfeited':
                    return 'bg-slate-500/10 text-slate-500 border-slate-500/20';
                default:
                    return 'bg-slate-500/10 text-slate-400 border-slate-500/20';
            }
        }
    }
}
</script>
@endsection
