@extends('admin.layout')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<div x-data="schedulerApp()" x-init="init()" class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-white italic tracking-wider mb-2 flex items-center gap-2">
                <span class="bg-neon-cyan/20 w-2.5 h-8 rounded-full inline-block shadow-[0_0_15px_rgba(0,242,254,0.5)]"></span>
                TOURNAMENT SCHEDULER
            </h1>
            <p class="text-slate-400 text-sm">Create, configure, and orchestrate knockout tournaments across games.</p>
        </div>
        <button
            @click="openModal()"
            class="px-6 py-3 bg-gradient-to-r from-neon-cyan to-neon-pink text-black font-black uppercase tracking-wider rounded-xl hover:scale-[1.02] shadow-[0_0_15px_rgba(0,242,254,0.3)] transition-all cursor-pointer text-xs"
        >
            Create Tournament
        </button>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-[#121623]/60 backdrop-blur-md rounded-[20px] p-5 border border-white/5 relative overflow-hidden group hover:scale-[1.02] transition-all duration-300">
            <div class="absolute -right-4 -bottom-4 opacity-5 text-neon-cyan">
                <i data-lucide="trophy" class="w-20 h-20"></i>
            </div>
            <span class="text-slate-400 text-xs font-bold uppercase block mb-1">Total Tournaments</span>
            <span class="text-2xl font-black text-white font-mono" x-text="stats.total_tournaments || 0">0</span>
        </div>
        <div class="bg-[#121623]/60 backdrop-blur-md rounded-[20px] p-5 border border-white/5 relative overflow-hidden group hover:scale-[1.02] transition-all duration-300">
            <div class="absolute -right-4 -bottom-4 opacity-5 text-neon-cyan">
                <i data-lucide="play-circle" class="w-20 h-20"></i>
            </div>
            <span class="text-slate-400 text-xs font-bold uppercase block mb-1">Active</span>
            <span class="text-2xl font-black text-neon-cyan font-mono" x-text="stats.active_tournaments || 0">0</span>
        </div>
        <div class="bg-[#121623]/60 backdrop-blur-md rounded-[20px] p-5 border border-white/5 relative overflow-hidden group hover:scale-[1.02] transition-all duration-300">
            <div class="absolute -right-4 -bottom-4 opacity-5 text-green-400">
                <i data-lucide="check-circle-2" class="w-20 h-20"></i>
            </div>
            <span class="text-slate-400 text-xs font-bold uppercase block mb-1">Completed</span>
            <span class="text-2xl font-black text-green-400 font-mono" x-text="stats.completed_tournaments || 0">0</span>
        </div>
        <div class="bg-[#121623]/60 backdrop-blur-md rounded-[20px] p-5 border border-white/5 relative overflow-hidden group hover:scale-[1.02] transition-all duration-300">
            <div class="absolute -right-4 -bottom-4 opacity-5 text-neon-pink">
                <i data-lucide="dollar-sign" class="w-20 h-20"></i>
            </div>
            <span class="text-slate-400 text-xs font-bold uppercase block mb-1">Total Revenue</span>
            <span class="text-2xl font-black text-neon-pink font-mono" x-text="'Rs. ' + parseFloat(stats.total_revenue || 0).toLocaleString()">Rs. 0</span>
        </div>
    </div>

    <!-- Alert System -->
    <template x-if="successMsg">
        <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl p-4 flex items-center gap-3">
            <i data-lucide="check-circle" class="w-5 h-5 text-emerald-400"></i>
            <p class="text-sm font-bold" x-text="successMsg"></p>
        </div>
    </template>
    <template x-if="errorMsg">
        <div class="bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl p-4 flex items-center gap-3">
            <i data-lucide="alert-circle" class="w-5 h-5 text-rose-400"></i>
            <p class="text-sm font-bold" x-text="errorMsg"></p>
        </div>
    </template>

    <!-- Tournaments List -->
    <div class="bg-[#121623]/40 border border-white/10 rounded-3xl overflow-hidden backdrop-blur-md">
        <div class="p-6 border-b border-white/5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <h2 class="font-black text-white text-lg italic tracking-wider">ALL TOURNAMENTS</h2>
            <div class="flex gap-3 w-full sm:w-auto">
                <select
                    v-model="filterStatus"
                    @change="fetchTournaments()"
                    x-model="filterStatus"
                    class="w-full sm:w-48 bg-white/5 border border-white/10 rounded-xl px-4 py-2 text-white text-sm focus:outline-none focus:border-neon-cyan"
                >
                    <option class="bg-[#090a0f] text-white" value="">All Statuses</option>
                    <option class="bg-[#090a0f] text-white" value="draft">Draft</option>
                    <option class="bg-[#090a0f] text-white" value="open">Open (Registering)</option>
                    <option class="bg-[#090a0f] text-white" value="registration_closed">Registration Closed</option>
                    <option class="bg-[#090a0f] text-white" value="in_progress">In Progress (Live)</option>
                    <option class="bg-[#090a0f] text-white" value="completed">Completed</option>
                    <option class="bg-[#090a0f] text-white" value="cancelled">Cancelled</option>
                </select>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="flex flex-col items-center justify-center py-20 gap-3">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-neon-cyan"></div>
            <span class="text-slate-400 text-xs font-bold uppercase tracking-wider">Retrieving Tournaments...</span>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && tournaments.length === 0" class="text-center py-20">
            <div class="bg-white/5 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-white/10">
                <i data-lucide="trophy" class="w-8 h-8 text-slate-500"></i>
            </div>
            <p class="text-slate-400 font-bold tracking-wide uppercase text-sm">No Tournaments Found</p>
            <p class="text-slate-600 text-xs mt-1">Configure and release a new tournament to start.</p>
        </div>

        <!-- Tournament Table -->
        <div x-show="!loading && tournaments.length > 0" class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-white/5 text-slate-400 text-xs font-bold uppercase tracking-wider border-b border-white/5">
                    <tr>
                        <th class="px-6 py-4">Tournament Details</th>
                        <th class="px-6 py-4">Game</th>
                        <th class="px-6 py-4">Format</th>
                        <th class="px-6 py-4">Entry Fee</th>
                        <th class="px-6 py-4">Players</th>
                        <th class="px-6 py-4">Total Pool</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-sm text-slate-300">
                    <template x-for="t in tournaments" :key="t.id">
                        <tr class="hover:bg-white/5 transition-all">
                            <td class="px-6 py-4">
                                <div class="font-bold text-white text-base" x-text="t.title"></div>
                                <div class="text-xs text-slate-500 mt-1" x-text="formatDate(t.starts_at)"></div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="bg-[#121623] px-2.5 py-1 rounded-md text-xs font-semibold text-slate-300 border border-white/5" x-text="t.game?.name || t.game?.title || 'Unknown Game'"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs font-semibold" :class="t.bracket_type === 'double_elimination' ? 'text-neon-pink' : 'text-neon-cyan'" x-text="t.bracket_type === 'double_elimination' ? 'Double Elimination' : 'Single Elimination'"></span>
                            </td>
                            <td class="px-6 py-4 font-mono font-bold text-white">
                                <template x-if="t.is_dynamic_fee">
                                    <span class="text-neon-cyan" x-text="'Rs. ' + parseFloat(t.entry_fee) + ' (Dynamic)'"></span>
                                </template>
                                <template x-if="!t.is_dynamic_fee">
                                    <span x-text="'Rs. ' + parseFloat(t.entry_fee).toLocaleString()"></span>
                                </template>
                            </td>
                            <td class="px-6 py-4 font-mono font-bold" x-text="(t.registered_players_count || 0) + '/' + t.max_players"></td>
                            <td class="px-6 py-4 font-mono font-black text-neon-pink" x-text="'Rs. ' + parseFloat(t.total_pool || 0).toLocaleString()"></td>
                            <td class="px-6 py-4">
                                <span 
                                    class="inline-flex px-2 py-1 rounded-md text-[8px] font-black uppercase border"
                                    :class="statusClasses(t.status)"
                                    x-text="statusLabel(t.status)"
                                ></span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a
                                        :href="'/admin/tournaments/audit?id=' + t.id"
                                        class="p-2 rounded-lg bg-white/5 hover:bg-neon-cyan/20 border border-white/5 transition-all"
                                        title="Financial & Bracket Audit"
                                    >
                                        <i data-lucide="file-text" class="w-4 h-4 text-neon-cyan"></i>
                                    </a>
                                    <template x-if="t.status === 'open'">
                                        <button
                                            @click="generateBracket(t)"
                                            class="p-2 rounded-lg bg-white/5 hover:bg-emerald-500/20 border border-white/5 transition-all cursor-pointer"
                                            title="Generate Matches & Start"
                                        >
                                            <i data-lucide="git-branch" class="w-4 h-4 text-emerald-400"></i>
                                        </button>
                                    </template>
                                    <template x-if="['draft', 'open', 'registration_closed'].includes(t.status)">
                                        <button
                                            @click="cancelTournament(t)"
                                            class="p-2 rounded-lg bg-white/5 hover:bg-rose-500/20 border border-white/5 transition-all cursor-pointer"
                                            title="Cancel Tournament (Refunds Placed)"
                                        >
                                            <i data-lucide="x-circle" class="w-4 h-4 text-rose-500"></i>
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Tournament Modal -->
    <div x-show="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="showCreateModal = false"></div>
        <div class="relative bg-[#121623] border border-white/10 rounded-3xl max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-[0_0_50px_rgba(0,0,0,0.8)]">
            <div class="p-6 border-b border-white/5 flex items-center justify-between">
                <h2 class="text-xl font-black text-white italic tracking-wider flex items-center gap-2">
                    <span class="bg-neon-pink/20 w-2 h-6 rounded-full inline-block shadow-[0_0_15px_rgba(255,0,127,0.5)]"></span>
                    CREATE NEW TOURNAMENT
                </h2>
                <button @click="showCreateModal = false" class="p-2 rounded-full bg-white/5 hover:bg-white/10 transition-colors">
                    <i data-lucide="x" class="w-5 h-5 text-white"></i>
                </button>
            </div>
            
            <form @submit.prevent="createTournament()" class="p-6 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-slate-400 text-xs font-bold uppercase mb-2">Game Select</label>
                        <select
                            x-model="form.game_id"
                            required
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-neon-cyan"
                        >
                            <option value="">Select a game</option>
                            <template x-for="g in games" :key="g.id">
                                <option class="bg-[#090a0f] text-white" :value="g.id" x-text="g.name || g.title"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs font-bold uppercase mb-2">Bracket Format</label>
                        <select
                            x-model="form.bracket_type"
                            required
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-neon-cyan"
                        >
                            <option class="bg-[#090a0f] text-white" value="single_elimination">Single Elimination</option>
                            <option class="bg-[#090a0f] text-white" value="double_elimination">Double Elimination</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-slate-400 text-xs font-bold uppercase mb-2">Tournament Title</label>
                    <input
                        x-model="form.title"
                        type="text"
                        required
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-neon-cyan"
                        placeholder="Weekly Sabong Showdown / Ludo Master Knockout"
                    />
                </div>

                <div>
                    <label class="block text-slate-400 text-xs font-bold uppercase mb-2">Description</label>
                    <textarea
                        x-model="form.description"
                        rows="3"
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-neon-cyan"
                        placeholder="Rules, match setup info, guidelines for players..."
                    ></textarea>
                </div>

                <!-- Dynamic Pricing Toggle -->
                <div class="bg-white/5 rounded-2xl p-4 border border-white/5 space-y-4">
                    <div class="flex items-center gap-3">
                        <input
                            x-model="form.is_dynamic_fee"
                            type="checkbox"
                            id="is_dynamic_fee"
                            class="w-4 h-4 rounded bg-white/5 border-white/10 text-neon-cyan focus:ring-neon-cyan focus:ring-1 focus:ring-offset-0 focus:outline-none"
                        />
                        <label for="is_dynamic_fee" class="text-slate-200 text-xs font-bold uppercase tracking-wider cursor-pointer">
                            Enable Early Bird / Dynamic Pricing
                        </label>
                    </div>

                    <div x-show="form.is_dynamic_fee" class="grid grid-cols-1 md:grid-cols-3 gap-4" x-cloak>
                        <div>
                            <label class="block text-slate-400 text-[10px] font-bold uppercase mb-2">Base Entry Fee (PKR/Rs)</label>
                            <input
                                x-model.number="form.base_entry_fee"
                                type="number"
                                step="0.01"
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-white focus:outline-none focus:border-neon-cyan font-mono"
                                placeholder="10.00"
                            />
                        </div>
                        <div>
                            <label class="block text-slate-400 text-[10px] font-bold uppercase mb-2">Increment Amount (PKR/Rs)</label>
                            <input
                                x-model.number="form.fee_increment_amount"
                                type="number"
                                step="0.01"
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-white focus:outline-none focus:border-neon-cyan font-mono"
                                placeholder="5.00"
                            />
                        </div>
                        <div>
                            <label class="block text-slate-400 text-[10px] font-bold uppercase mb-2">Increment Every N Players</label>
                            <input
                                x-model.number="form.fee_increment_every_n_players"
                                type="number"
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-white focus:outline-none focus:border-neon-cyan font-mono"
                                placeholder="5"
                            />
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-slate-400 text-xs font-bold uppercase mb-2">Base/Fallback Entry Fee</label>
                        <input
                            x-model.number="form.entry_fee"
                            type="number"
                            step="0.01"
                            required
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-neon-cyan font-mono"
                            placeholder="0.00"
                        />
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs font-bold uppercase mb-2">Min Players</label>
                        <input
                            x-model.number="form.min_players"
                            type="number"
                            required
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-neon-cyan font-mono"
                            placeholder="4"
                        />
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs font-bold uppercase mb-2">Max Players</label>
                        <input
                            x-model.number="form.max_players"
                            type="number"
                            required
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-neon-cyan font-mono"
                            placeholder="64"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-slate-400 text-xs font-bold uppercase mb-2">Registration Opens</label>
                        <input
                            x-model="form.registration_opens_at"
                            type="datetime-local"
                            required
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-3 py-3 text-white text-xs focus:outline-none focus:border-neon-cyan"
                        />
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs font-bold uppercase mb-2">Registration Closes</label>
                        <input
                            x-model="form.registration_closes_at"
                            type="datetime-local"
                            required
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-3 py-3 text-white text-xs focus:outline-none focus:border-neon-cyan"
                        />
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs font-bold uppercase mb-2">Launch/Starts At</label>
                        <input
                            x-model="form.starts_at"
                            type="datetime-local"
                            required
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-3 py-3 text-white text-xs focus:outline-none focus:border-neon-cyan"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-slate-400 text-xs font-bold uppercase mb-3">Prize Breakdown & platform cut (%)</label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="text-slate-500 text-[10px] block mb-1">Winner Payout %</label>
                            <input
                                x-model.number="form.winner_prize_percentage"
                                type="number"
                                step="0.01"
                                required
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-neon-cyan font-mono"
                                placeholder="50"
                            />
                        </div>
                        <div>
                            <label class="text-slate-500 text-[10px] block mb-1">Runner-up Payout %</label>
                            <input
                                x-model.number="form.runner_up_prize_percentage"
                                type="number"
                                step="0.01"
                                required
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-neon-cyan font-mono"
                                placeholder="30"
                            />
                        </div>
                        <div>
                            <label class="text-slate-500 text-[10px] block mb-1">House Commission %</label>
                            <input
                                x-model.number="form.platform_commission_percentage"
                                type="number"
                                step="0.01"
                                required
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-neon-cyan font-mono"
                                placeholder="10"
                            />
                        </div>
                    </div>
                </div>

                <div class="bg-white/5 rounded-2xl p-4 border border-white/5 space-y-4">
                    <div class="flex items-center gap-3">
                        <input
                            x-model="form.include_semifinalist_prizes"
                            type="checkbox"
                            id="semifinalist"
                            class="w-4 h-4 rounded bg-white/5 border-white/10 text-neon-cyan focus:ring-neon-cyan focus:ring-1 focus:ring-offset-0 focus:outline-none"
                        />
                        <label for="semifinalist" class="text-slate-200 text-xs font-bold uppercase tracking-wider cursor-pointer">
                            Include Semifinalist Payouts
                        </label>
                    </div>

                    <div x-show="form.include_semifinalist_prizes" x-cloak>
                        <label class="block text-slate-400 text-[10px] font-bold uppercase mb-2">Total Semifinalist Pool % (split between 3rd & 4th)</label>
                        <input
                            x-model.number="form.semifinalist_prize_percentage"
                            type="number"
                            step="0.01"
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-neon-cyan font-mono"
                            placeholder="20"
                        />
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="loading"
                    class="w-full py-4 bg-gradient-to-r from-neon-cyan to-neon-pink text-black font-black uppercase tracking-wider rounded-xl hover:opacity-90 shadow-[0_0_20px_rgba(0,242,254,0.2)] transition-opacity disabled:opacity-50 cursor-pointer"
                    x-text="loading ? 'SCHEDULING TOURNAMENT...' : 'LAUNCH TOURNAMENT'"
                ></button>
            </form>
        </div>
    </div>
</div>

<script>
function schedulerApp() {
    return {
        tournaments: [],
        games: [],
        stats: {
            total_tournaments: 0,
            active_tournaments: 0,
            completed_tournaments: 0,
            total_revenue: 0
        },
        loading: false,
        showCreateModal: false,
        filterStatus: '',
        successMsg: '',
        errorMsg: '',

        form: {
            game_id: '',
            title: '',
            description: '',
            entry_fee: 10,
            min_players: 4,
            max_players: 16,
            registration_opens_at: '',
            registration_closes_at: '',
            starts_at: '',
            winner_prize_percentage: 60,
            runner_up_prize_percentage: 30,
            include_semifinalist_prizes: false,
            semifinalist_prize_percentage: 0,
            platform_commission_percentage: 10,
            bracket_type: 'single_elimination',
            is_dynamic_fee: false,
            base_entry_fee: 10,
            fee_increment_amount: 5,
            fee_increment_every_n_players: 4
        },

        init() {
            this.fetchTournaments();
            this.fetchGames();
            this.fetchStats();
        },

        showAlert(type, msg) {
            if (type === 'success') {
                this.successMsg = msg;
                this.errorMsg = '';
            } else {
                this.errorMsg = msg;
                this.successMsg = '';
            }
            setTimeout(() => {
                this.successMsg = '';
                this.errorMsg = '';
            }, 6000);
        },

        openModal() {
            // Preset dates for convenience
            const now = new Date();
            const openDate = new Date(now.getTime() + 5 * 60 * 1000); // 5 mins from now
            const closeDate = new Date(now.getTime() + 60 * 60 * 1000); // 1 hour from now
            const startDate = new Date(now.getTime() + 75 * 60 * 1000); // 1.25 hours from now

            this.form.registration_opens_at = this.formatDateTimeLocal(openDate);
            this.form.registration_closes_at = this.formatDateTimeLocal(closeDate);
            this.form.starts_at = this.formatDateTimeLocal(startDate);

            this.showCreateModal = true;
            this.$nextTick(() => {
                lucide.createIcons();
            });
        },

        formatDateTimeLocal(date) {
            const pad = (n) => n.toString().padStart(2, '0');
            return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
        },

        async fetchTournaments() {
            this.loading = true;
            try {
                const url = this.filterStatus 
                    ? `/admin/api/tournaments/list?status=${this.filterStatus}`
                    : '/admin/api/tournaments/list';
                const response = await axios.get(url);
                this.tournaments = response.data.tournaments || [];
            } catch (err) {
                console.error(err);
                this.showAlert('error', 'Failed to retrieve tournaments.');
            } finally {
                this.loading = false;
                this.$nextTick(() => {
                    lucide.createIcons();
                });
            }
        },

        async fetchGames() {
            try {
                const response = await axios.get('/admin/api/games');
                this.games = response.data || [];
            } catch (err) {
                console.error(err);
            }
        },

        async fetchStats() {
            try {
                const response = await axios.get('/admin/api/tournaments/dashboard');
                this.stats = {
                    total_tournaments: response.data.overview?.total_tournaments || 0,
                    active_tournaments: response.data.overview?.active_tournaments || 0,
                    completed_tournaments: response.data.overview?.completed_tournaments || 0,
                    total_revenue: response.data.financials?.total_revenue || 0
                };
            } catch (err) {
                console.error(err);
            }
        },

        async createTournament() {
            this.loading = true;
            try {
                // Prepare form data structure
                const payload = { ...this.form };
                if (!payload.include_semifinalist_prizes) {
                    payload.semifinalist_prize_percentage = 0;
                }

                await axios.post('/admin/api/tournaments', payload);
                this.showCreateModal = false;
                this.showAlert('success', 'Tournament scheduled successfully.');
                this.fetchTournaments();
                this.fetchStats();
            } catch (err) {
                console.error(err);
                const msg = err.response?.data?.message || err.response?.data?.error || 'Failed to schedule tournament. Verify dates and entries.';
                this.showAlert('error', msg);
            } finally {
                this.loading = false;
            }
        },

        async generateBracket(t) {
            if (!confirm(`Generate matches & start the bracket for "${t.title}"? This actions cannot be undone.`)) {
                return;
            }
            this.loading = true;
            try {
                const response = await axios.post(`/admin/api/tournaments/${t.id}/generate-bracket`);
                this.showAlert('success', response.data.message || 'Bracket matches generated successfully.');
                this.fetchTournaments();
                this.fetchStats();
            } catch (err) {
                console.error(err);
                const msg = err.response?.data?.error || err.response?.data?.message || 'Error generating brackets. Need at least min registrations.';
                this.showAlert('error', msg);
            } finally {
                this.loading = false;
            }
        },

        async cancelTournament(t) {
            if (!confirm(`Cancel the tournament "${t.title}"? All entry fees will be instantly refunded to the players.`)) {
                return;
            }
            this.loading = true;
            try {
                const response = await axios.post(`/admin/api/tournaments/${t.id}/cancel`);
                this.showAlert('success', response.data.message || 'Tournament cancelled and refunds dispatched.');
                this.fetchTournaments();
                this.fetchStats();
            } catch (err) {
                console.error(err);
                const msg = err.response?.data?.error || 'Failed to cancel tournament.';
                this.showAlert('error', msg);
            } finally {
                this.loading = false;
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
        }
    }
}
</script>
@endsection
