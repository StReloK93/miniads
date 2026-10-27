<template>
   <section class="admin-page">
      <header class="admin-page-heading">
         <div>
            <h1>Qidiruvlar</h1>
            <p class="admin-muted">Odamlar nimani qidiryapti va nima topilmayapti — talab bor, e'lon yo'q joylar.</p>
         </div>
         <div class="flex gap-1.5">
            <button
               v-for="option in [1, 7, 30]"
               :key="option"
               type="button"
               class="admin-filter-chip pr-3!"
               :class="{ 'is-active': days === option }"
               @click="setDays(option)"
            >
               {{ option === 1 ? "Bugun" : `${option} kun` }}
            </button>
         </div>
      </header>

      <p v-if="error" class="admin-alert admin-alert-error">{{ error }}</p>
      <div v-if="loading && !stats" class="admin-panel admin-loading">Statistika yuklanmoqda…</div>

      <template v-else-if="stats">
         <div class="admin-stats-grid">
            <article class="admin-stat-card">
               <span class="admin-stat-label">Jami qidiruvlar</span>
               <strong>{{ stats.total.toLocaleString("ru-RU") }}</strong>
            </article>
            <article class="admin-stat-card">
               <span class="admin-stat-label">Topilmagan so'rovlar</span>
               <strong>{{ stats.not_found.length }}</strong>
               <span class="admin-stat-detail">turli so'z (top 30)</span>
            </article>
         </div>

         <div class="admin-dashboard-grid">
            <!-- Eng foydali ro'yxat: shu narsalar bo'yicha e'lon topish/import qilish kerak -->
            <section class="admin-chart-card">
               <div class="admin-card-header">
                  <div>
                     <h2 class="admin-card-title">Topilmadi</h2>
                     <p class="admin-card-subtitle">Natija bermagan so'rovlar</p>
                  </div>
                  <SearchX class="size-4 text-(--z-danger)" />
               </div>
               <StatsList :rows="stats.not_found" empty="Hamma qidiruv natija bergan — zo'r!" danger />
            </section>

            <section class="admin-chart-card">
               <div class="admin-card-header">
                  <div>
                     <h2 class="admin-card-title">Ko'p qidirilgan</h2>
                     <p class="admin-card-subtitle">O'rtacha natijalar soni bilan</p>
                  </div>
                  <TrendingUp class="size-4 text-(--z-primary)" />
               </div>
               <StatsList :rows="stats.top" empty="Hali qidiruvlar yo'q." show-results />
            </section>
         </div>
      </template>
   </section>
</template>

<script setup lang="ts">
import { defineComponent, h, onMounted, ref, type PropType } from "vue";
import { SearchX, TrendingUp } from "lucide-vue-next";
import AdminRepo from "@admin/entities/AdminRepo";

type Row = { query: string; searches: number; users: number; avg_results?: number; last_at: string };
type Stats = { days: number; total: number; top: Row[]; not_found: Row[] };

/** Reyting ro'yxati: so'z, nechta qidiruv, nechta odam (va o'rtacha natija) */
const StatsList = defineComponent({
   props: {
      rows: { type: Array as PropType<Row[]>, required: true },
      empty: { type: String, required: true },
      showResults: Boolean,
      danger: Boolean,
   },
   setup(props) {
      return () => {
         if (!props.rows.length) return h("p", { class: "admin-muted" }, props.empty);
         const max = Math.max(...props.rows.map((row) => row.searches));

         return h(
            "div",
            { class: "admin-rank-list" },
            props.rows.map((row) =>
               h("div", { class: "admin-rank-item", key: row.query }, [
                  h("div", { class: "admin-rank-header" }, [
                     h("span", { class: "admin-rank-name truncate" }, row.query),
                     h(
                        "span",
                        { class: "admin-rank-count shrink-0 tabular-nums" },
                        `${row.searches} marta · ${row.users} kishi` +
                           (props.showResults ? ` · ~${row.avg_results ?? 0} natija` : ""),
                     ),
                  ]),
                  h("div", { class: "admin-rank-track" }, [
                     h("div", {
                        class: "admin-rank-fill",
                        style: {
                           width: `${(row.searches / max) * 100}%`,
                           ...(props.danger ? { background: "var(--z-danger)" } : {}),
                        },
                     }),
                  ]),
               ]),
            ),
         );
      };
   },
});

const days = ref(7);
const stats = ref<Stats | null>(null);
const loading = ref(true);
const error = ref("");

async function load() {
   loading.value = true;
   error.value = "";
   try {
      const { data } = await AdminRepo.searchStats(days.value);
      stats.value = data;
   } catch (exception) {
      console.error("Qidiruv statistikasi yuklanmadi.", exception);
      error.value = "Statistikani yuklab bo'lmadi.";
   } finally {
      loading.value = false;
   }
}

function setDays(value: number) {
   if (days.value === value) return;
   days.value = value;
   load();
}

onMounted(load);
</script>
