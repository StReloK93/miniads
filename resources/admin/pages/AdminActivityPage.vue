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
         <AdminTabs v-model="tab" :tabs="tabs" />

         <!-- UMUMIY: asosiy raqamlar, kunlar, qayerdan kelyapti -->
         <template v-if="tab === 'overview'">
            <div class="admin-stats-grid">
               <article class="admin-stat-card">
                  <span class="admin-stat-label">{{ days === 1 ? "Bugun kirganlar" : "Kunlik o'rtacha" }}</span>
                  <strong>{{ num(days === 1 ? stats.summary.visitors : stats.summary.avg_daily) }}</strong>
                  <span class="admin-stat-detail">kishi (mehmonlar ham)</span>
               </article>
               <article class="admin-stat-card">
                  <span class="admin-stat-label">E'lon ko'rishlar</span>
                  <strong>{{ num(stats.summary.product_views) }}</strong>
                  <span class="admin-stat-detail">{{ num(stats.summary.page_views) }} sahifa o'tishi</span>
               </article>
               <article class="admin-stat-card">
                  <span class="admin-stat-label">Sotuvchiga murojaat</span>
                  <strong>{{ num(stats.summary.contacts) }}</strong>
                  <span class="admin-stat-detail">qo'ng'iroq {{ num(stats.contacts.phone) }} · TG {{ num(stats.contacts.telegram) }}</span>
               </article>
               <article class="admin-stat-card">
                  <span class="admin-stat-label">E'lon qo'yish</span>
                  <strong>{{ num(stats.funnel.created) }} <small class="text-sm text-(--admin-muted)">/ {{ num(stats.funnel.started) }}</small></strong>
                  <span class="admin-stat-detail">
                     qo'ydi / boshladi<template v-if="abandoned > 0"> · {{ num(abandoned) }} tashladi</template>
                  </span>
               </article>
            </div>

            <section v-if="days > 1" class="admin-chart-card">
               <div class="admin-card-header">
                  <h2 class="admin-card-title">Kunlar bo'yicha</h2>
                  <span class="admin-card-subtitle">kirgan kishilar</span>
               </div>
               <AdminRankList
                  :rows="[...stats.daily].reverse().map((d) => ({ label: dayLabel(d.date), value: d.users, detail: `${num(d.users)} kishi` }))"
                  empty="Bu davrda hali ma'lumot yo'q."
                  :limit="7"
               />
            </section>

            <section class="admin-chart-card">
               <div class="admin-card-header">
                  <h2 class="admin-card-title">Qayerdan kelyapti</h2>
                  <span class="admin-card-subtitle">ilova qanday ochilgan</span>
               </div>
               <AdminRankList :rows="rows(stats.sources, sourceLabel)" empty="Hali ma'lumot yo'q." />
            </section>
         </template>

         <!-- KONTENT: nima ko'rilyapti -->
         <template v-else-if="tab === 'content'">
            <section class="admin-chart-card">
               <div class="admin-card-header">
                  <h2 class="admin-card-title">Ko'p ko'rilgan e'lonlar</h2>
               </div>
               <AdminRankList :rows="rows(stats.products)" empty="Hali e'lon ko'rilmagan." />
            </section>
            <section class="admin-chart-card">
               <div class="admin-card-header">
                  <h2 class="admin-card-title">Kategoriyalar</h2>
                  <span class="admin-card-subtitle">eng ko'p ochilgan</span>
               </div>
               <AdminRankList :rows="rows(stats.categories)" empty="Hali kategoriya ochilmagan." color="#0ea5e9" />
            </section>
            <section class="admin-chart-card">
               <div class="admin-card-header">
                  <h2 class="admin-card-title">Sahifalar</h2>
                  <span class="admin-card-subtitle">ilova bo'limlari</span>
               </div>
               <AdminRankList :rows="rows(stats.pages, pageLabel)" empty="Hali ma'lumot yo'q." />
            </section>
         </template>

         <!-- AUDITORIYA: kimlar -->
         <template v-else>
            <section class="admin-chart-card">
               <div class="admin-mini-stats">
                  <div>
                     <small>Ro'yxatdan o'tgan</small>
                     <strong>{{ num(stats.summary.registered) }}</strong>
                  </div>
                  <div>
                     <small>Sevimliga</small>
                     <strong>{{ num(stats.summary.favorites) }}</strong>
                  </div>
                  <div>
                     <small>Kuzatuv</small>
                     <strong>{{ num(stats.summary.saved_searches) }}</strong>
                  </div>
               </div>
               <div class="admin-card-header">
                  <h2 class="admin-card-title">Qurilma</h2>
               </div>
               <AdminRankList :rows="rows(stats.clients, clientLabel)" empty="Hali ma'lumot yo'q." />
            </section>
            <section class="admin-chart-card">
               <div class="admin-card-header">
                  <h2 class="admin-card-title">Til</h2>
               </div>
               <AdminRankList :rows="rows(stats.locales, localeLabel)" empty="Hali ma'lumot yo'q." color="#8b5cf6" />
            </section>
            <p class="admin-field-hint px-1">
               «kishi» har kun alohida sanaladi: 7 yoki 30 kunlik oraliqda bir odam har kuni kirsa, har kuni hisoblanadi.
               Shikoyatlar: {{ num(stats.summary.reports) }}.
            </p>
         </template>
      </template>
   </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import AdminRepo from "@admin/entities/AdminRepo";
import AdminTabs from "@admin/components/AdminTabs.vue";
import AdminRankList from "@admin/components/AdminRankList.vue";

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

const tabs = [
   { value: "overview", label: "Umumiy" },
   { value: "content", label: "Kontent" },
   { value: "audience", label: "Auditoriya" },
];
const tab = ref("overview");

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
