<template>
   <section class="admin-page">
      <header class="admin-page-heading">
         <div>
            <h1>Boshqaruv paneli</h1>
            <p class="admin-muted">E'lonlar, hududlar va foydalanuvchilar bo'yicha qisqa ko'rinish.</p>
         </div>
      </header>

      <p v-if="error" class="admin-alert admin-alert-error">{{ error }}</p>
      <div v-if="loading" class="admin-panel admin-loading">Ma'lumotlar yuklanmoqda…</div>

      <template v-else-if="dashboard">
         <!-- Moderatsiya navbati: ko'rib chiqilmagan shikoyatlar birinchi ko'zga tashlansin -->
         <RouterLink
            v-if="dashboard.stats.reported_products"
            :to="{ name: 'admin-products', query: { status: 'reported' } }"
            class="admin-alert admin-alert-warning"
         >
            <Flag class="size-4 shrink-0" />
            <span class="grow"><b>{{ dashboard.stats.reported_products }} ta e'lon</b> ustidan shikoyat bor</span>
            <ChevronRight class="size-4 shrink-0" />
         </RouterLink>

         <AdminTabs v-model="tab" :tabs="tabs" />

         <!-- UMUMIY: asosiy raqamlar va 14 kunlik dinamika -->
         <template v-if="tab === 'overview'">
            <div class="admin-stats-grid">
               <article class="admin-stat-card">
                  <span class="admin-stat-label">E'lonlar</span>
                  <strong>{{ num(dashboard.stats.products) }}</strong>
                  <span class="admin-stat-detail">
                     <b class="text-emerald-600">{{ num(dashboard.stats.active_products) }} faol</b>
                     · {{ num(dashboard.stats.expired_products) }} tugagan
                  </span>
               </article>
               <article class="admin-stat-card">
                  <span class="admin-stat-label">Bugun qo'shildi</span>
                  <strong>{{ num(todayAds) }}</strong>
                  <span class="admin-stat-detail">14 kunda {{ num(totalAds14) }} ta</span>
               </article>
               <article class="admin-stat-card">
                  <span class="admin-stat-label">Foydalanuvchilar</span>
                  <strong>{{ num(dashboard.stats.users) }}</strong>
                  <span class="admin-stat-detail">
                     <b class="text-emerald-600">{{ num(dashboard.stats.active_users_today) }} bugun</b>
                     · +{{ num(dashboard.stats.new_users_week) }} hafta
                  </span>
               </article>
               <article class="admin-stat-card">
                  <span class="admin-stat-label">Ko'rishlar</span>
                  <strong>{{ num(dashboard.stats.total_views) }}</strong>
                  <span class="admin-stat-detail">barcha e'lonlar bo'yicha</span>
               </article>
            </div>

            <section class="admin-chart-card">
               <div class="admin-card-header">
                  <div>
                     <h2 class="admin-card-title">Kunlik yangi e'lonlar</h2>
                     <p class="admin-card-subtitle">So'nggi 14 kun</p>
                  </div>
                  <span v-if="dashboard.stats.busiest_day" class="admin-badge admin-badge-muted shrink-0">
                     <Flame class="size-3 text-amber-500" /> {{ dashboard.stats.busiest_day }}
                  </span>
               </div>

               <div class="admin-bar-chart">
                  <div
                     v-for="day in dashboard.daily_ads"
                     :key="day.date"
                     class="admin-bar-col"
                     :class="{ 'is-peak': isPeakDay(day.count) }"
                     :title="`${day.day_label}: ${day.count} ta`"
                  >
                     <span class="admin-bar-tooltip">{{ day.count }}</span>
                     <div class="admin-bar-pill" :style="{ height: getBarHeight(day.count) }" />
                     <span class="admin-bar-label">{{ day.day_name.slice(0, 2) }}</span>
                  </div>
               </div>
            </section>

            <RouterLink :to="{ name: 'admin-activity' }" class="admin-panel admin-list-row px-3.5!">
               <Activity class="size-4.5 text-(--z-primary) shrink-0" />
               <span class="admin-list-main">
                  <span class="admin-list-title">Faollik statistikasi</span>
                  <span class="admin-list-meta">Odamlar nimani ko'ryapti va qayerdan kelyapti</span>
               </span>
               <ChevronRight class="size-4 text-(--admin-muted) shrink-0" />
            </RouterLink>
         </template>

         <!-- E'LONLAR: so'nggilari va taqsimot -->
         <template v-else-if="tab === 'ads'">
            <section class="admin-chart-card">
               <div class="admin-card-header">
                  <h2 class="admin-card-title">So'nggi e'lonlar</h2>
                  <RouterLink class="admin-text-link" :to="{ name: 'admin-products' }">Hammasi</RouterLink>
               </div>
               <div class="admin-list">
                  <RouterLink
                     v-for="product in dashboard.recent_products"
                     :key="product.id"
                     :to="{ name: 'admin-products', query: { search: `#${product.id}` } }"
                     class="admin-list-row"
                  >
                     <span class="admin-list-main">
                        <span class="admin-list-title">{{ product.title }}</span>
                        <span class="admin-list-meta">
                           {{ [product.category?.name, product.district?.name, priceOf(product)].filter(Boolean).join(" · ") }}
                        </span>
                        <span class="admin-list-meta">
                           #{{ product.id }} · {{ product.user?.name || "—" }} · {{ timeAgo(product.created_at) }} ·
                           {{ num(product.views_count) }} ko'rish
                        </span>
                     </span>
                     <span class="admin-badge shrink-0" :class="statusOf(product).class">{{ statusOf(product).label }}</span>
                  </RouterLink>
                  <p v-if="!dashboard.recent_products.length" class="admin-muted">Hozircha e'lonlar yo'q.</p>
               </div>
            </section>

            <section class="admin-chart-card">
               <div class="admin-card-header">
                  <h2 class="admin-card-title">Kategoriyalar</h2>
                  <RouterLink :to="{ name: 'admin-categories' }" class="admin-text-link">Katalog</RouterLink>
               </div>
               <AdminRankList
                  :rows="dashboard.categories_breakdown.map((c) => ({ label: c.name, value: c.count, detail: `${c.count} ta · ${c.percentage}%` }))"
                  empty="Hali e'lonlar yo'q."
               />
            </section>

            <section class="admin-chart-card">
               <div class="admin-card-header">
                  <h2 class="admin-card-title">Hududlar</h2>
               </div>
               <AdminRankList
                  :rows="dashboard.districts_breakdown.map((d) => ({ label: d.name, value: d.count, detail: `${d.count} ta · ${d.percentage}%` }))"
                  empty="Hududlar bo'yicha ma'lumot yo'q."
                  color="#10b981"
               />
            </section>
         </template>

         <!-- ODAMLAR: faollik va so'nggi kirganlar -->
         <template v-else>
            <section class="admin-chart-card">
               <div class="admin-mini-stats">
                  <div>
                     <small>Bugun faol</small>
                     <strong class="text-emerald-600!">{{ num(dashboard.stats.active_users_today) }}</strong>
                  </div>
                  <div>
                     <small>7 kunda faol</small>
                     <strong>{{ num(dashboard.stats.active_users_week) }}</strong>
                  </div>
                  <div>
                     <small>Yangi (hafta)</small>
                     <strong>+{{ num(dashboard.stats.new_users_week) }}</strong>
                  </div>
               </div>

               <div class="admin-card-header mb-0!">
                  <h2 class="admin-card-title">So'nggi kirganlar</h2>
                  <RouterLink :to="{ name: 'admin-users' }" class="admin-text-link">Hammasi</RouterLink>
               </div>
               <div class="admin-list">
                  <div v-for="user in dashboard.recent_users" :key="user.id" class="admin-list-row">
                     <span class="relative shrink-0">
                        <span class="admin-avatar size-8! text-xs!">{{ user.name?.charAt(0)?.toUpperCase() || "U" }}</span>
                        <span
                           class="absolute -bottom-0.5 -right-0.5 size-2.5 rounded-full border-2 border-(--z-card)"
                           :class="isRecentlyActive(user.last_active_at) ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600'"
                        />
                     </span>
                     <span class="admin-list-main">
                        <span class="admin-list-title">{{ user.name }}</span>
                        <span class="admin-list-meta">
                           {{ user.username ? `@${user.username}` : `ID: ${user.id}` }}
                           <template v-if="user.district_name"> · {{ user.district_name }}</template>
                        </span>
                     </span>
                     <span class="text-right shrink-0">
                        <span class="admin-list-title block text-xs!">
                           {{ user.last_active_at ? timeAgo(user.last_active_at) : "Kirmagan" }}
                        </span>
                        <span class="admin-list-meta block">{{ user.products_count }} ta e'lon</span>
                     </span>
                  </div>
                  <p v-if="!dashboard.recent_users.length" class="admin-muted">Foydalanuvchilar yo'q.</p>
               </div>
            </section>
         </template>
      </template>
   </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { Activity, ChevronRight, Flag, Flame } from "lucide-vue-next";
