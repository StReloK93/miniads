<template>
   <section class="admin-page">
      <AdminMessageDialog
         :open="messageTarget !== null"
         :recipient="messageTarget"
         @close="messageTarget = null"
         @sent="(text) => (notice = text)"
      />

      <BaseModal
         :open="confirmState !== null"
         :title="confirmState?.title"
         :description="confirmState?.description"
         :confirm-text="confirmState?.confirmText"
         cancel-text="Bekor qilish"
         :danger="confirmState?.danger"
         @close="confirmState = null"
         @confirm="runConfirm"
      >
         <template #icon>
            <component :is="confirmState?.danger ? Ban : ShieldCheck" class="size-5" :class="{ 'text-(--z-danger)': confirmState?.danger }" />
         </template>
         <p class="text-sm text-(--z-muted-text)">{{ confirmState?.note }}</p>
         <label v-if="confirmState?.withArchive" class="mt-3 flex items-center gap-2.5 text-sm cursor-pointer select-none">
            <input v-model="archiveProducts" type="checkbox" class="size-4 accent-(--z-danger)" />
            Hozirgi e'lonlarini ham arxivlash
         </label>
      </BaseModal>

      <header class="admin-page-heading">
         <div>
            <h1>Foydalanuvchilar</h1>
            <p class="admin-muted">Qatorni bosing — ma'lumotlar, e'lonlari, rol va bloklash.</p>
         </div>
      </header>

      <p v-if="error" class="admin-alert admin-alert-error">{{ error }}</p>
      <p v-if="notice" class="admin-alert admin-alert-success">{{ notice }}</p>

      <!-- Qaytib kirish: kunlar bo'yicha (bir kunda necha marta ochgani hisobga olinmaydi). Kartani bosish — filtr -->
      <div v-if="summary" class="admin-stats-grid">
         <button
            type="button"
            class="admin-stat-card"
            :class="{ 'is-active': visitsFilter === 'once' }"
            @click="setVisits('once')"
         >
            <span class="admin-stat-label">Bir marta kirib, qaytmagan</span>
            <strong>{{ num(summary.once) }}</strong>
            <span class="admin-stat-detail">{{ percent(summary.once) }} · faqat bitta kun kirgan</span>
         </button>
         <button
            type="button"
            class="admin-stat-card"
            :class="{ 'is-active': visitsFilter === 'returning' }"
            @click="setVisits('returning')"
         >
            <span class="admin-stat-label">Qayta-qayta kirayotgan</span>
            <strong>{{ num(summary.returning) }}</strong>
            <span class="admin-stat-detail">{{ percent(summary.returning) }} · 2+ kun · {{ num(summary.regular) }} tasi 5+ kun</span>
         </button>
         <article class="admin-stat-card">
            <span class="admin-stat-label">Shu hafta qaytganlar</span>
            <strong>{{ num(summary.returned_week) }}</strong>
            <span class="admin-stat-detail">oxirgi 7 kunda kirgan eski foydalanuvchilar</span>
         </article>
      </div>

      <section class="admin-panel admin-table-panel">
         <div class="admin-toolbar">
            <label class="admin-search">
               <Search class="size-4" aria-hidden="true" />
               <input
                  v-model="searchInput"
                  type="search"
                  placeholder="Ism, username yoki Telegram ID"
                  @keyup.enter="load(1)"
               />
            </label>
            <div class="sm:w-44">
               <FieldSelect
                  v-model="sort"
                  size="sm"
                  :options="[
                     { label: 'Yangi qo\'shilganlar', value: 'new' },
                     { label: 'Ko\'p kirganlar', value: 'visits' },
                     { label: 'Oxirgi faollar', value: 'active' },
                  ]"
                  @change="load(1)"
               />
            </div>
            <BaseButton severity="secondary" size="sm" @click="load(1)">
               <template #icon><Search class="size-3.5" /></template>
               Qidirish
            </BaseButton>
         </div>

         <nav class="admin-filter-chips" aria-label="Rol bo'yicha">
            <button
               v-for="chip in roleChips"
               :key="chip.value"
               type="button"
               class="admin-filter-chip pr-3!"
               :class="{ 'is-active': roleFilter === chip.value }"
               @click="setRole(chip.value)"
            >
               {{ chip.label }}
            </button>
         </nav>

         <div v-if="visitsFilter" class="admin-filter-note">
            <span class="min-w-0 truncate">
               Ko'rsatilmoqda: <b>{{ visitsFilter === "once" ? "bir marta kirganlar" : "qayta-qayta kirayotganlar" }}</b>
            </span>
            <BaseButton size="xs" severity="secondary" @click="setVisits(visitsFilter)">
               <template #icon><X class="size-3.5" /></template>
               Olib tashlash
            </BaseButton>
         </div>

         <div v-if="loading && !users.length" class="admin-loading">Foydalanuvchilar yuklanmoqda…</div>
         <div v-else class="admin-table-wrap transition-opacity" :class="{ 'opacity-60': loading }">
            <table class="admin-xtable">
               <thead>
                  <tr>
                     <th class="admin-col-main">Foydalanuvchi</th>
                     <th class="admin-col-wide">Telegram ID</th>
                     <th class="admin-col-wide">Shahar</th>
                     <th class="admin-col-wide">Kirgan</th>
                     <th class="admin-col-wide">Oxirgi faollik</th>
                     <th class="admin-col-shrink">E'lonlar</th>
                     <th class="admin-col-shrink"><span class="sr-only">Ochish</span></th>
                  </tr>
               </thead>
               <tbody>
                  <template v-for="user in users" :key="user.id">
                     <tr
                        class="admin-xrow"
                        :class="{ 'is-open': openId === user.id, 'is-muted': !!user.blocked_at }"
                        :aria-expanded="openId === user.id"
                        tabindex="0"
                        @click="toggle(user.id)"
                        @keydown.enter.prevent="toggle(user.id)"
                     >
                        <td class="admin-col-main">
                           <div class="admin-xmain">
                              <span class="admin-xthumb rounded-full! font-bold text-sm text-(--z-foreground)">
                                 {{ initials(user.name) }}
                              </span>
                              <div class="min-w-0 flex-1">
                                 <!-- Uzun ism bitta qatorda qisqartiriladi, belgilar doim ko'rinadi -->
                                 <div class="flex min-w-0 items-center gap-1.5 text-[13.5px] font-semibold leading-tight">
                                    <span class="min-w-0 truncate" :title="user.name">{{ user.name }}</span>
                                    <span v-if="user.role === 'admin'" class="admin-badge admin-badge-info shrink-0">Admin</span>
                                    <span v-if="user.blocked_at" class="admin-badge admin-badge-danger shrink-0">Bloklangan</span>
                                 </div>
                                 <div class="admin-xmeta">
                                    <span v-if="user.username" class="max-w-full truncate">@{{ user.username }}</span>
                                    <span class="admin-only-narrow inline-flex items-center gap-1">
                                       <span class="size-1.5 rounded-full" :class="isRecentlyActive(user.last_active_at) ? 'bg-emerald-500' : 'bg-slate-400'" />
                                       {{ user.last_active_at ? timeAgo(user.last_active_at) : "Kirmagan" }}
                                    </span>
                                    <span class="admin-only-narrow">{{ visitLabel(user.visit_days) }}</span>
                                 </div>
                              </div>
                           </div>
                        </td>
                        <td class="admin-col-wide tabular-nums">{{ user.telegram_user_id || "—" }}</td>
                        <td class="admin-col-wide">{{ user.active_district?.name || "—" }}</td>
                        <td class="admin-col-wide whitespace-nowrap">
                           <span v-if="user.visit_days >= 2" class="admin-badge admin-badge-success">{{ visitLabel(user.visit_days) }}</span>
                           <span v-else class="text-(--z-muted-text)">{{ visitLabel(user.visit_days) }}</span>
                        </td>
                        <td class="admin-col-wide whitespace-nowrap">
                           <span class="inline-flex items-center gap-2">
                              <span class="size-2 rounded-full" :class="isRecentlyActive(user.last_active_at) ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600'" />
                              {{ user.last_active_at ? timeAgo(user.last_active_at) : "Kirmagan" }}
                           </span>
                        </td>
                        <td class="admin-col-shrink text-right tabular-nums">
                           <b>{{ user.active_products_count ?? 0 }}</b>
                           <span class="text-(--z-muted-text)"> / {{ user.products_count }}</span>
                        </td>
                        <td class="admin-col-shrink"><ChevronDown class="admin-xchevron size-4" /></td>
                     </tr>

                     <tr v-if="openId === user.id" class="admin-xdetail">
                        <td colspan="7">
                           <div class="admin-xdetail-inner">
                              <dl class="admin-dl admin-xfull">
                                 <div>
                                    <dt>Telegram ID</dt>
                                    <dd class="tabular-nums">{{ user.telegram_user_id || "—" }}</dd>
                                 </div>
                                 <div>
                                    <dt>Shahar</dt>
                                    <dd>{{ user.active_district?.name || "Tanlanmagan" }}</dd>
                                 </div>
                                 <div>
                                    <dt>E'lonlar (faol / jami)</dt>
                                    <dd class="tabular-nums">{{ user.active_products_count ?? 0 }} / {{ user.products_count }}</dd>
                                 </div>
                                 <div>
                                    <dt>Ro'yxatdan o'tgan</dt>
                                    <dd>{{ formatDateTime(user.created_at) }}</dd>
                                 </div>
                                 <div>
                                    <dt>Oxirgi faollik</dt>
                                    <dd>{{ user.last_active_at ? formatDateTime(user.last_active_at) : "Kirmagan" }}</dd>
                                 </div>
                                 <div>
                                    <dt>Ilovaga kirgan</dt>
                                    <dd>
                                       {{ visitLabel(user.visit_days) }}
                                       <span v-if="user.last_visit_on" class="text-(--z-muted-text)">· oxirgisi {{ formatDate(user.last_visit_on) }}</span>
                                    </dd>
                                 </div>
                                 <div v-if="user.blocked_at">
                                    <dt>Bloklangan</dt>
                                    <dd>{{ formatDateTime(user.blocked_at) }}</dd>
                                 </div>
                              </dl>

                              <div class="admin-xactions admin-xfull">
                                 <BaseButton v-if="user.products_count" size="sm" severity="secondary" @click="openProducts(user)">
                                    <template #icon><FileText class="size-3.5" /></template>
                                    E'lonlari
                                 </BaseButton>
                                 <BaseButton v-if="user.id !== currentUserId" size="sm" severity="secondary" @click="messageTarget = user">
                                    <template #icon><MessageSquare class="size-3.5" /></template>
                                    Xabar yozish
                                 </BaseButton>
                                 <a v-if="telegramLink(user)" :href="telegramLink(user)!" target="_blank" rel="noopener">
                                    <BaseButton size="sm" severity="secondary">
                                       <template #icon><Send class="size-3.5" /></template>
                                       Telegramda yozish
                                    </BaseButton>
                                 </a>

                                 <template v-if="user.id !== currentUserId">
                                    <BaseButton size="sm" severity="secondary" :loading="busyId === user.id" @click="askRole(user)">
                                       <template #icon><ShieldCheck class="size-3.5" /></template>
                                       {{ user.role === "admin" ? "Adminlikdan olish" : "Admin qilish" }}
                                    </BaseButton>
                                    <BaseButton
                                       v-if="user.blocked_at"
                                       size="sm"
                                       severity="secondary"
                                       :loading="busyId === user.id"
                                       @click="unblock(user)"
                                    >
                                       <template #icon><Unlock class="size-3.5" /></template>
                                       Blokdan chiqarish
                                    </BaseButton>
                                    <BaseButton v-else-if="user.role !== 'admin'" size="sm" severity="danger" @click="askBlock(user)">
                                       <template #icon><Ban class="size-3.5" /></template>
                                       Bloklash
                                    </BaseButton>
                                 </template>
                                 <span v-else class="self-center text-xs text-(--z-muted-text)">Bu sizning hisobingiz</span>
                              </div>
                           </div>
                        </td>
                     </tr>
                  </template>

                  <tr v-if="users.length === 0">
                     <td colspan="7" class="admin-empty-cell">Foydalanuvchi topilmadi.</td>
                  </tr>
               </tbody>
            </table>
         </div>

         <footer v-if="pagination" class="admin-pagination">
            <span>{{ pagination.from || 0 }}–{{ pagination.to || 0 }} / {{ pagination.total }}</span>
            <div class="flex items-center gap-2">
               <BaseButton
                  size="xs"
                  severity="secondary"
                  :disabled="pagination.current_page <= 1 || loading"
                  @click="load(pagination.current_page - 1)"
               >
                  Oldingi
               </BaseButton>
               <span class="admin-page-number">{{ pagination.current_page }} / {{ pagination.last_page || 1 }}</span>
               <BaseButton
                  size="xs"
                  severity="secondary"
                  :disabled="pagination.current_page >= pagination.last_page || loading"
                  @click="load(pagination.current_page + 1)"
               >
                  Keyingi
               </BaseButton>
            </div>
         </footer>
      </section>
   </section>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { Ban, ChevronDown, FileText, MessageSquare, Search, Send, ShieldCheck, Unlock, X } from "lucide-vue-next";
