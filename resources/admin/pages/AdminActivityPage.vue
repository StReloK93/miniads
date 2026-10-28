<template>
   <section class="admin-page">
      <header class="admin-page-heading">
         <div>
            <h1>Faollik</h1>
            <p class="admin-muted">Foydalanuvchilar ilovada nimalarni ko'ryapti va qayerdan kelyapti.</p>
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
               <span class="admin-stat-label">{{ days === 1 ? "Bugun kirganlar" : "Kunlik o'rtacha" }}</span>
               <strong>{{ num(days === 1 ? stats.summary.visitors : stats.summary.avg_daily) }}</strong>
               <span class="admin-stat-detail">kishi (mehmonlar ham)</span>
            </article>
            <article class="admin-stat-card">
               <span class="admin-stat-label">E'lon ko'rishlar</span>
               <strong>{{ num(stats.summary.product_views) }}</strong>
               <span class="admin-stat-detail">{{ num(stats.summary.page_views) }} ta sahifa o'tishi</span>
            </article>
            <article class="admin-stat-card">
               <span class="admin-stat-label">Sotuvchiga murojaat</span>
               <strong>{{ num(stats.summary.contacts) }}</strong>
               <span class="admin-stat-detail">
                  qo'ng'iroq {{ num(stats.contacts.phone) }} · Telegram {{ num(stats.contacts.telegram) }}
               </span>
            </article>
            <article class="admin-stat-card">
               <span class="admin-stat-label">E'lon qo'yish</span>
               <strong>{{ num(stats.funnel.created) }} / {{ num(stats.funnel.started) }}</strong>
               <span class="admin-stat-detail">
                  qo'ydi / formani ochdi<template v-if="abandoned > 0"> · {{ num(abandoned) }} kishi tashlab ketdi</template>
               </span>
            </article>
         </div>

         <div class="admin-dashboard-grid">
            <section v-if="days > 1" class="admin-chart-card">
               <div class="admin-card-header">
                  <div>
                     <h2 class="admin-card-title">Kunlar bo'yicha</h2>
                     <p class="admin-card-subtitle">Har kuni kirgan kishilar</p>
                  </div>
                  <CalendarDays class="size-4 text-(--z-primary)" />
               </div>
               <RankList
                  :rows="[...stats.daily].reverse().map((d) => ({ label: dayLabel(d.date), value: d.users, detail: `${num(d.users)} kishi` }))"
                  empty="Bu davrda hali ma'lumot yo'q."
               />
            </section>

            <section class="admin-chart-card">
               <div class="admin-card-header">
                  <div>
                     <h2 class="admin-card-title">Qayerdan kelyapti</h2>
                     <p class="admin-card-subtitle">Ilova qanday ochilgan</p>
                  </div>
                  <LogIn class="size-4 text-(--z-primary)" />
               </div>
               <RankList :rows="rows(stats.sources, sourceLabel)" empty="Hali ma'lumot yo'q." />
            </section>

            <section class="admin-chart-card">
               <div class="admin-card-header">
                  <div>
                     <h2 class="admin-card-title">Sahifalar</h2>
                     <p class="admin-card-subtitle">Qaysi bo'limlar ko'p ochiladi</p>
                  </div>
                  <LayoutGrid class="size-4 text-(--z-primary)" />
               </div>
               <RankList :rows="rows(stats.pages, pageLabel)" empty="Hali ma'lumot yo'q." />
            </section>

            <section class="admin-chart-card">
               <div class="admin-card-header">
                  <div>
                     <h2 class="admin-card-title">Kategoriyalar</h2>
                     <p class="admin-card-subtitle">Eng ko'p ochilganlari</p>
                  </div>
                  <Workflow class="size-4 text-(--z-primary)" />
               </div>
               <RankList :rows="rows(stats.categories)" empty="Hali kategoriya ochilmagan." />
            </section>

            <section class="admin-chart-card">
               <div class="admin-card-header">
                  <div>
                     <h2 class="admin-card-title">Ko'p ko'rilgan e'lonlar</h2>
                     <p class="admin-card-subtitle">Top 10</p>
                  </div>
                  <Eye class="size-4 text-(--z-primary)" />
               </div>
               <RankList :rows="rows(stats.products)" empty="Hali e'lon ko'rilmagan." />
            </section>

            <section class="admin-chart-card">
               <div class="admin-card-header">
                  <div>
                     <h2 class="admin-card-title">Qurilma va til</h2>
                     <p class="admin-card-subtitle">
                        Ro'yxatdan o'tganlar: {{ num(stats.summary.registered) }} · sevimliga qo'shildi:
                        {{ num(stats.summary.favorites) }} · kuzatuv: {{ num(stats.summary.saved_searches) }} ·
                        shikoyat: {{ num(stats.summary.reports) }}
                     </p>
                  </div>
                  <Smartphone class="size-4 text-(--z-primary)" />
               </div>
               <RankList
                  :rows="[...rows(stats.clients, clientLabel), ...rows(stats.locales, localeLabel)]"
                  empty="Hali ma'lumot yo'q."
               />
            </section>
         </div>
      </template>
   </section>