import AdminRepo from "@admin/entities/AdminRepo";
import AdminTabs from "@admin/components/AdminTabs.vue";
import AdminRankList from "@admin/components/AdminRankList.vue";
import { formatProductPrice, timeAgo } from "@shared/modules/formatters";

type Dashboard = {
   stats: Record<string, any>;
   districts_breakdown: Array<{ name: string; count: number; percentage: number }>;
   categories_breakdown: Array<{ id: number; name: string; count: number; percentage: number }>;
   daily_ads: Array<{ date: string; day_label: string; day_name: string; count: number }>;
   weekly_distribution: Array<{ day: string; count: number }>;
   recent_users: Array<Record<string, any>>;
   recent_products: Array<Record<string, any>>;
};

const tabs = [
   { value: "overview", label: "Umumiy" },
   { value: "ads", label: "E'lonlar" },
   { value: "people", label: "Odamlar" },
];
const tab = ref("overview");

const dashboard = ref<Dashboard | null>(null);
const loading = ref(true);
const error = ref("");

const num = (value: number | null | undefined) => Number(value ?? 0).toLocaleString("ru-RU");

const todayAds = computed(() => dashboard.value?.daily_ads.at(-1)?.count ?? 0);
const totalAds14 = computed(() => dashboard.value?.daily_ads.reduce((sum, day) => sum + day.count, 0) ?? 0);

