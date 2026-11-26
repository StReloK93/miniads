<template>
   <section class="admin-page">
      <header class="admin-page-heading">
         <div>
            <h1>Qidiruvlar</h1>
            <p class="admin-muted">Odamlar nimani qidiryapti va nima topilmayapti — talab bor, e'lon yo'q joylar.</p>
         </div>
      </header>
      <AdminDateBar v-model="selection" ranges />

      <p v-if="error" class="admin-alert admin-alert-error">{{ error }}</p>
      <div v-if="loading && !stats" class="admin-panel admin-loading">Statistika yuklanmoqda…</div>

      <template v-else-if="stats">
         <div class="admin-stats-grid">
            <article class="admin-stat-card">
               <span class="admin-stat-label">Jami qidiruvlar</span>
               <strong>{{ stats.total.toLocaleString("ru-RU") }}</strong>
            </article>
            <article class="admin-stat-card">
               <span class="admin-stat-label">Topilmagan so'zlar</span>
               <strong>{{ stats.not_found.length }}</strong>
               <span class="admin-stat-detail">turli so'z (top 30)</span>
            </article>
         </div>

         <AdminTabs
            v-model="tab"
            :tabs="[
               { value: 'not_found', label: 'Topilmadi', count: stats.not_found.length, danger: true },
               { value: 'top', label: 'Ko\'p qidirilgan' },
            ]"
         />

         <!-- Eng foydali ro'yxat: shu narsalar bo'yicha e'lon topish/import qilish kerak -->
         <section v-if="tab === 'not_found'" class="admin-chart-card">
            <p class="admin-card-subtitle mb-3!">Natija bermagan so'rovlar — talab bor, e'lon yo'q.</p>
            <AdminRankList
               :rows="stats.not_found.map((row) => ({ label: row.query, value: row.searches, detail: `${row.searches} marta · ${row.users} kishi` }))"
               empty="Hamma qidiruv natija bergan — zo'r!"
               :limit="15"
               color="var(--z-danger)"
            />
         </section>
         <section v-else class="admin-chart-card">
            <p class="admin-card-subtitle mb-3!">O'rtacha natijalar soni bilan.</p>
            <AdminRankList
               :rows="stats.top.map((row) => ({ label: row.query, value: row.searches, detail: `${row.searches} marta · ~${row.avg_results ?? 0} natija` }))"
               empty="Hali qidiruvlar yo'q."
               :limit="15"
            />
         </section>
      </template>
   </section>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from "vue";
import AdminDateBar from "@admin/components/AdminDateBar.vue";
import { useDateSelection } from "@admin/modules/useDateSelection";
import AdminRepo from "@admin/entities/AdminRepo";
import AdminTabs from "@admin/components/AdminTabs.vue";
import AdminRankList from "@admin/components/AdminRankList.vue";

type Row = { query: string; searches: number; users: number; avg_results?: number; last_at: string };
type Stats = { days: number; total: number; top: Row[]; not_found: Row[] };

const tab = ref("not_found");

// Standart: oxirgi 7 kun (qidiruvlar bir kunda kam bo'ladi); istalgan kunni ham tanlash mumkin
const { selection, params } = useDateSelection({ date: null, days: 7 });
const stats = ref<Stats | null>(null);
const loading = ref(true);
const error = ref("");

async function load() {
   loading.value = true;
   error.value = "";
   try {
      const { data } = await AdminRepo.searchStats(params());
      stats.value = data;
   } catch (exception) {
      console.error("Qidiruv statistikasi yuklanmadi.", exception);
      error.value = "Statistikani yuklab bo'lmadi.";
   } finally {
      loading.value = false;
   }
}

watch(selection, load);
onMounted(load);
</script>
