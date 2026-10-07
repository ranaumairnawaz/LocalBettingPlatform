<template>
  <div class="min-h-screen text-slate-100 flex flex-col gap-8 pb-10">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
      <div>
        <h2 class="text-xs uppercase tracking-widest text-slate-400 font-bold">Player Account</h2>
        <h1 class="text-2xl font-black text-white italic tracking-wide">PLAYER PROFILE & STATS</h1>
      </div>
      
      <!-- Referral tag -->
      <div 
        @click="copyReferralCode" 
        class="bg-[#121623]/60 border border-white/15 px-4 py-2 rounded-2xl flex items-center gap-3 cursor-pointer hover:border-neon-cyan/40 hover:bg-white/5 active:scale-95 transition-all"
        title="Click to copy Referral Code"
      >
        <div class="flex flex-col">
          <span class="text-[9px] uppercase font-bold text-slate-400">Referral Code</span>
          <span class="text-xs font-black text-neon-cyan">{{ auth.user?.referral_code || 'N/A' }}</span>
        </div>
        <Copy size="14" class="text-slate-400 hover:text-neon-cyan transition-colors" />
      </div>
    </div>

    <!-- 1. GLOWING USER PASSPORT CARD -->
    <div class="glow-card-cyan p-6 md:p-8 flex flex-col md:flex-row items-center md:justify-between gap-6 relative overflow-hidden">
      <!-- Background glowing vector -->
      <div class="absolute -right-20 -bottom-20 w-64 h-64 bg-neon-cyan/5 rounded-full blur-3xl pointer-events-none"></div>

      <div class="flex flex-col md:flex-row items-center gap-5 relative z-10">
        <!-- Glowing Avatar -->
        <div v-if="!auth.user?.avatar" class="w-20 h-20 bg-neon-cyan/15 rounded-2xl border-2 border-neon-cyan/40 flex items-center justify-center font-black text-3xl text-neon-cyan shadow-[0_0_20px_rgba(0,242,254,0.15)] shrink-0">
          {{ auth.user?.username?.charAt(0).toUpperCase() }}
        </div>
        <div v-else class="w-20 h-20 rounded-2xl border-2 border-neon-cyan/40 shadow-[0_0_20px_rgba(0,242,254,0.15)] shrink-0 overflow-hidden">
          <img :src="getAvatarUrl(auth.user?.avatar)" class="w-full h-full object-cover" />
        </div>
        <div class="text-center md:text-left space-y-1">
          <h2 class="text-2xl font-black text-white tracking-wide flex items-center justify-center md:justify-start gap-2">
            <span>{{ auth.user?.name || auth.user?.username }}</span>
            <span v-if="auth.user?.is_admin" class="text-[9px] bg-neon-gold/15 text-neon-gold border border-neon-gold/25 font-black uppercase px-2 py-0.5 rounded-md">Admin</span>
          </h2>
          <p v-if="auth.user?.name" class="text-xs text-slate-400 font-bold uppercase tracking-wider">@{{ auth.user?.username }}</p>
          <p class="text-xs text-slate-400 font-semibold">{{ auth.user?.email || 'No email associated' }}</p>
          <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold">Registered: {{ formatDate(auth.user?.created_at) }}</p>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-wrap justify-center gap-3 w-full md:w-auto relative z-10">
        <!-- Admin Panel Button -->
        <a 
          v-if="auth.user?.is_admin" 
          href="/admin/dashboard" 
          target="_blank" 
          class="flex items-center gap-2 bg-neon-gold text-black font-black italic px-5 py-2.5 rounded-xl text-xs hover:scale-105 active:scale-95 transition-all shadow-[0_0_15px_rgba(255,183,0,0.3)]"
        >
          <LayoutDashboard size="14" />
          <span>ADMIN PANEL</span>
        </a>

        <!-- Edit Profile Button -->
        <button 
          @click="$router.push('/profile/edit')" 
          class="flex items-center gap-2 bg-white/5 border border-white/5 hover:border-neon-cyan/40 hover:bg-neon-cyan/10 text-slate-200 hover:text-white font-black italic px-5 py-2.5 rounded-xl text-xs hover:scale-105 active:scale-95 transition-all cursor-pointer"
        >
          <Edit3 size="14" />
          <span>EDIT PROFILE</span>
        </button>

        <!-- Sign Out Button -->
        <button 
          @click="handleLogout" 
          class="flex items-center gap-2 bg-white/5 border border-white/5 hover:border-neon-pink/40 hover:bg-neon-pink/10 text-slate-200 hover:text-white font-black italic px-5 py-2.5 rounded-xl text-xs hover:scale-105 active:scale-95 transition-all cursor-pointer"
        >
          <LogOut size="14" />
          <span>SIGN OUT</span>
        </button>
      </div>
    </div>

    <!-- 2. ANALYTICS STATS METRIC GRID -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- Total Deposits -->
      <div class="glow-card-cyan p-4 flex items-center justify-between">
        <div class="flex flex-col">
          <span class="text-[9px] uppercase tracking-wider text-slate-400 font-bold">Total Deposits</span>
          <span class="text-lg font-black text-neon-cyan mt-1">{{ currencySymbol }}{{ stats?.total_deposits || '0.00' }}</span>
        </div>
        <div class="p-2.5 bg-neon-cyan/15 rounded-xl border border-neon-cyan/20">
          <ArrowUpRight size="20" class="text-neon-cyan" />
        </div>
      </div>

      <!-- Total Withdrawals -->
      <div class="glow-card-pink p-4 flex items-center justify-between">
        <div class="flex flex-col">
          <span class="text-[9px] uppercase tracking-wider text-slate-400 font-bold">Withdrawals</span>
          <span class="text-lg font-black text-neon-pink mt-1">{{ currencySymbol }}{{ stats?.total_withdrawals || '0.00' }}</span>
        </div>
        <div class="p-2.5 bg-neon-pink/15 rounded-xl border border-neon-pink/20">
          <ArrowDownLeft size="20" class="text-neon-pink" />
        </div>
      </div>

      <!-- Total Winnings -->
      <div class="glow-card-green p-4 flex items-center justify-between">
        <div class="flex flex-col">
          <span class="text-[9px] uppercase tracking-wider text-slate-400 font-bold">Total Winnings</span>
          <span class="text-lg font-black text-neon-green mt-1">{{ currencySymbol }}{{ stats?.total_winnings || '0.00' }}</span>
        </div>
        <div class="p-2.5 bg-neon-green/15 rounded-xl border border-neon-green/20">
          <Coins size="20" class="text-neon-green" />
        </div>
      </div>

      <!-- Total Losses -->
      <div class="glow-card-gold p-4 flex items-center justify-between">
        <div class="flex flex-col">
          <span class="text-[9px] uppercase tracking-wider text-slate-400 font-bold">Total Losses</span>
          <span class="text-lg font-black text-neon-gold mt-1">{{ currencySymbol }}{{ stats?.total_losses || '0.00' }}</span>
        </div>
        <div class="p-2.5 bg-neon-gold/15 rounded-xl border border-neon-gold/20">
          <Flame size="20" class="text-neon-gold" />
        </div>
      </div>
    </div>

    <!-- ACCOUNT MANAGEMENT SECTION -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- Bindings Navigation Card -->
      <div 
        @click="$router.push('/profile/bind')" 
        class="bg-[#121623]/60 border border-white/5 hover:border-neon-cyan/40 p-6 rounded-[24px] backdrop-blur-md flex items-center justify-between cursor-pointer hover:bg-white/5 hover:shadow-[0_0_20px_rgba(0,242,254,0.05)] transition-all active:scale-[0.98]"
      >
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-2xl bg-neon-cyan/15 border border-neon-cyan/20 flex items-center justify-center text-neon-cyan">
            <WalletCards size="24" />
          </div>
          <div>
            <h3 class="text-base font-black text-white italic uppercase tracking-wider">Withdrawal Accounts</h3>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">Configure your EasyPaisa & JazzCash payout details</p>
          </div>
        </div>
        <ChevronRight size="18" class="text-slate-500" />
      </div>

      <!-- Teams Navigation Card -->
      <div 
        @click="$router.push('/teams')" 
        class="bg-[#121623]/60 border border-white/5 hover:border-neon-cyan/40 p-6 rounded-[24px] backdrop-blur-md flex items-center justify-between cursor-pointer hover:bg-white/5 hover:shadow-[0_0_20px_rgba(0,242,254,0.05)] transition-all active:scale-[0.98]"
      >
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-2xl bg-neon-cyan/15 border border-neon-cyan/20 flex items-center justify-center text-neon-cyan">
            <Users size="24" />
          </div>
          <div>
            <h3 class="text-base font-black text-white italic uppercase tracking-wider">Teams Program</h3>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">Grow your referral tree and track L1-L5 commissions</p>
          </div>
        </div>
        <ChevronRight size="18" class="text-slate-500" />
      </div>

      <!-- Settings Navigation Card -->
      <div 
        @click="$router.push('/profile/settings')" 
        class="bg-[#121623]/60 border border-white/5 hover:border-neon-cyan/40 p-6 rounded-[24px] backdrop-blur-md flex items-center justify-between cursor-pointer hover:bg-white/5 hover:shadow-[0_0_20px_rgba(0,242,254,0.05)] transition-all active:scale-[0.98]"
      >
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-2xl bg-neon-cyan/15 border border-neon-cyan/20 flex items-center justify-center text-neon-cyan">
            <Settings size="24" />
          </div>
          <div>
            <h3 class="text-base font-black text-white italic uppercase tracking-wider">Settings</h3>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">Manage audio and app preferences</p>
          </div>
        </div>
        <ChevronRight size="18" class="text-slate-500" />
      </div>
    </div>

    <!-- SUPER AGENT PROGRAM CARD -->
    <div v-if="!auth.user?.is_super_agent" class="bg-gradient-to-r from-neon-cyan/10 to-neon-pink/10 border border-neon-cyan/20 p-6 rounded-[24px] backdrop-blur-md">
      <div class="flex items-start justify-between mb-4">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-2xl bg-neon-cyan/20 border border-neon-cyan/30 flex items-center justify-center text-neon-cyan">
            <Star size="24" />
          </div>
          <div>
            <h3 class="text-base font-black text-white italic uppercase tracking-wider">Join Super Agent Program</h3>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">Become an elite earner & build your team</p>
          </div>
        </div>
      </div>
      <p class="text-xs text-slate-300 mb-4">Earn custom-tiered commissions, manage your team, submit content for bonuses, and compete in exclusive challenges.</p>
      <button 
        @click="$router.push('/super-agent/apply')" 
        class="w-full bg-neon-cyan hover:bg-neon-cyan/90 text-black font-black italic px-5 py-2.5 rounded-xl text-xs hover:scale-[1.02] active:scale-95 transition-all shadow-[0_0_15px_rgba(0,242,254,0.3)]"
      >
        Apply Now
      </button>
    </div>

    <!-- SUPER AGENT DASHBOARD LINK -->
    <div v-else class="bg-gradient-to-r from-neon-gold/10 to-neon-cyan/10 border border-neon-gold/20 p-6 rounded-[24px] backdrop-blur-md">
      <div class="flex items-start justify-between">
        <div class="flex items-center gap-3 flex-1">
          <div class="w-12 h-12 rounded-2xl bg-neon-gold/20 border border-neon-gold/30 flex items-center justify-center text-neon-gold">
            <Star size="24" />
          </div>
          <div>
            <h3 class="text-base font-black text-white italic uppercase tracking-wider">Super Agent Dashboard</h3>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">View your team earnings & commissions</p>
          </div>
        </div>
      </div>
      <button 
        @click="$router.push('/super-agent/dashboard')" 
        class="w-full bg-neon-gold hover:bg-neon-gold/90 text-black font-black italic px-5 py-2.5 rounded-xl text-xs mt-4 hover:scale-[1.02] active:scale-95 transition-all shadow-[0_0_15px_rgba(255,183,0,0.3)]"
      >
        Go to Dashboard
      </button>
    </div>

    <!-- 3. DETAILED PERFORMANCE & AWARDS ROW -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- PERFORMANCE CIRCLE -->
      <div class="glow-card-cyan p-6 flex flex-col items-center gap-4">
        <div class="w-full flex items-center gap-2 border-b border-white/5 pb-2">
          <Activity size="18" class="text-neon-cyan" />
          <h3 class="font-black text-sm text-white">Win Ratio</h3>
        </div>

        <div class="relative w-36 h-36 flex items-center justify-center mt-2">
          <!-- Circular Progress Meter SVG -->
          <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
            <!-- Background circle -->
            <circle cx="50" cy="50" r="40" stroke="rgba(255,255,255,0.05)" stroke-width="8" fill="transparent" />
            <!-- Active neon circle -->
            <circle 
              cx="50" 
              cy="50" 
              r="40" 
              stroke="#00f2fe" 
              stroke-width="8" 
              fill="transparent" 
              :stroke-dasharray="251.2"
              :stroke-dashoffset="251.2 - (251.2 * (stats?.win_percentage || 0)) / 100"
              stroke-linecap="round"
              class="transition-all duration-1000 ease-out"
            />
          </svg>
          <!-- Inside text -->
          <div class="absolute flex flex-col items-center">
            <span class="text-2xl font-black text-white tracking-wide">{{ stats?.win_percentage || 0 }}%</span>
            <span class="text-[8px] text-slate-400 uppercase tracking-widest font-black">Win Rate</span>
          </div>
        </div>

        <div class="text-[10px] uppercase font-bold text-slate-400 mt-2">
          Total Completed Games: <span class="text-white">{{ stats?.total_games || 0 }}</span>
        </div>
      </div>

      <!-- BEST GAME PROFILE -->
      <div class="glow-card-green p-6 flex flex-col justify-between gap-4">
        <div>
          <div class="w-full flex items-center gap-2 border-b border-white/5 pb-2 mb-3">
            <Award size="18" class="text-neon-green" />
            <h3 class="font-black text-sm text-white">Best Game (Wins)</h3>
          </div>

          <div v-if="stats?.best_game" class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-xl border border-white/10 overflow-hidden bg-zinc-950 shrink-0">
              <img :src="getGameThumbnail(stats.best_game)" class="w-full h-full object-cover" />
            </div>
            <div class="flex flex-col">
              <span class="text-base font-black text-white italic tracking-wide">{{ stats.best_game.name }}</span>
              <span class="text-[10px] text-slate-400 font-bold uppercase mt-1">Wins Count: <span class="text-neon-green font-black">{{ stats.best_game.wins }} rounds</span></span>
            </div>
          </div>
          <div v-else class="text-slate-500 italic text-xs flex flex-col items-center justify-center py-6 gap-1.5">
            <HelpCircle size="24" class="stroke-[1.5]" />
            <span>No winning history yet.</span>
          </div>
        </div>
        <div class="text-[9px] uppercase font-black tracking-widest text-neon-green bg-neon-green/5 border border-neon-green/10 py-1.5 rounded-lg text-center">
          MVP STRAT ACTIVE
        </div>
      </div>

      <!-- WORST GAME PROFILE -->
      <div class="glow-card-pink p-6 flex flex-col justify-between gap-4">
        <div>
          <div class="w-full flex items-center gap-2 border-b border-white/5 pb-2 mb-3">
            <TrendingDown size="18" class="text-neon-pink" />
            <h3 class="font-black text-sm text-white">Worst Game (Losses)</h3>
          </div>

          <div v-if="stats?.worst_game" class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-xl border border-white/10 overflow-hidden bg-zinc-950 shrink-0">
              <img :src="getGameThumbnail(stats.worst_game)" class="w-full h-full object-cover" />
            </div>
            <div class="flex flex-col">
              <span class="text-base font-black text-white italic tracking-wide">{{ stats.worst_game.name }}</span>
              <span class="text-[10px] text-slate-400 font-bold uppercase mt-1">Losses Count: <span class="text-neon-pink font-black">{{ stats.worst_game.losses }} rounds</span></span>
            </div>
          </div>
          <div v-else class="text-slate-500 italic text-xs flex flex-col items-center justify-center py-6 gap-1.5">
            <HelpCircle size="24" class="stroke-[1.5]" />
            <span>No loss history recorded.</span>
          </div>
        </div>
        <div class="text-[9px] uppercase font-black tracking-widest text-neon-pink bg-neon-pink/5 border border-neon-pink/10 py-1.5 rounded-lg text-center">
          POTENTIAL TO IMPROVE
        </div>
      </div>
    </div>

    <!-- 4. LOGS STATEMENT MODULE -->
    <div class="glow-card-cyan p-6 md:p-8 flex flex-col gap-6">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-white/5 pb-3">
        <!-- Tabs Header -->
        <div class="flex gap-4">
          <button 
            @click="activeLogTab = 'game_logs'"
            class="text-sm font-black italic tracking-wider pb-2 relative transition-colors cursor-pointer"
            :class="activeLogTab === 'game_logs' ? 'text-neon-cyan' : 'text-slate-400 hover:text-slate-200'"
          >
            <span>GAME LOGS</span>
            <div v-if="activeLogTab === 'game_logs'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-neon-cyan"></div>
          </button>
          <button 
            @click="activeLogTab = 'transactions'"
            class="text-sm font-black italic tracking-wider pb-2 relative transition-colors cursor-pointer"
            :class="activeLogTab === 'transactions' ? 'text-neon-cyan' : 'text-slate-400 hover:text-slate-200'"
          >
            <span>TRANSACTION HISTORY</span>
            <div v-if="activeLogTab === 'transactions'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-neon-cyan"></div>
          </button>
        </div>

        <!-- Search Input inside logs -->
        <div class="relative w-full md:w-64">
          <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
            <Search size="14" />
          </span>
          <input 
            v-model="logSearch"
            type="text" 
            placeholder="Search records..." 
            class="w-full bg-white/5 border border-white/5 rounded-xl py-1.5 pl-8 pr-3 text-xs focus:outline-none focus:border-neon-cyan/40"
          />
        </div>
      </div>

      <!-- Tab Content 1: Game Logs -->
      <div v-if="activeLogTab === 'game_logs'">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="border-b border-white/5 text-slate-400 font-bold uppercase tracking-widest text-[9px]">
                <th class="py-3 px-4">Game</th>
                <th class="py-3 px-4">Bet (Rs)</th>
                <th class="py-3 px-4">Payout (Rs)</th>
                <th class="py-3 px-4">Result</th>
                <th class="py-3 px-4">Date</th>
              </tr>
            </thead>
            <tbody v-if="filteredGameLogs.length > 0" class="divide-y divide-white/5 font-semibold">
              <tr v-for="log in filteredGameLogs" :key="log.id" class="hover:bg-white/5 transition-colors">
                <td class="py-3 px-4 font-black italic text-slate-200">
                  {{ log.game?.name || 'Unknown Game' }}
                </td>
                <td class="py-3 px-4 font-mono">
                  {{ log.bet_amount }}
                </td>
                <td class="py-3 px-4 font-mono" :class="log.payout_amount > 0 ? 'text-neon-green' : 'text-slate-400'">
                  {{ log.payout_amount }}
                </td>
                <td class="py-3 px-4">
                  <span 
                    class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase"
                    :class="[
                      log.result === 'win' ? 'bg-neon-green/15 text-neon-green border border-neon-green/25' : 
                      log.result === 'lose' ? 'bg-neon-pink/15 text-neon-pink border border-neon-pink/25' : 
                      'bg-slate-800 text-slate-400 border border-white/5'
                    ]"
                  >
                    {{ log.result }}
                  </span>
                </td>
                <td class="py-3 px-4 text-[10px] text-slate-400">
                  {{ formatDate(log.created_at) }}
                </td>
              </tr>
            </tbody>
            <tbody v-else>
              <tr>
                <td colspan="5" class="py-8 text-center text-slate-500 italic">
                  No gameplay logs found.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab Content 2: Transactions -->
      <div v-if="activeLogTab === 'transactions'">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="border-b border-white/5 text-slate-400 font-bold uppercase tracking-widest text-[9px]">
                <th class="py-3 px-4">Type</th>
                <th class="py-3 px-4">Method</th>
                <th class="py-3 px-4">Amount (Rs)</th>
                <th class="py-3 px-4">Reference</th>
                <th class="py-3 px-4">Status</th>
                <th class="py-3 px-4">Date</th>
              </tr>
            </thead>
            <tbody v-if="filteredTransactions.length > 0" class="divide-y divide-white/5 font-semibold">
              <tr v-for="tx in filteredTransactions" :key="tx.id" class="hover:bg-white/5 transition-colors">
                <td class="py-3 px-4 uppercase text-[10px]">
                  <span 
                    class="px-2 py-0.5 rounded-md font-black"
                    :class="tx.type === 'deposit' ? 'text-neon-cyan bg-neon-cyan/15' : 'text-neon-pink bg-neon-pink/15'"
                  >
                    {{ tx.type }}
                  </span>
                </td>
                <td class="py-3 px-4 text-slate-200">
                  {{ tx.method || 'Unknown' }}
                </td>
                <td class="py-3 px-4 font-mono font-black" :class="tx.type === 'deposit' ? 'text-neon-cyan' : 'text-neon-pink'">
                  {{ tx.amount }}
                </td>
                <td class="py-3 px-4 font-mono text-[10px] text-slate-400">
                  {{ tx.reference || 'N/A' }}
                </td>
                <td class="py-3 px-4">
                  <span 
                    class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase"
                    :class="[
                      tx.status === 'completed' ? 'bg-neon-green/15 text-neon-green border border-neon-green/25' : 
                      tx.status === 'pending' ? 'bg-neon-gold/15 text-neon-gold border border-neon-gold/25' : 
                      'bg-neon-pink/15 text-neon-pink border border-neon-pink/25'
                    ]"
                  >
                    {{ tx.status }}
                  </span>
                </td>
                <td class="py-3 px-4 text-[10px] text-slate-400">
                  {{ formatDate(tx.created_at) }}
                </td>
              </tr>
            </tbody>
            <tbody v-else>
              <tr>
                <td colspan="6" class="py-8 text-center text-slate-500 italic">
                  No transaction records found.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { 
  User as UserIcon, 
  LogOut, 
  Copy, 
  ArrowUpRight, 
  ArrowDownLeft, 
  Coins, 
  Flame, 
  Activity, 
  Award, 
  TrendingDown, 
  HelpCircle, 
  Search,
  LayoutDashboard,
  WalletCards,
  Users,
  ChevronRight,
  Settings,
  Edit3,
  Star
} from 'lucide-vue-next'
import { useAuthStore } from '../store/auth'
import { useRouter } from 'vue-router'
import axios from 'axios'

