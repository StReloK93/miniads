<template>
   <section class="admin-page">
      <header class="admin-page-heading">
         <div>
            <h1>Boshqaruv paneli</h1>
            <p class="admin-muted">E'lonlar statistikasi, hududlar taqsimoti, kunlik dinamika va faol foydalanuvchilar.</p>
         </div>
         <RouterLink :to="{ name: 'admin-products' }">
            <BaseButton size="sm">
               <template #icon><FileText class="size-4" /></template>
               E'lonlarni boshqarish
            </BaseButton>
         </RouterLink>
      </header>

      <p v-if="error" class="admin-alert admin-alert-error">{{ error }}</p>
      <div v-if="loading" class="admin-panel admin-loading">Ma'lumotlar yuklanmoqda…</div>

      <template v-else-if="dashboard">
         <!-- KPI Cards Grid -->
         <div class="admin-stats-grid mb-5">
            <article class="admin-stat-card">
               <div class="flex items-center justify-between">
                  <span class="admin-stat-label">Jami e'lonlar</span>
                  <Layers class="size-4 text-(--z-primary)" />
               </div>
               <strong>{{ dashboard.stats.products?.toLocaleString() ?? 0 }}</strong>
               <div class="flex items-center gap-2 mt-1">
                  <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600">
                     <span class="size-1.5 rounded-full bg-emerald-500"></span>
                     {{ dashboard.stats.active_products ?? 0 }} faol
                  </span>
                  <span class="text-[11px] text-(--z-muted-text)">·</span>
                  <span class="text-[11px] text-(--z-muted-text)">
                     {{ dashboard.stats.deleted_products ?? 0 }} arxiv
                  </span>
               </div>
            </article>

            <article class="admin-stat-card">
               <div class="flex items-center justify-between">
                  <span class="admin-stat-label">Jami ko'rishlar</span>
                  <Eye class="size-4 text-sky-500" />
               </div>
               <strong>{{ (dashboard.stats.total_views ?? 0).toLocaleString() }}</strong>
               <span class="admin-stat-detail">E'lonlar bo'yicha auditoriya qamrovi</span>
            </article>

            <article class="admin-stat-card">
               <div class="flex items-center justify-between">
                  <span class="admin-stat-label">Foydalanuvchilar</span>
                  <Users class="size-4 text-violet-500" />
               </div>
               <strong>{{ dashboard.stats.users?.toLocaleString() ?? 0 }}</strong>
               <div class="flex items-center gap-2 mt-1">
                  <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600">
                     <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                     {{ dashboard.stats.active_users_today ?? 0 }} bugun faol
                  </span>
                  <span class="text-[11px] text-(--z-muted-text)">·</span>
                  <span class="text-[11px] text-(--z-muted-text)">
                     +{{ dashboard.stats.new_users_week ?? 0 }} bu hafta
                  </span>
               </div>
            </article>

            <article class="admin-stat-card">
               <div class="flex items-center justify-between">
                  <span class="admin-stat-label">Katalog bo'limlari</span>
                  <FolderTree class="size-4 text-amber-500" />
               </div>
               <strong>{{ dashboard.stats.categories ?? 0 }}</strong>
               <span class="admin-stat-detail">
                  Eng faol kun: <b class="text-(--z-primary)">{{ dashboard.stats.busiest_day ?? '—' }}</b>
               </span>
            </article>
         </div>

         <!-- Row 1: Kunlik e'lonlar dinamikasi & Hududlar kesimi -->
         <div class="admin-dashboard-grid">
            <!-- Kunlik e'lonlar Bar Chart -->
            <section class="admin-chart-card">
               <div class="admin-card-header">
                  <div>
                     <h2 class="admin-card-title flex items-center gap-2">
                        <Calendar class="size-4 text-(--z-primary)" />
                        E'lonlar dinamikasi
                     </h2>
                     <p class="admin-card-subtitle">So'nggi 14 kunlik e'lonlar soni va eng faol kunlar</p>
                  </div>
                  <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-md bg-(--z-muted) text-(--z-primary)">
                     <Flame class="size-3.5 text-amber-500" />
                     {{ dashboard.stats.busiest_day }} ko'proq
                  </span>
               </div>

               <!-- Bar Chart -->
               <div class="admin-bar-chart mt-auto">
                  <div
                     v-for="day in dashboard.daily_ads"
                     :key="day.date"
                     class="admin-bar-col group"
                     :class="{ 'is-peak': isPeakDay(day.count) }"
                  >
                     <span class="admin-bar-tooltip">{{ day.count }} ta</span>
                     <div
                        class="admin-bar-pill"
                        :style="{ height: getBarHeight(day.count) }"
                     ></div>
                     <span class="admin-bar-label">{{ day.day_name }}</span>
                  </div>
               </div>

               <!-- Haftalik xulosa pills -->
               <div class="mt-8 pt-3 border-t border-(--z-border) flex items-center justify-between text-xs text-(--z-muted-text) flex-wrap gap-2">
                  <div
                     v-for="item in dashboard.weekly_distribution"
                     :key="item.day"
                     class="flex items-center gap-1"
                  >
                     <span class="font-medium" :class="item.day === dashboard.stats.busiest_day ? 'text-(--z-primary) font-bold' : ''">{{ item.day.slice(0, 2) }}:</span>
                     <span class="font-semibold text-(--z-foreground)">{{ item.count }}</span>
                  </div>
               </div>
            </section>

            <!-- Hududlar / Tumanlar taqsimoti -->
            <section class="admin-chart-card">
               <div class="admin-card-header">
                  <div>
                     <h2 class="admin-card-title flex items-center gap-2">
                        <MapPin class="size-4 text-emerald-600" />
                        Hududlar kesimida e'lonlar
                     </h2>
                     <p class="admin-card-subtitle">Qaysi hudud yoki tumanda e'lonlar ko'proq joylanayotgani</p>
                  </div>
                  <span class="text-xs text-(--z-muted-text) font-semibold">
                     {{ dashboard.districts_breakdown.length }} ta hudud
                  </span>
               </div>

               <div class="admin-rank-list overflow-y-auto max-h-[220px] pr-1">
                  <div
                     v-for="(district, index) in dashboard.districts_breakdown"
                     :key="district.name"
                     class="admin-rank-item"
                  >
                     <div class="admin-rank-header">
                        <span class="admin-rank-name flex items-center gap-2">
                           <span class="size-5 rounded-md text-[10px] font-bold flex items-center justify-center bg-(--z-muted) text-(--z-muted-text)">
                              {{ index + 1 }}
                           </span>
                           {{ district.name }}
                        </span>
                        <div class="flex items-center gap-2">
                           <span class="font-bold text-(--z-foreground)">{{ district.count }} ta</span>
                           <span class="admin-rank-count">({{ district.percentage }}%)</span>
                        </div>
                     </div>
                     <div class="admin-rank-track">
                        <div
                           class="admin-rank-fill"
                           :style="{ width: `${district.percentage}%` }"
                        ></div>
                     </div>
                  </div>

                  <p v-if="dashboard.districts_breakdown.length === 0" class="text-xs text-(--z-muted-text) py-4 text-center">
                     Hududlar bo'yicha ma'lumot topilmadi.
                  </p>
               </div>
            </section>
         </div>

         <!-- Row 2: Kategoriyalar taqsimoti & Foydalanuvchilar faolligi -->
         <div class="admin-dashboard-grid">
            <!-- Kategoriyalar taqsimoti -->
            <section class="admin-chart-card">
               <div class="admin-card-header">
                  <div>
                     <h2 class="admin-card-title flex items-center gap-2">
                        <Tag class="size-4 text-sky-500" />
                        Kategoriyalar bo'yicha taqsimot
                     </h2>
                     <p class="admin-card-subtitle">Eng ko'p e'lon berilayotgan katalog bo'limlari</p>
                  </div>
                  <RouterLink :to="{ name: 'admin-categories' }" class="admin-text-link">
                     Barcha bo'limlar
                  </RouterLink>
               </div>

               <div class="admin-rank-list overflow-y-auto max-h-[260px] pr-1">
                  <div
                     v-for="(cat, index) in dashboard.categories_breakdown"
                     :key="cat.id"
                     class="admin-rank-item"
                  >
                     <div class="admin-rank-header">
                        <span class="admin-rank-name flex items-center gap-2">
                           <span class="size-5 rounded-md text-[10px] font-bold flex items-center justify-center bg-(--z-muted) text-(--z-muted-text)">
                              {{ index + 1 }}
                           </span>
                           {{ cat.name }}
                        </span>
                        <div class="flex items-center gap-2">
                           <span class="font-bold text-(--z-foreground)">{{ cat.count }} ta</span>
                           <span class="admin-rank-count">({{ cat.percentage }}%)</span>
                        </div>
                     </div>
                     <div class="admin-rank-track">
                        <div
                           class="admin-rank-fill bg-sky-500!"
                           :style="{ width: `${cat.percentage}%` }"
                        ></div>
                     </div>
                  </div>

                  <p v-if="dashboard.categories_breakdown.length === 0" class="text-xs text-(--z-muted-text) py-4 text-center">
                     Kategoriyalar bo'yicha e'lonlar topilmadi.
                  </p>
               </div>
            </section>

            <!-- Foydalanuvchilar faolligi va yangi a'zolar -->
            <section class="admin-chart-card">
               <div class="admin-card-header">
                  <div>
                     <h2 class="admin-card-title flex items-center gap-2">
                        <Activity class="size-4 text-violet-500" />
                        Foydalanuvchilar faolligi
                     </h2>
                     <p class="admin-card-subtitle">So'nggi faol bo'lgan foydalanuvchilar va ularning vaqtlari</p>
                  </div>
                  <RouterLink :to="{ name: 'admin-users' }" class="admin-text-link">
                     Barcha userlar
                  </RouterLink>
               </div>

               <div class="grid grid-cols-3 gap-2 mb-3 pb-3 border-b border-(--z-border) text-center">
                  <div class="p-2 rounded-lg bg-(--z-muted)/40">
                     <span class="text-[10px] text-(--z-muted-text) block">Bugun faol</span>
                     <strong class="text-sm font-bold text-emerald-600">{{ dashboard.stats.active_users_today ?? 0 }}</strong>
                  </div>
                  <div class="p-2 rounded-lg bg-(--z-muted)/40">
                     <span class="text-[10px] text-(--z-muted-text) block">7 kunda faol</span>
                     <strong class="text-sm font-bold text-(--z-foreground)">{{ dashboard.stats.active_users_week ?? 0 }}</strong>
                  </div>
                  <div class="p-2 rounded-lg bg-(--z-muted)/40">
                     <span class="text-[10px] text-(--z-muted-text) block">Yangi qo'shilgan</span>
                     <strong class="text-sm font-bold text-violet-600">+{{ dashboard.stats.new_users_week ?? 0 }}</strong>
                  </div>
               </div>

               <div class="admin-user-activity-list overflow-y-auto max-h-[190px] pr-1">
                  <div
                     v-for="user in dashboard.recent_users"
                     :key="user.id"
                     class="admin-user-activity-item"
                  >
                     <div class="flex items-center gap-2.5 min-w-0">
                        <div class="relative shrink-0">
                           <div class="size-8 rounded-full bg-(--z-muted) text-(--z-foreground) font-bold text-xs flex items-center justify-center">
                              {{ user.name?.charAt(0)?.toUpperCase() || 'U' }}
                           </div>
                           <span
                              class="absolute -bottom-0.5 -right-0.5 size-2.5 rounded-full border-2 border-(--z-card)"
                              :class="isRecentlyActive(user.last_active_at) ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600'"
                           ></span>
                        </div>
                        <div class="min-w-0">
                           <strong class="text-xs font-semibold block truncate text-(--z-foreground)">
                              {{ user.name }}
                           </strong>
                           <small class="text-[10px] text-(--z-muted-text) block truncate">
                              {{ user.username ? `@${user.username}` : `ID: ${user.id}` }}
                              <span v-if="user.district_name"> · {{ user.district_name }}</span>
                           </small>
                        </div>
                     </div>

                     <div class="text-right shrink-0">
                        <span class="text-[11px] font-semibold text-(--z-foreground) block">
                           {{ timeAgo(user.last_active_at) || 'Hozirgina' }}
                        </span>
                        <small class="text-[10px] text-(--z-muted-text) block">
                           {{ user.products_count }} ta e'lon
                        </small>
                     </div>
                  </div>

                  <p v-if="dashboard.recent_users.length === 0" class="text-xs text-(--z-muted-text) py-4 text-center">
                     Foydalanuvchilar topilmadi.
                  </p>
               </div>
            </section>
         </div>

         <!-- Row 3: So'nggi kiritilgan e'lonlar jadvali -->
         <section class="admin-panel admin-table-panel">
            <div class="admin-toolbar flex items-center justify-between border-b border-(--z-border) px-4 py-3">
               <div>
                  <h2 class="font-bold text-sm text-(--z-foreground)">So'nggi joylangan e'lonlar</h2>
                  <p class="text-xs text-(--z-muted-text)">Oxirgi kiritilgan e'lonlar va ularning moderatsiya holati.</p>
               </div>
               <RouterLink class="admin-text-link text-xs font-bold" :to="{ name: 'admin-products' }">
                  Barcha e'lonlarni ko'rish →
               </RouterLink>
            </div>

            <div class="admin-table-wrap">
               <table class="admin-table">
                  <thead>
                     <tr>
                        <th>E'lon</th>
                        <th>Muallif</th>
                        <th>Kategoriya</th>
                        <th>Hudud</th>
                        <th>Narx</th>
                        <th>Holati</th>
                     </tr>
                  </thead>
                  <tbody>
                     <tr v-for="product in dashboard.recent_products" :key="product.id">
                        <td>
                           <strong>{{ product.title }}</strong>
                           <small>#{{ product.id }} · {{ formatDate(product.created_at) }}</small>
                        </td>
                        <td>{{ product.user?.name || "—" }}</td>
                        <td>{{ product.category?.name || "—" }}</td>
                        <td>{{ product.district?.name || "—" }}</td>
                        <td>{{ formatPrice(product.price) }}</td>
                        <td>
                           <span
                              class="admin-badge"
                              :class="product.deleted_at ? 'admin-badge-muted' : product.is_active ? 'admin-badge-success' : 'admin-badge-warning'"
                           >
                              {{ product.deleted_at ? "Arxivlangan" : product.is_active ? "Faol" : "Muddati tugagan" }}
                           </span>
                        </td>
                     </tr>
                     <tr v-if="dashboard.recent_products.length === 0">
                        <td colspan="6" class="admin-empty-cell">Hozircha e'lonlar yo'q.</td>
                     </tr>
                  </tbody>
               </table>
            </div>
         </section>
      </template>
   </section>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from "vue";
