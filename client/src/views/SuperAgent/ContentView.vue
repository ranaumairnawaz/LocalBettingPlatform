<template>
  <div class="space-y-8">
    <!-- Submission Form -->
    <div class="bg-slate-800/60 rounded-[20px] border border-white/10 backdrop-blur-md p-8">
      <h2 class="text-lg font-black text-white italic tracking-wider mb-6 uppercase flex items-center gap-2">
        <span class="bg-neon-pink/20 w-1.5 h-6 rounded-full"></span>
        Submit Content
      </h2>

      <form @submit.prevent="submitContent" class="space-y-4">
        <div>
          <label class="text-xs font-black text-slate-400 uppercase tracking-wider mb-2 block">Platform</label>
          <select v-model="form.platform" class="w-full bg-slate-700/50 border border-white/10 rounded-lg px-4 py-2 text-white text-xs focus:outline-none focus:ring-1 focus:ring-neon-cyan">
            <option value="">Select Platform</option>
            <option value="youtube">YouTube</option>
            <option value="instagram">Instagram</option>
            <option value="tiktok">TikTok</option>
          </select>
        </div>

        <div>
          <label class="text-xs font-black text-slate-400 uppercase tracking-wider mb-2 block">Video URL</label>
          <input v-model="form.video_url" type="url" placeholder="https://..." class="w-full bg-slate-700/50 border border-white/10 rounded-lg px-4 py-2 text-white text-xs focus:outline-none focus:ring-1 focus:ring-neon-cyan" required>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="text-xs font-black text-slate-400 uppercase tracking-wider mb-2 block">View Count</label>
            <input v-model.number="form.view_count" type="number" min="0" class="w-full bg-slate-700/50 border border-white/10 rounded-lg px-4 py-2 text-white text-xs focus:outline-none focus:ring-1 focus:ring-neon-cyan" required>
          </div>
          <div>
            <label class="text-xs font-black text-slate-400 uppercase tracking-wider mb-2 block">Estimated Bonus</label>
            <input :value="'Rs. ' + formatCurrency(estimatedBonus)" type="text" disabled class="w-full bg-slate-600/50 border border-white/10 rounded-lg px-4 py-2 text-neon-gold text-xs font-mono font-black">
          </div>
        </div>

        <div>
          <label class="text-xs font-black text-slate-400 uppercase tracking-wider mb-2 block">Screenshot/Proof</label>
          <input @change="onScreenshotSelect" type="file" accept="image/*" class="w-full bg-slate-700/50 border border-white/10 rounded-lg px-4 py-2 text-white text-xs focus:outline-none">
        </div>

        <button type="submit" :disabled="submitting" class="w-full bg-neon-cyan/20 hover:bg-neon-cyan/40 text-neon-cyan px-6 py-3 rounded-lg font-black uppercase tracking-wider transition-all border border-neon-cyan/20 disabled:opacity-50 cursor-pointer">
          {{ submitting ? 'Submitting...' : 'Submit for Approval' }}
        </button>
      </form>
    </div>

    <!-- Previous Submissions -->
    <div class="bg-slate-800/60 rounded-[20px] border border-white/10 backdrop-blur-md p-8">
      <h2 class="text-lg font-black text-white italic tracking-wider mb-6 uppercase flex items-center gap-2">
        <span class="bg-neon-gold/20 w-1.5 h-6 rounded-full"></span>
        Your Submissions
      </h2>

      <div class="grid gap-4">
        <div v-for="submission in submissions" :key="submission.id" class="bg-slate-700/50 rounded-[20px] p-5 border border-white/10">
          <div class="flex justify-between items-start mb-2">
            <div>
              <p class="text-sm font-black text-white capitalize">
                {{ getPlatformIcon(submission.platform) }} {{ submission.platform }} • {{ formatNumber(submission.view_count) }} Views
              </p>
              <p class="text-xs text-slate-400">{{ formatDate(submission.created_at) }}</p>
            </div>
            <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-wider border"
              :class="submission.status === 'approved' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' :
                       submission.status === 'rejected' ? 'bg-red-500/10 text-red-400 border-red-500/20' :
                       'bg-yellow-500/10 text-yellow-400 border-yellow-500/20'">
              {{ submission.status }}
            </span>
          </div>
          <p class="text-xs text-slate-300 mb-3 break-all bg-black/20 p-2 rounded-lg font-mono">{{ submission.url }}</p>
          <div class="flex justify-between items-center pt-2 border-t border-white/5">
            <p class="text-xs text-slate-400">Bonus: <span class="text-neon-gold font-black font-mono">Rs. {{ formatCurrency(submission.bonus_amount || 0) }}</span></p>
            <button v-if="submission.screenshot_path" @click="viewScreenshot(submission.screenshot_path)" class="text-neon-cyan text-[10px] font-black uppercase hover:text-neon-cyan/80 transition-colors cursor-pointer">
              View Proof Screenshot
            </button>
          </div>
          <p v-if="submission.admin_note" class="text-xs text-rose-400 mt-2 p-2 bg-rose-500/5 border border-rose-500/10 rounded-lg">
            <strong>Admin Note:</strong> {{ submission.admin_note }}
          </p>
        </div>

        <div v-if="!submissions.length" class="text-center py-8 text-slate-400">
          <p>No submissions yet</p>
        </div>
      </div>
    </div>

    <!-- Screenshot Modal -->
    <div v-if="selectedScreenshot" @click="selectedScreenshot = null" class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4 cursor-zoom-out animate-fade-in">
      <img :src="selectedScreenshot" class="max-h-[85vh] max-w-full rounded-lg border border-white/10 shadow-2xl">
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const form = ref({ platform: '', video_url: '', view_count: 0, screenshot: null });
const submissions = ref([]);
const submitting = ref(false);
const selectedScreenshot = ref(null);

