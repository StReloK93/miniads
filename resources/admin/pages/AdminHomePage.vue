<template>
   <section class="adm-page">
      <AdminDateBar v-model="selection" class="adm-sticky" />

      <p v-if="error" class="admin-alert admin-alert-error">
         {{ error }}
         <button type="button" class="admin-text-link ml-2" @click="load">Qayta urinish</button>
      </p>

      <template v-if="data">
         <!-- Diqqat talab qiladigan narsalar (faqat bor bo'lsa) -->
         <div v-if="data.attention.open_reports || data.attention.struggles" class="adm-attention">
            <RouterLink
               v-if="data.attention.open_reports"
               :to="{ name: 'admin-products', query: { status: 'reported' } }"
               class="adm-attention-item is-danger"
            >
               <Flag class="size-4" aria-hidden="true" />
               <span><b>{{ data.attention.open_reports }}</b> ta e'longa shikoyat</span>
               <ChevronRight class="size-4 ml-auto opacity-60" aria-hidden="true" />
            </RouterLink>
            <RouterLink
               v-if="data.attention.struggles"
               :to="{ name: 'admin-activity', query: dateQuery }"
               class="adm-attention-item is-warning"
            >
               <TriangleAlert class="size-4" aria-hidden="true" />
               <span><b>{{ data.attention.struggles }}</b> kishi e'lon qo'ya olmadi</span>
               <ChevronRight class="size-4 ml-auto opacity-60" aria-hidden="true" />
            </RouterLink>
         </div>

         <!--
            Bitta grafik — hamma ko'rsatkich. Tepadagi tugmalar: tanlangan kunning soni va oldingi kunga farqi;
            bosilsa grafik shu ko'rsatkichning 14 kunini ko'rsatadi. Ustunni bosish — o'sha kun ochiladi.
         -->
         <section class="adm-card adm-trend" :class="{ 'is-loading': loading }">
            <div class="adm-metrics" role="tablist" aria-label="Ko'rsatkich">
               <button
                  v-for="metric in metrics"
                  :key="metric.key"
                  type="button"
                  role="tab"
                  class="adm-metric"
                  :class="{ 'is-active': trendMetric === metric.key }"
                  :aria-selected="trendMetric === metric.key"
                  @click="trendMetric = metric.key"
               >
                  <span class="adm-metric-label">
                     <component :is="metric.icon" class="size-3.5" aria-hidden="true" />{{ metric.label }}
                  </span>
                  <span class="adm-metric-value">{{ num(metric.value) }}</span>
                  <span class="adm-delta" :class="deltaClass(metric.value, metric.previous)">{{ deltaText(metric.value, metric.previous) }}</span>
               </button>
            </div>

            <div class="adm-trend-head">
               <span>
                  <b>{{ activeMetric?.label }}</b> · 14 kun
                  <template v-if="activeMetric?.note"> · {{ activeMetric.note }}</template>
               </span>
               <RouterLink v-if="activeMetric?.to" class="adm-link" :to="activeMetric.to">Ro'yxat</RouterLink>
            </div>

            <!-- Bosilgan ustun: sana va son (kunga o'tmaydi — kun tepadagi kalendardan tanlanadi) -->
            <div class="adm-inspect" aria-live="polite">
               <span>{{ longLabel(inspectedRow?.date ?? data.date) }}</span>
               <b>{{ num(inspectedRow ? trendValue(inspectedRow) : 0) }}</b>
               <small v-if="trendMetric === 'ads' && inspectedRow?.ads">
                  {{ inspectedRow.ads - inspectedRow.ads_ai }} odam · {{ inspectedRow.ads_ai }} AI
               </small>
            </div>

            <div class="adm-bars" role="list">
               <button
                  v-for="row in data.trend"
                  :key="row.date"
                  type="button"
                  role="listitem"
                  class="adm-bar"
                  :class="{ 'is-selected': row.date === inspectedDate, 'is-day': row.date === data.date }"
                  :aria-label="`${longLabel(row.date)}: ${trendValue(row)}`"
                  :aria-pressed="row.date === inspectedDate"
                  @click="inspected = row.date"
               >
                  <span class="adm-bar-value">{{ trendValue(row) || "" }}</span>
                  <span class="adm-bar-track">
                     <span class="adm-bar-fill" :style="{ height: `${barHeight(trendValue(row), trendMax)}%` }">
                        <span
                           v-if="trendMetric === 'ads' && row.ads_ai"
                           class="adm-bar-ai"
                           :style="{ height: `${(row.ads_ai / Math.max(row.ads, 1)) * 100}%` }"
                        />
                     </span>
                  </span>
                  <span class="adm-bar-label">{{ dayOfMonth(row.date) }}</span>
               </button>
            </div>
            <p class="adm-legend">
               <template v-if="trendMetric === 'ads'">
                  <span><i class="is-people" />Odamlar</span>
                  <span><i class="is-ai" />AI (kanallardan)</span>
               </template>
               <span class="ml-auto">▲▼ — oldingi kunga nisbatan · ustunni bosing — sonini ko'rasiz</span>
            </p>
         </section>

         <p class="adm-footnote">
            <span>Sotildi <b>{{ num(data.metrics.sold) }}</b></span>
            <span>Sevimlilarga <b>{{ num(data.metrics.favorites) }}</b></span>
            <span>Shikoyat <b>{{ num(data.metrics.reports) }}</b></span>
            <span>Natijasiz qidiruv <b>{{ num(data.metrics.searches_empty) }}</b></span>
         </p>

         <!-- Soatlar bo'yicha: kun ichida qachon e'lon ko'p tushdi -->
         <section v-if="data.metrics.ads" class="adm-card">
            <header class="adm-card-head">
               <h2>Soatlar bo'yicha e'lonlar</h2>
               <span class="adm-card-meta">eng faol: {{ peakHour }}:00</span>
            </header>
            <div class="adm-hours" aria-hidden="true">
               <span
                  v-for="(count, hour) in data.hours"
                  :key="hour"
                  class="adm-hour"
                  :class="{ 'is-peak': count === hoursMax && count > 0 }"
                  :style="{ height: `${barHeight(count, hoursMax)}%` }"
                  :title="`${hour}:00 — ${count}`"
               />
            </div>
            <div class="adm-hours-axis" aria-hidden="true"><span>0</span><span>6</span><span>12</span><span>18</span><span>23</span></div>
         </section>

         <div class="adm-grid-2">
            <!-- Shu kungi e'lonlar -->
            <section class="adm-card">
               <header class="adm-card-head">
                  <h2>E'lonlar · {{ relativeLabel(data.date) }}</h2>
                  <RouterLink
                     v-if="data.metrics.ads"
                     class="adm-link"
                     :to="{ name: 'admin-products', query: { date: data.date } }"
                  >
                     Hammasi {{ data.metrics.ads }}
                  </RouterLink>
               </header>
               <ul v-if="data.products.length" class="adm-rows">
                  <li v-for="product in data.products" :key="product.id">
                     <RouterLink :to="{ name: 'admin-products', query: { search: String(product.id), open: product.id } }" class="adm-row">
                        <img v-if="product.image" :src="product.image" alt="" class="adm-thumb" loading="lazy" />
                        <span v-else class="adm-thumb"><ImageOff class="size-4" /></span>
                        <span class="adm-row-main">
                           <span class="adm-row-title">{{ product.title }}</span>
                           <span class="adm-row-meta">
                              {{ time(product.created_at) }} · {{ product.category || "—" }}
                              <template v-if="product.district"> · {{ product.district }}</template>
                           </span>
                        </span>
                        <span v-if="product.is_bot" class="admin-badge admin-badge-info">AI</span>
                        <span v-else-if="product.deleted" class="admin-badge admin-badge-muted">Arxiv</span>
                     </RouterLink>
                  </li>
               </ul>
               <p v-else class="adm-empty">Bu kuni e'lon joylanmagan.</p>
            </section>

            <!-- Shu kuni qo'shilganlar -->
            <section class="adm-card">
               <header class="adm-card-head">
                  <h2>Yangi foydalanuvchilar</h2>
                  <RouterLink
                     v-if="data.metrics.new_users"
                     class="adm-link"
                     :to="{ name: 'admin-users', query: { date: data.date } }"
                  >
                     Hammasi {{ data.metrics.new_users }}
                  </RouterLink>
               </header>
               <ul v-if="data.users.length" class="adm-rows">
                  <li v-for="user in data.users" :key="user.id">
                     <RouterLink :to="{ name: 'admin-users', query: { search: user.username || user.name, open: user.id } }" class="adm-row">
                        <span class="adm-row-main">
                           <span class="adm-row-title">{{ user.name }}</span>
                           <span class="adm-row-meta">
                              {{ time(user.created_at) }}
                              <template v-if="user.district"> · {{ user.district }}</template>
                              <template v-if="user.products_count"> · {{ user.products_count }} e'lon</template>
                           </span>
                        </span>
                        <span v-if="user.bot" class="admin-badge admin-badge-success">Bot</span>
                     </RouterLink>
                  </li>
               </ul>
               <p v-else class="adm-empty">Bu kuni hech kim qo'shilmagan.</p>
            </section>

            <!-- Kategoriyalar -->
            <section v-if="data.categories.length" class="adm-card">
               <header class="adm-card-head"><h2>Qaysi bo'limlarga</h2></header>
               <ul class="adm-ranks">
                  <li v-for="category in data.categories" :key="category.id">
                     <RouterLink :to="{ name: 'admin-products', query: { date: data.date, category_id: category.id } }" class="adm-rank">
                        <span class="adm-rank-name">{{ category.name }}</span>
                        <span class="adm-rank-count">{{ category.count }}</span>
                        <span class="adm-rank-track"><span :style="{ width: `${barHeight(category.count, data.categories[0].count)}%` }" /></span>
                     </RouterLink>
                  </li>
               </ul>
            </section>

            <!-- Qidiruvlar -->
            <section v-if="data.searches.length" class="adm-card">
               <header class="adm-card-head">
                  <h2>Nimani qidirishdi</h2>
                  <RouterLink class="adm-link" :to="{ name: 'admin-search-stats', query: dateQuery }">Batafsil</RouterLink>
               </header>
               <ul class="adm-rows is-compact">
                  <li v-for="search in data.searches" :key="search.query" class="adm-row">
                     <Search class="size-4 shrink-0 opacity-50" aria-hidden="true" />
                     <span class="adm-row-main"><span class="adm-row-title">{{ search.query }}</span></span>
                     <span v-if="search.empty" class="admin-badge admin-badge-warning">topilmadi</span>
                     <span class="adm-count">{{ search.count }}</span>
                  </li>
               </ul>
            </section>
         </div>

         <p class="adm-footnote is-center">
            Hozir: <b>{{ num(data.totals.active_products) }}</b> faol e'lon ·
            <b>{{ num(data.totals.users) }}</b> foydalanuvchi ·
            botda <b>{{ num(data.totals.bot_reachable) }}</b>
         </p>
      </template>

      <div v-else-if="loading" class="adm-skeleton" aria-label="Yuklanmoqda">
         <span v-for="index in 6" :key="index" />
      </div>
   </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { RouterLink } from "vue-router";
import {
   ChevronRight,
   Eye,
   FileText,
   Flag,
   ImageOff,
   PhoneCall,
   Search,
   TriangleAlert,
   UserPlus,
   Users,
} from "lucide-vue-next";
import AdminDateBar from "@admin/components/AdminDateBar.vue";
import AdminRepo from "@admin/entities/AdminRepo";
import { dayOfMonth, longLabel, relativeLabel, todayKey } from "@admin/modules/dates";
import { useDateSelection } from "@admin/modules/useDateSelection";

type Metrics = {
   ads: number;
   ads_people: number;
   ads_ai: number;
   new_users: number;
   visitors: number;
   product_views: number;
   contacts: number;
   favorites: number;
   searches: number;
   searches_empty: number;
   sold: number;
   reports: number;
};
type TrendRow = {
   date: string;
   ads: number;
   ads_ai: number;
   new_users: number;
   visitors: number;
   product_views: number;
   contacts: number;
   searches: number;
};
type DayReport = {
   date: string;
   is_today: boolean;
   metrics: Metrics;
   previous: Metrics;
   trend: TrendRow[];
   hours: number[];
   products: {
      id: number;
      title: string;
      is_bot: boolean;
      deleted: boolean;
      created_at: string;
      category: string | null;
      district: string | null;
      image: string | null;
   }[];
   users: {
      id: number;
      name: string;
      username: string | null;
      district: string | null;
      products_count: number;
      bot: boolean;
      created_at: string;
   }[];
   categories: { id: number; name: string; count: number }[];
   searches: { query: string; count: number; empty: boolean }[];
   attention: { open_reports: number; struggles: number };
   totals: { active_products: number; users: number; bot_reachable: number };
};

const { selection } = useDateSelection();
const data = ref<DayReport | null>(null);
const loading = ref(true);
const error = ref("");

const date = computed(() => selection.value.date ?? todayKey());
const dateQuery = computed(() => (date.value === todayKey() ? {} : { date: date.value }));

async function load() {
   loading.value = true;
   error.value = "";
   try {
      const { data: response } = await AdminRepo.day(date.value);
      // Server eski bo'lsa yoki boshqa narsa qaytarsa (masalan HTML) — sahifa yiqilmasin, sababini ko'rsatamiz
      if (!response || typeof response !== "object" || !response.metrics || !response.attention) {
         throw new Error("Kutilmagan javob: /api/admin/day");
      }
      data.value = response;
   } catch (exception) {
      console.error("Kunlik hisobot yuklanmadi.", exception);
      error.value = "Ma'lumot yuklanmadi. Server yangilanmagan bo'lishi mumkin: serverda php artisan optimize:clear bajaring.";
   } finally {
      loading.value = false;
   }
}

watch(date, load);
onMounted(load);

// Grafikda bosilgan ustun (faqat sonini ko'rsatish uchun); bo'sh — tanlangan kun
const inspected = ref<string | null>(null);
const inspectedDate = computed(() => inspected.value ?? data.value?.date ?? null);
const inspectedRow = computed(() => data.value?.trend.find((row) => row.date === inspectedDate.value) ?? null);
// Boshqa kun yuklanganda — yana o'sha kun ko'rsatiladi
watch(date, () => (inspected.value = null));

type TrendMetric = "ads" | "new_users" | "visitors" | "product_views" | "contacts" | "searches";

// Grafikning ko'rsatkichlari: tanlangan kun qiymati, oldingi kun va ro'yxatga havola
const metrics = computed(() => {
   if (!data.value) return [];
   const { metrics: m, previous: p } = data.value;
   const dayLink = (name: string) => ({ name, query: { date: data.value!.date } });
   const list: { key: TrendMetric; label: string; icon: any; value: number; previous: number; note: string; to: any }[] = [
      {
         key: "ads",
         label: "E'lonlar",
         icon: FileText,
         value: m.ads,
         previous: p.ads,
         note: m.ads ? `shu kuni ${m.ads_people} odam, ${m.ads_ai} AI` : "",
         to: dayLink("admin-products"),
      },
      { key: "new_users", label: "Yangi odamlar", icon: UserPlus, value: m.new_users, previous: p.new_users, note: "", to: dayLink("admin-users") },
      {
         key: "visitors",
         label: "Kirganlar",
         icon: Users,
         value: m.visitors,
         previous: p.visitors,
         note: "",
         to: { name: "admin-activity", query: dateQuery.value },
      },
      { key: "product_views", label: "Ko'rishlar", icon: Eye, value: m.product_views, previous: p.product_views, note: "", to: null },
      { key: "contacts", label: "Bog'lanish", icon: PhoneCall, value: m.contacts, previous: p.contacts, note: "qo'ng'iroq + Telegram", to: null },
      {
         key: "searches",
         label: "Qidiruvlar",
         icon: Search,
         value: m.searches,
         previous: p.searches,
         note: m.searches_empty ? `${m.searches_empty} natijasiz` : "",
         to: { name: "admin-search-stats", query: dateQuery.value },
      },
   ];
   return list;
});

const trendMetric = ref<TrendMetric>("ads");
const activeMetric = computed(() => metrics.value.find((metric) => metric.key === trendMetric.value));
const trendValue = (row: TrendRow) => row[trendMetric.value] ?? 0;
const trendMax = computed(() => Math.max(1, ...(data.value?.trend ?? []).map(trendValue)));

const hoursMax = computed(() => Math.max(0, ...(data.value?.hours ?? [])));
const peakHour = computed(() => String(data.value?.hours.indexOf(hoursMax.value) ?? 0).padStart(2, "0"));

function barHeight(value: number, max: number) {
   if (!value || !max) return 0;
   return Math.max(6, Math.round((value / max) * 100));
}

const num = (value: number) => (value ?? 0).toLocaleString("ru-RU");

function deltaText(value: number, previous: number) {
   const diff = value - previous;
   if (diff === 0) return "o'zgarishsiz";
   return `${diff > 0 ? "▲" : "▼"} ${Math.abs(diff).toLocaleString("ru-RU")}`;
}

function deltaClass(value: number, previous: number) {
   if (value === previous) return "is-flat";
   return value > previous ? "is-up" : "is-down";
}

function time(iso: string) {
   return new Date(iso).toLocaleTimeString("ru-RU", { hour: "2-digit", minute: "2-digit" });
}
</script>