</template>

<script setup lang="ts">
import { computed, defineComponent, h, onMounted, ref, type PropType } from "vue";
import { CalendarDays, Eye, LayoutGrid, LogIn, Smartphone, Workflow } from "lucide-vue-next";
import AdminRepo from "@admin/entities/AdminRepo";

type Group = { ref: string | null; hits: number; users: number; label?: string };
type Stats = {
   days: number;
   summary: Record<
      | "visitors"
      | "avg_daily"
      | "registered"
      | "app_opens"
      | "page_views"
      | "product_views"
      | "contacts"
      | "favorites"
      | "saved_searches"
      | "reports",
      number
   >;
   funnel: { started: number; created: number; ads: number };
   daily: { date: string; users: number; hits: number }[];
   pages: Group[];
   categories: Group[];
   products: Group[];
   contacts: { phone: number; telegram: number };
   sources: Group[];
   clients: Group[];
   locales: Group[];
};
type Row = { label: string; value: number; detail: string };

// Ilova route nomlari → tushunarli nom
const PAGES: Record<string, string> = {
   home: "Bosh sahifa",
   categories: "Kategoriyalar ro'yxati",
   category: "Kategoriya ichi",
   "product-id": "E'lon sahifasi",
   favorites: "Sevimlilar",
   profile: "Profil",
   "my-ads": "Mening e'lonlarim",
   "create-select-category": "E'lon qo'yish: kategoriya",
   "create-select-city": "E'lon qo'yish: shahar",
   "create-product": "E'lon qo'yish: forma",
   "edit-product": "E'lonni tahrirlash",
   login: "Kirish sahifasi",
};
const SOURCES: Record<string, string> = {
   direct: "Telegram: bot / menyu orqali",
   product: "E'lon havolasi (kanal, ulashish)",
   create: "\"E'lon qo'yish\" tugmasi (kanal)",
   ss: "Kuzatuv xabari (bot)",
   my_ads: "E'lon muddati xabari (bot)",
   admin_reported: "Admin xabari (shikoyat)",
   browser: "Brauzer (sayt)",
};
const CLIENTS: Record<string, string> = { telegram: "Telegram ilovasi", browser: "Brauzer" };
const LOCALES: Record<string, string> = { uz: "Til: O'zbekcha", ru: "Til: Русский", en: "Til: English" };

const pageLabel = (ref: string) => PAGES[ref] ?? ref;
const sourceLabel = (ref: string) => SOURCES[ref] ?? ref;
const clientLabel = (ref: string) => CLIENTS[ref] ?? ref;
const localeLabel = (ref: string) => LOCALES[ref] ?? ref;

const num = (value: number) => Number(value ?? 0).toLocaleString("ru-RU");

function rows(groups: Group[], label?: (ref: string) => string): Row[] {
   return groups.map((group) => ({
      label: group.label ?? (label ? label(group.ref ?? "") : (group.ref ?? "—")),
      value: group.hits,
      detail: `${num(group.hits)} marta · ${num(group.users)} kishi`,
   }));
}

function dayLabel(date: string) {
   return new Date(date).toLocaleDateString("uz-UZ", { day: "numeric", month: "short", weekday: "short" });
}

/** Reyting ro'yxati (Qidiruvlar sahifasidagi bilan bir xil ko'rinish) */
const RankList = defineComponent({
   props: {
      rows: { type: Array as PropType<Row[]>, required: true },
      empty: { type: String, required: true },
   },
   setup(props) {
      return () => {
         if (!props.rows.length) return h("p", { class: "admin-muted" }, props.empty);
         const max = Math.max(...props.rows.map((row) => row.value), 1);

         return h(
            "div",
            { class: "admin-rank-list" },
            props.rows.map((row, index) =>
               h("div", { class: "admin-rank-item", key: `${row.label}-${index}` }, [
                  h("div", { class: "admin-rank-header" }, [
                     h("span", { class: "admin-rank-name truncate" }, row.label),
                     h("span", { class: "admin-rank-count shrink-0 tabular-nums" }, row.detail),
                  ]),
                  h("div", { class: "admin-rank-track" }, [
                     h("div", { class: "admin-rank-fill", style: { width: `${(row.value / max) * 100}%` } }),
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

const abandoned = computed(() => Math.max(0, (stats.value?.funnel.started ?? 0) - (stats.value?.funnel.created ?? 0)));

async function load() {
   loading.value = true;
   error.value = "";
   try {
      const { data } = await AdminRepo.activity(days.value);
      stats.value = data;
   } catch (exception) {
      console.error("Faollik statistikasi yuklanmadi.", exception);
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
