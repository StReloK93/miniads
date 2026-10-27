<template>
   <section class="admin-page">
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

         <div v-if="loading && !users.length" class="admin-loading">Foydalanuvchilar yuklanmoqda…</div>
         <div v-else class="admin-table-wrap transition-opacity" :class="{ 'opacity-60': loading }">
            <table class="admin-xtable">
               <thead>
                  <tr>
                     <th>Foydalanuvchi</th>
                     <th class="admin-col-wide">Telegram ID</th>
                     <th class="admin-col-wide">Shahar</th>
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
                        <td>
                           <div class="admin-xmain">
                              <span class="admin-xthumb rounded-full! font-bold text-sm text-(--z-foreground)">
                                 {{ initials(user.name) }}
                              </span>
                              <div class="min-w-0">
                                 <div class="admin-xtitle flex items-center gap-1.5">
                                    <span class="truncate">{{ user.name }}</span>
                                    <span v-if="user.role === 'admin'" class="admin-badge admin-badge-info">Admin</span>
                                    <span v-if="user.blocked_at" class="admin-badge admin-badge-danger">Bloklangan</span>
                                 </div>
                                 <div class="admin-xmeta">
                                    <span v-if="user.username">@{{ user.username }}</span>
                                    <span class="admin-only-narrow inline-flex items-center gap-1">
                                       <span class="size-1.5 rounded-full" :class="isRecentlyActive(user.last_active_at) ? 'bg-emerald-500' : 'bg-slate-400'" />
                                       {{ user.last_active_at ? timeAgo(user.last_active_at) : "Kirmagan" }}
                                    </span>
                                 </div>
                              </div>
                           </div>
                        </td>
                        <td class="admin-col-wide tabular-nums">{{ user.telegram_user_id || "—" }}</td>
                        <td class="admin-col-wide">{{ user.active_district?.name || "—" }}</td>
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
                        <td colspan="6">
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
                     <td colspan="6" class="admin-empty-cell">Foydalanuvchi topilmadi.</td>
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
import { Ban, ChevronDown, FileText, Search, Send, ShieldCheck, Unlock } from "lucide-vue-next";
import AdminRepo from "@admin/entities/AdminRepo";
import { useAuth } from "@shared/store/useAuth";
import BaseButton from "@shared/ui/BaseButton.vue";
import BaseModal from "@shared/ui/BaseModal.vue";
import { timeAgo, formatDateTime } from "@shared/modules/formatters";

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
};

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
const loading = ref(true);
const busyId = ref<number | null>(null);
const openId = ref<number | null>(null);
const error = ref("");
const notice = ref("");
const confirmState = ref<ConfirmState | null>(null);
const archiveProducts = ref(false);

function isRecentlyActive(dateString: string | null | undefined): boolean {
   if (!dateString) return false;
   return Date.now() - new Date(dateString).getTime() < 1000 * 60 * 60 * 24;
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

      const { data } = await AdminRepo.users(params);
      users.value = data.data;
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