import tomJerryImg from '../assets/tom_and_jerry/create_an_image_where_both_202605220403.jpeg'

const auth = useAuthStore()
const router = useRouter()

const stats = ref(null)
const currencySymbol = ref('Rs.')
const activeLogTab = ref('game_logs')
const logSearch = ref('')

const gameLogs = ref([])
const transactions = ref([])

const fetchStats = async () => {
  try {
    const { data } = await axios.get('/api/user/stats')
    stats.value = data
  } catch (e) {
    console.error('Failed to load stats', e)
  }
}

const getAvatarUrl = (path) => {
  if (!path) return '';
  return path.startsWith('http') ? path : `http://localhost:8000/storage/${path}`;
}

const fetchSettings = async () => {
  try {
    const { data } = await axios.get('/api/settings')
    if (data.currency_symbol) {
      currencySymbol.value = data.currency_symbol
    }
  } catch (e) {
    console.error('Failed to load settings', e)
  }
}

const fetchActivity = async () => {
  try {
    const { data } = await axios.get('/api/activity')
    gameLogs.value = data
  } catch (e) {
    console.error('Failed to load game activity', e)
  }
}

const fetchTransactions = async () => {
  try {
    const { data } = await axios.get('/api/transactions')
    transactions.value = data
  } catch (e) {
    console.error('Failed to load transactions', e)
  }
}