import AdminMessageDialog from "@admin/components/AdminMessageDialog.vue";
import AdminRepo from "@admin/entities/AdminRepo";
import { useAuth } from "@shared/store/useAuth";
import BaseButton from "@shared/ui/BaseButton.vue";
import BaseModal from "@shared/ui/BaseModal.vue";
import FieldSelect from "@shared/ui/FieldSelect.vue";
import { timeAgo, formatDate, formatDateTime } from "@shared/modules/formatters";

type UserItem = {
   id: number;
   name: string;
   username: string | null;
   telegram_user_id: number | null;
   role: "user" | "admin";
   products_count: number;
   active_products_count?: number;
   active_district?: { id: number; name: string } | null;
   created_at?: string;
   last_active_at?: string | null;
   blocked_at?: string | null;
   /** Ilovaga kirgan kunlar soni (bir kunda necha marta ochgani ahamiyatsiz) */
   visit_days: number;
   last_visit_on?: string | null;
};

type VisitSummary = {
   total: number;
   once: number;
   returning: number;
   regular: number;
   returned_week: number;
};
type VisitsFilter = "" | "once" | "returning";

type PaginationMeta = {
   current_page: number;
   last_page: number;
   total: number;
   from: number | null;
   to: number | null;
};

type ConfirmState = {
   title: string;
   description: string;
   note: string;
   confirmText: string;
   danger: boolean;
   withArchive?: boolean;
   run: () => Promise<unknown>;
};