const formatCurrency = (amount) => new Intl.NumberFormat('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount);
const formatDate = (date) => new Date(date).toLocaleDateString('en-PK', { year: 'numeric', month: 'short', day: 'numeric' });
const formatNumber = (num) => new Intl.NumberFormat('en-PK').format(num);

const getPlatformIcon = (platform) => {
  const icons = { youtube: '▶️', instagram: '📷', tiktok: '🎵' };
  return icons[platform] || '📱';
};

const estimatedBonus = computed(() => {
  const bonusRates = {
    youtube: { 10000: 500, 50000: 2500, 100000: 5000, 500000: 25000 },
    instagram: { 10000: 400, 50000: 2000, 100000: 4000, 500000: 20000 },
    tiktok: { 10000: 300, 50000: 1500, 100000: 3000, 500000: 15000 }
  };
  
  if (!form.value.platform || !form.value.view_count) return 0;
  
  const rates = bonusRates[form.value.platform];
  const milestones = Object.keys(rates).map(Number).sort((a, b) => b - a); // sort desc to find highest hit milestone
  
  for (let milestone of milestones) {
    if (form.value.view_count >= milestone) {
      return rates[milestone];
    }
  }
  
  return 0;
});

const onScreenshotSelect = (e) => {
  form.value.screenshot = e.target.files[0];
};

const submitContent = async () => {
  if (!form.value.platform || !form.value.video_url) return;
  
  submitting.value = true;
  const formData = new FormData();
  formData.append('platform', form.value.platform);
  formData.append('video_url', form.value.video_url);
  formData.append('view_count', form.value.view_count);
  if (form.value.screenshot) {
    formData.append('screenshot', form.value.screenshot);
  }

  try {
    const { data } = await axios.post('/api/super-agent/content-bonus/request', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    if (data.success) {
      alert('Content submitted successfully for approval!');
      form.value = { platform: '', video_url: '', view_count: 0, screenshot: null };
      await fetchSubmissions();
    } else {
      alert('Error: ' + (data.error || data.message || 'Submission failed'));
    }
  } catch (error) {
    alert('Error: ' + (error.response?.data?.error || error.response?.data?.message || 'Submission failed'));
  } finally {
    submitting.value = false;
  }
};

const fetchSubmissions = async () => {
  try {
    const { data } = await axios.get('/api/super-agent/content-bonus/requests');
    submissions.value = data.data || [];
  } catch (error) {
    console.error('Error fetching submissions:', error);
  }
};

const viewScreenshot = (path) => {
  selectedScreenshot.value = `http://localhost:8000/storage/${path}`;
};

onMounted(() => {
  fetchSubmissions();
});
</script>