const getGameThumbnail = (g) => {
  if (g.thumbnail) {
    return `http://localhost:8000/storage/${g.thumbnail}`
  }
  if (g.image) {
    return `http://localhost:8000/storage/${g.image}`
  }
  if (g.slug === 'tom_jerry') {
    return tomJerryImg
  }
  const fallback = {
    aviator: 'https://images.unsplash.com/photo-1569154941061-e231b4725ef1?q=80&w=400',
    ludo: 'https://images.unsplash.com/photo-1596720426673-e483d74482d7?q=80&w=400',
    pool_1v1: 'https://images.unsplash.com/photo-1544197150-b99a580bb7a8?q=80&w=400',
    pool_snooker: 'https://images.unsplash.com/photo-1609141975765-a8c6f2df6a09?q=80&w=400',
    chicken: 'https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?q=80&w=400',
    chicken_fight: 'https://images.unsplash.com/photo-1608096299210-db7e38487075?q=80&w=400',
    cricket: 'https://images.unsplash.com/photo-1531415074968-036ba1b575da?q=80&w=400',
    carrom: 'https://images.unsplash.com/photo-1610890716171-6b1bb98ffd09?q=80&w=400'
  }
  return fallback[g.slug] || 'https://images.unsplash.com/photo-1511512578047-dfb367046420?q=80&w=400'
}