const router = useRouter();
const authStore = useAuth();
const currentUserId = authStore.user?.id ?? 0;

const roleChips = [
   { value: "", label: "Hammasi" },
   { value: "user", label: "Foydalanuvchilar" },
   { value: "admin", label: "Adminlar" },
   { value: "blocked", label: "Bloklanganlar" },
];

const users = ref<UserItem[]>([]);
const pagination = ref<PaginationMeta | null>(null);
const searchInput = ref("");
const roleFilter = ref("");
const visitsFilter = ref<VisitsFilter>("");
const sort = ref("new");
const summary = ref<VisitSummary | null>(null);
const loading = ref(true);
const busyId = ref<number | null>(null);
const openId = ref<number | null>(null);
const error = ref("");
const notice = ref("");
const confirmState = ref<ConfirmState | null>(null);
const archiveProducts = ref(false);
// "Xabar yozish" oynasi ochilgan foydalanuvchi
const messageTarget = ref<UserItem | null>(null);

function isRecentlyActive(dateString: string | null | undefined): boolean {
   if (!dateString) return false;
   return Date.now() - new Date(dateString).getTime() < 1000 * 60 * 60 * 24;
}

const num = (value: number) => value.toLocaleString("ru-RU");

function percent(value: number) {
   const total = summary.value?.total || 0;
   return total ? `${Math.round((value / total) * 100)}%` : "0%";
}

