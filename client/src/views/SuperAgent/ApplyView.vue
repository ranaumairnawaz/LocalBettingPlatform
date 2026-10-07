<template>
  <div class="space-y-8 max-w-2xl mx-auto">
    <!-- Already Super Agent -->
    <div v-if="isSuperAgent" class="bg-gradient-to-r from-emerald-500/10 to-neon-cyan/10 rounded-[20px] border border-emerald-500/20 backdrop-blur-md p-8 text-center">
      <CheckCircle class="w-12 h-12 text-emerald-400 mx-auto mb-4 animate-bounce" />
      <h2 class="text-2xl font-black text-white italic tracking-wider mb-2">You're Already a Super Agent!</h2>
      <p class="text-slate-300 mb-6">Check out your dashboard to manage your team and earn commissions.</p>
      <router-link to="/super-agent/dashboard" class="inline-block bg-neon-cyan/20 hover:bg-neon-cyan/40 text-neon-cyan px-6 py-2 rounded-lg font-black uppercase tracking-wider border border-neon-cyan/20 transition-all">
        Go to Dashboard
      </router-link>
    </div>

    <!-- Application Form -->
    <div v-else>
      <div class="bg-slate-800/60 rounded-[20px] border border-white/10 backdrop-blur-md p-8 mb-8">
        <h1 class="text-2xl font-black text-white italic tracking-wider mb-2">Become a Super Agent</h1>
        <p class="text-slate-400">Join the elite group of top earners and start building your team today.</p>
      </div>

      <!-- Application Status -->
      <div v-if="applicationStatus" class="bg-slate-800/60 rounded-[20px] border border-white/10 backdrop-blur-md p-6 mb-8">
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Application Status</p>
        <span class="px-4 py-2 rounded-full text-xs font-black uppercase tracking-wider border inline-block"
          :class="applicationStatus.status === 'approved' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' :
                   applicationStatus.status === 'rejected' ? 'bg-red-500/10 text-red-400 border-red-500/20' :
                   'bg-yellow-500/10 text-yellow-400 border-yellow-500/20'">
          {{ titleCase(applicationStatus.status) }}
        </span>
        <p v-if="applicationStatus.rejection_note" class="text-sm text-slate-300 mt-3">
          <strong>Rejection Reason:</strong> {{ applicationStatus.rejection_note }}
        </p>
      </div>

      <!-- Form -->
      <form v-if="!applicationStatus || applicationStatus.status === 'rejected'" @submit.prevent="submitApplication" class="space-y-6">
        <div class="bg-slate-800/60 rounded-[20px] border border-white/10 backdrop-blur-md p-8 space-y-4">
          <div>
            <label class="text-xs font-black text-slate-400 uppercase tracking-wider mb-2 block">Group/Team Name *</label>
            <input v-model="form.group_name" type="text" placeholder="Your team's name..." required 
              class="w-full bg-slate-700/50 border border-white/10 rounded-lg px-4 py-3 text-white text-xs focus:outline-none focus:ring-1 focus:ring-neon-cyan">
          </div>

          <div>
            <label class="text-xs font-black text-slate-400 uppercase tracking-wider mb-2 block">Primary Platform *</label>
            <select v-model="form.platform" required class="w-full bg-slate-700/50 border border-white/10 rounded-lg px-4 py-3 text-white text-xs focus:outline-none focus:ring-1 focus:ring-neon-cyan">
              <option value="">Select Platform</option>
              <option value="whatsapp">WhatsApp</option>
              <option value="telegram">Telegram</option>
              <option value="facebook">Facebook</option>
              <option value="instagram">Instagram</option>
              <option value="tiktok">TikTok</option>
              <option value="other">Other</option>
            </select>
          </div>

          <div>
            <label class="text-xs font-black text-slate-400 uppercase tracking-wider mb-2 block">Team Member Count *</label>
            <input v-model.number="form.member_count" type="number" min="500" placeholder="How many members? (Min 500)" required 
              class="w-full bg-slate-700/50 border border-white/10 rounded-lg px-4 py-3 text-white text-xs focus:outline-none focus:ring-1 focus:ring-neon-cyan">
          </div>

          <div>
            <label class="text-xs font-black text-slate-400 uppercase tracking-wider mb-2 block">Group Screenshot/Proof *</label>
            <input @change="onProofSelect" type="file" accept="image/*" required 
              class="w-full bg-slate-700/50 border border-white/10 rounded-lg px-4 py-3 text-white text-xs focus:outline-none focus:ring-1 focus:ring-neon-cyan">
            <p class="text-[10px] text-slate-400 mt-1">Upload a screenshot showing your group size (WhatsApp, Telegram, etc.)</p>
          </div>

          <div v-if="previewProof" class="mt-4">
            <p class="text-xs font-black text-slate-400 uppercase tracking-wider mb-2">Preview</p>
            <img :src="previewProof" class="w-full h-48 object-cover rounded-lg border border-white/10">
          </div>

          <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-lg p-4">
            <p class="text-xs text-emerald-300 font-semibold flex items-center gap-2">
              <Info class="w-4 h-4 shrink-0" />
              Complete your profile before applying. Approval may take 24-48 hours.
            </p>
          </div>

          <button type="submit" :disabled="submitting" class="w-full bg-neon-cyan/20 hover:bg-neon-cyan/40 text-neon-cyan px-6 py-3 rounded-lg font-black uppercase tracking-wider transition-all border border-neon-cyan/20 disabled:opacity-50 cursor-pointer">
            {{ submitting ? 'Submitting...' : 'Submit Application' }}
          </button>
        </div>
      </form>

      <!-- Requirements -->
      <div class="bg-slate-800/60 rounded-[20px] border border-white/10 backdrop-blur-md p-8">
        <h2 class="text-lg font-black text-white italic tracking-wider mb-4 uppercase">Requirements</h2>
        <ul class="space-y-3 text-sm text-slate-300">
          <li class="flex gap-3">
            <span class="text-neon-cyan font-black text-lg">✓</span>
            <span>Minimum 500 active team members</span>
          </li>
          <li class="flex gap-3">
            <span class="text-neon-cyan font-black text-lg">✓</span>
            <span>Verified account with complete profile</span>
          </li>
          <li class="flex gap-3">
            <span class="text-neon-cyan font-black text-lg">✓</span>
            <span>No account violations or restrictions</span>
          </li>
          <li class="flex gap-3">
            <span class="text-neon-cyan font-black text-lg">✓</span>
            <span>Proof of active group/community</span>
          </li>
          <li class="flex gap-3">
            <span class="text-neon-cyan font-black text-lg">✓</span>
            <span>Commitment to recruit and support team</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { CheckCircle, Info } from 'lucide-vue-next';