const copyReferralCode = () => {
  const code = auth.user?.referral_code
  if (code) {
    navigator.clipboard.writeText(code)
    alert('Referral code copied successfully!')
  }
}

const formatDate = (dateStr) => {
  if (!dateStr) return 'N/A'
  const date = new Date(dateStr)
  return date.toLocaleDateString(undefined, { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  }) + ' ' + date.toLocaleTimeString(undefined, {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const filteredGameLogs = computed(() => {
  if (!logSearch.value.trim()) return gameLogs.value
  const query = logSearch.value.toLowerCase().trim()
  return gameLogs.value.filter(log => {
    return (
      log.game?.name?.toLowerCase().includes(query) ||
      log.result?.toLowerCase().includes(query)
    )
  })
})

const filteredTransactions = computed(() => {
  if (!logSearch.value.trim()) return transactions.value
  const query = logSearch.value.toLowerCase().trim()
  return transactions.value.filter(tx => {
    return (
      tx.type?.toLowerCase().includes(query) ||
      tx.method?.toLowerCase().includes(query) ||
      tx.reference?.toLowerCase().includes(query) ||
      tx.status?.toLowerCase().includes(query)
    )
  })
})

const handleLogout = async () => {
  if (confirm('Are you sure you want to sign out?')) {
    await auth.logout()
    router.push('/login')
  }
}

onMounted(() => {
  fetchStats()
  fetchSettings()
  fetchActivity()
  fetchTransactions()
})
</script>