function visitLabel(days: number) {
   if (!days) return "Ma'lumot yo'q";
   return days === 1 ? "1 kun" : `${days} kun`;
}

function initials(name: string) {
   return (name || "?")
      .split(/\s+/)
      .slice(0, 2)
      .map((part) => part[0]?.toUpperCase())
      .join("");
}

function telegramLink(user: UserItem) {
   if (user.username) return `https://t.me/${user.username}`;
   return user.telegram_user_id ? `tg://user?id=${user.telegram_user_id}` : null;
}

async function load(page = 1) {
   loading.value = true;
   error.value = "";
   try {
      const params: Record<string, any> = { page };
      if (searchInput.value.trim()) params.search = searchInput.value.trim();
      if (roleFilter.value) params.role = roleFilter.value;
      if (visitsFilter.value) params.visits = visitsFilter.value;
      if (sort.value !== "new") params.sort = sort.value;

      const { data } = await AdminRepo.users(params);
      users.value = data.data;
      summary.value = data.visit_summary ?? null;
      pagination.value = {
         current_page: data.current_page,
         last_page: data.last_page,
         total: data.total,
         from: data.from,
         to: data.to,
      };
   } catch (exception) {
      console.error("Foydalanuvchilarni yuklab bo'lmadi.", exception);
      error.value = "Foydalanuvchilarni yuklab bo'lmadi. Qaytadan urinib ko'ring.";
   } finally {
      loading.value = false;
   }
}