import { FileText, Layers, Eye, Users, FolderTree, Calendar, MapPin, Tag, Activity, Flame } from "lucide-vue-next";
import BaseButton from "@shared/ui/BaseButton.vue";
import AdminRepo from "@admin/entities/AdminRepo";
import { formatPrice as formatPriceUtil, formatDate, timeAgo } from "@shared/modules/formatters";

type Dashboard = {
   stats: Record<string, any>;
   districts_breakdown: Array<{ name: string; count: number; percentage: number }>;
   categories_breakdown: Array<{ id: number; name: string; count: number; percentage: number }>;
   daily_ads: Array<{ date: string; day_label: string; day_name: string; count: number }>;
   weekly_distribution: Array<{ day: string; count: number }>;
   recent_users: Array<Record<string, any>>;
   recent_products: Array<Record<string, any>>;
};

const dashboard = ref<Dashboard | null>(null);
const loading = ref(true);
const error = ref("");

const maxDailyCount = computed(() => {
   if (!dashboard.value?.daily_ads?.length) return 1;
   const max = Math.max(...dashboard.value.daily_ads.map((d) => d.count));
   return max > 0 ? max : 1;
});

function getBarHeight(count: number): string {
   if (count <= 0) return "6px";
   const pct = Math.max(10, Math.round((count / maxDailyCount.value) * 100));
   return `${pct}%`;
}

function isPeakDay(count: number): boolean {
   return count > 0 && count === maxDailyCount.value;
}

function isRecentlyActive(dateString: string | null | undefined): boolean {
   if (!dateString) return false;
   const diffMs = Date.now() - new Date(dateString).getTime();
   return diffMs < 1000 * 60 * 60 * 24; // Oxirgi 24 soat ichida
}

function formatPrice(value: number | null) {
   return formatPriceUtil(value, { fallback: "Kelishiladi", unit: "so'm" });
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