const maxDailyCount = computed(() => Math.max(1, ...(dashboard.value?.daily_ads ?? []).map((d) => d.count)));

function getBarHeight(count: number): string {
   if (count <= 0) return "4px";
   return `${Math.max(8, Math.round((count / maxDailyCount.value) * 100))}%`;
}

function isPeakDay(count: number): boolean {
   return count > 0 && count === maxDailyCount.value;
}

function isRecentlyActive(dateString: string | null | undefined): boolean {
   if (!dateString) return false;
   return Date.now() - new Date(dateString).getTime() < 1000 * 60 * 60 * 24;
}

function priceOf(product: Record<string, any>) {
   return product.price ? formatProductPrice(product.price, product.price_type) : "";
}

function statusOf(product: Record<string, any>) {
   if (product.deleted_at) return { label: "Arxiv", class: "admin-badge-muted" };
   return product.is_active
      ? { label: "Faol", class: "admin-badge-success" }
      : { label: "Tugagan", class: "admin-badge-warning" };
}

onMounted(async () => {
   try {
      const { data } = await AdminRepo.dashboard();
      dashboard.value = data;
   } catch (exception) {
      console.error("Admin statistikasi yuklanmadi.", exception);
      error.value = "Statistikani yuklab bo'lmadi. Sahifani yangilab ko'ring.";
   } finally {
      loading.value = false;
   }
});
</script>