function setRole(value: string) {
   if (roleFilter.value === value) return;
   roleFilter.value = value;
   openId.value = null;
   load(1);
}

/** Karta qayta bosilsa — filtr olib tashlanadi */
function setVisits(value: Exclude<VisitsFilter, "">) {
   visitsFilter.value = visitsFilter.value === value ? "" : value;
   openId.value = null;
   load(1);
}

function toggle(id: number) {
   openId.value = openId.value === id ? null : id;
}

function openProducts(user: UserItem) {
   router.push({ name: "admin-products", query: { user_id: user.id, user_name: user.name } });
}

async function act(user: UserItem, action: () => Promise<{ data?: { message?: string } }>, failure: string) {
   busyId.value = user.id;
   error.value = "";
   notice.value = "";
   try {
      const response = await action();
      notice.value = response?.data?.message || "Saqlandi.";
      await load(pagination.value?.current_page || 1);
   } catch (exception: any) {
      console.error(failure, exception);
      error.value = exception?.response?.data?.message || failure;
   } finally {
      busyId.value = null;
   }
}

function askRole(user: UserItem) {
   const makeAdmin = user.role !== "admin";
   confirmState.value = {
      title: makeAdmin ? "Admin qilish" : "Adminlikdan olish",
      description: user.name,
      note: makeAdmin
         ? "Bu foydalanuvchi admin panelga kira oladi: e'lonlar, foydalanuvchilar va sozlamalarni boshqaradi."
         : "Foydalanuvchi admin paneldan foydalana olmay qoladi.",
      confirmText: makeAdmin ? "Admin qilish" : "Huquqni olish",
      danger: false,
      run: () => act(user, () => AdminRepo.updateUserRole(user.id, makeAdmin ? "admin" : "user"), "Rolni o'zgartirib bo'lmadi."),
   };
}

function askBlock(user: UserItem) {
   archiveProducts.value = false;
   confirmState.value = {
      title: "Foydalanuvchini bloklash",
      description: user.name,
      note: "Bloklangan foydalanuvchi e'lonlarni ko'ra oladi, lekin yangi e'lon joylay olmaydi va eskilarini tahrirlay olmaydi.",
      confirmText: "Bloklash",
      danger: true,
      withArchive: user.products_count > 0,
      run: () => act(user, () => AdminRepo.blockUser(user.id, archiveProducts.value), "Bloklab bo'lmadi."),
   };
}

function unblock(user: UserItem) {
   return act(user, () => AdminRepo.unblockUser(user.id), "Blokdan chiqarib bo'lmadi.");
}

function runConfirm() {
   const state = confirmState.value;
   confirmState.value = null;
   state?.run();
}

onMounted(() => load(1));
</script>