import axios from 'axios';

const isSuperAgent = ref(false);
const applicationStatus = ref(null);
const form = ref({ group_name: '', platform: '', member_count: 5, group_proof: null });
const previewProof = ref(null);
const submitting = ref(false);

const titleCase = (str) => {
  if (!str) return '';
  return str.toLowerCase().split('_').join(' ').replace(/\b\w/g, (c) => c.toUpperCase());
};

const onProofSelect = (e) => {
  form.value.group_proof = e.target.files[0];
  const reader = new FileReader();
  reader.onload = (evt) => {
    previewProof.value = evt.target.result;
  };
  reader.readAsDataURL(form.value.group_proof);
};

const submitApplication = async () => {
  if (!form.value.group_proof) {
    alert('Please upload a screenshot of your group proof.');
    return;
  }
  
  const formData = new FormData();
  formData.append('group_name', form.value.group_name);
  formData.append('platform', form.value.platform);
  formData.append('member_count', form.value.member_count);
  formData.append('group_proof', form.value.group_proof);

  submitting.value = true;
  try {
    const { data } = await axios.post('/api/super-agent/apply', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    if (data.success) {
      alert('Application submitted successfully!');
      await checkStatus();
    } else {
      alert('Error: ' + (data.error || data.message || 'Submission failed'));
    }
  } catch (error) {
    alert('Error: ' + (error.response?.data?.error || error.response?.data?.message || 'Submission failed'));
  } finally {
    submitting.value = false;
  }
};

const checkStatus = async () => {
  try {
    const { data } = await axios.get('/api/super-agent/application-status');
    
    if (data.status === 'approved') {
      isSuperAgent.value = true;
    } else if (data.status && data.status !== 'none') {
      applicationStatus.value = data;
    } else {
      applicationStatus.value = null;
    }
  } catch (error) {
    console.error('Error checking status:', error);
  }
};

onMounted(() => {
  checkStatus();
});
</script>
