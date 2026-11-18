<template>
   <section class="admin-page">
      <AdminUserJourney :user-id="journey?.userId ?? null" :date="journey?.date" @close="journey = null" />
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
                  <span v-if="stats.funnel.ai_drafts" class="admin-stat-detail">
                     matn bilan (AI): {{ num(stats.funnel.ai_created) }} qo'ydi / {{ num(stats.funnel.ai_drafts) }} urindi
                  </span>
               </article>
            </div>

            <!-- Qayerda qiynalyapti: bosilsa o'sha kungi yo'li ochiladi -->
            <section class="admin-chart-card">
               <div class="admin-card-header">
                  <h2 class="admin-card-title">E'lon qo'ya olmaganlar va xatolar</h2>
                  <span class="admin-card-subtitle">bosing — ilovadagi yo'li</span>
               </div>
               <ul v-if="stats.struggles.length" class="grid gap-1">
                  <li v-for="row in stats.struggles" :key="`${row.user_id}-${row.date}`">
                     <button
                        type="button"
                        class="w-full text-left rounded-lg px-2 py-2 -mx-2 hover:bg-(--z-muted)/60 cursor-pointer transition"
                        @click="journey = { userId: row.user_id, date: row.date }"
                     >
                        <span class="flex items-baseline justify-between gap-2">
                           <b class="min-w-0 truncate text-sm">{{ row.name }}</b>
                           <small class="shrink-0 text-xs text-(--z-muted-text) tabular-nums">{{ shortDate(row.date) }}</small>
                        </span>
                        <span class="block text-xs text-(--z-muted-text) mt-0.5" :class="{ 'text-(--z-danger)!': row.failed.length || row.errors.length }">
                           {{ struggleText(row) }}
                        </span>
                     </button>
                  </li>
               </ul>
               <p v-else class="admin-muted text-sm">Bu davrda hammasi joyida — muammo qayd etilmagan.</p>
               <p class="admin-field-hint mt-2">Faqat ro'yxatdan o'tganlar; batafsil yo'l oxirgi {{ stats.raw_days }} kun saqlanadi.</p>
            </section>

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
import AdminUserJourney from "@admin/components/AdminUserJourney.vue";
import { failedFieldsLabel, pageLabel, sourceLabel } from "@admin/modules/activityLabels";

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
   funnel: { started: number; created: number; ads: number; ai_drafts: number; ai_created: number };
   daily: { date: string; users: number; hits: number }[];
   pages: Group[];
   categories: Group[];
   products: Group[];
   contacts: { phone: number; telegram: number };
   sources: Group[];
   clients: Group[];
   locales: Group[];
   struggles: Struggle[];
   raw_days: number;
};
/** E'lon qo'ya olmagan / xatoga uchragan foydalanuvchi (bir kunda) */
type Struggle = {
   user_id: number;
   name: string;
   username: string | null;
   date: string;
   started: boolean;
   created: number;
   failed: string[];
   errors: string[];
   last_at: string | null;
};
type Row = { label: string; value: number; detail: string };

const CLIENTS: Record<string, string> = { telegram: "Telegram ilovasi", browser: "Brauzer" };
const LOCALES: Record<string, string> = { uz: "Til: O'zbekcha", ru: "Til: Русский", en: "Til: English" };

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

// Yo'li ochilgan foydalanuvchi va kun
const journey = ref<{ userId: number; date: string } | null>(null);

const shortDate = (date: string) => new Date(date).toLocaleDateString("ru-RU", { day: "2-digit", month: "2-digit" });

function struggleText(row: Struggle) {
   const parts: string[] = [];
   if (row.failed.length) parts.push(`Forma rad etildi: ${row.failed.map(failedFieldsLabel).join("; ")}`);
   if (row.started && !row.created) parts.push("Formani ochdi, joylamadi");
   if (row.errors.length) parts.push(`Xato: ${row.errors.join("; ")}`);
   return parts.join(" · ");
}

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
