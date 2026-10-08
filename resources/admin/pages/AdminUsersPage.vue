<template>
   <section class="admin-page">
      <header class="admin-page-heading">
         <div>
            <h1>Foydalanuvchilar</h1>
            <p class="admin-muted">Profil ma'lumotlari, e'lonlar soni, oxirgi faollik vaqti va administrator huquqlari.</p>
         </div>
      </header>

      <p v-if="error" class="admin-alert admin-alert-error mb-4">{{ error }}</p>
      <p v-if="notice" class="admin-alert admin-alert-success mb-4">{{ notice }}</p>

      <!-- Unified Table Card (Toolbar + Table + Pagination) -->
      <section class="admin-panel admin-table-panel">
         <!-- Integrated Toolbar -->
         <div class="admin-toolbar">
            <label class="admin-search">
               <Search class="size-4" aria-hidden="true" />
               <input
                  v-model="searchInput"
                  type="search"
                  placeholder="Ism, username yoki Telegram ID bo'yicha qidirish"
                  @keyup.enter="search"
               />
            </label>
            <div class="w-48">
               <FieldSelect
                  v-model="roleFilter"
                  size="sm"
                  :options="[
                     { label: 'Barcha rollar', value: '' },
                     { label: 'Foydalanuvchilar', value: 'user' },
                     { label: 'Administratorlar', value: 'admin' },
                  ]"
                  @change="load(1)"
               />
            </div>
            <BaseButton severity="secondary" size="sm" @click="search">
               <template #icon><Search class="size-3.5" /></template>
               Qidirish
            </BaseButton>
         </div>

         <div v-if="loading" class="admin-loading">Foydalanuvchilar yuklanmoqda…</div>
         <div v-else class="admin-table-wrap">
            <table class="admin-table">
               <thead>
                  <tr>
                     <th>Foydalanuvchi</th>
                     <th>Telegram ID</th>
                     <th>Shahar</th>
                     <th>E'lonlar</th>
                     <th>Roli</th>
                     <th>Oxirgi faollik</th>
                     <th>Huquqni o'zgartirish</th>
                  </tr>
               </thead>
               <tbody>
                  <tr v-for="user in users" :key="user.id">
                     <td>
                        <strong>{{ user.name }}</strong>
                        <small>{{ user.username ? `@${user.username}` : `ID: ${user.id}` }}</small>
                     </td>
                     <td>{{ user.telegram_user_id || "—" }}</td>
                     <td>{{ user.active_district?.name || "—" }}</td>
                     <td>{{ user.products_count }}</td>
                     <td>
                        <span
                           class="admin-badge"
                           :class="user.role === 'admin' ? 'admin-badge-info' : 'admin-badge-muted'"
                        >
                           {{ user.role === "admin" ? "Admin" : "Foydalanuvchi" }}
                        </span>
                     </td>
                     <td>
                        <div class="flex items-center gap-2">
                           <span
                              class="size-2 rounded-full shrink-0"
                              :class="isRecentlyActive(user.last_active_at) ? 'bg-emerald-500 animate-pulse' : 'bg-slate-300 dark:bg-slate-600'"
                              :title="isRecentlyActive(user.last_active_at) ? 'Faol' : 'Nofaol'"
                           ></span>
                           <div>
                              <strong>{{ timeAgo(user.last_active_at!) || 'Hozirgina' }}</strong>
                              <small>{{ formatDateTime(user.last_active_at) }}</small>
                           </div>
                        </div>
                     </td>
                     <td>
                        <div class="admin-role-control items-center">
                           <div class="w-36">
                              <FieldSelect
                                 v-model="roleDrafts[user.id]"
                                 size="xs"
                                 :disabled="user.id === currentUserId"
                                 :options="[
                                    { label: 'Foydalanuvchi', value: 'user' },
                                    { label: 'Admin', value: 'admin' },
                                 ]"
                              />
                           </div>
                           <BaseButton
                              size="xs"
                              severity="secondary"
                              :loading="savingId === user.id"
                              :disabled="user.id === currentUserId || roleDrafts[user.id] === user.role || savingId === user.id"
                              @click="saveRole(user)"
                           >
                              Saqlash
                           </BaseButton>
                        </div>
                     </td>
                  </tr>
                  <tr v-if="users.length === 0">
                     <td colspan="7" class="admin-empty-cell">Foydalanuvchi topilmadi.</td>
                  </tr>
               </tbody>
            </table>
         </div>

         <!-- Pagination -->
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
import { Search } from "lucide-vue-next";
import AdminRepo from "@admin/entities/AdminRepo";
import { useAuth } from "@shared/store/useAuth";
import BaseButton from "@shared/ui/BaseButton.vue";
import FieldSelect from "@shared/ui/FieldSelect.vue";
import { timeAgo, formatDateTime } from "@shared/modules/formatters";

type UserItem = {
   id: number;
   name: string;
   username: string | null;
   telegram_user_id: number | null;
   role: "user" | "admin";
   products_count: number;
   active_district?: { id: number; name: string } | null;
   created_at?: string;
   last_active_at?: string;
};

type PaginationMeta = {
   current_page: number;
   last_page: number;
   total: number;
   from: number | null;
   to: number | null;
};

const authStore = useAuth();
const currentUserId = authStore.user?.id ?? 0;

const users = ref<UserItem[]>([]);
const roleDrafts = ref<Record<number, "user" | "admin">>({});
const pagination = ref<PaginationMeta | null>(null);
const searchInput = ref("");
const roleFilter = ref<string>("");
const loading = ref(false);
const savingId = ref<number | null>(null);
const error = ref("");
const notice = ref("");

function isRecentlyActive(dateString: string | null | undefined): boolean {
   if (!dateString) return false;
   const diffMs = Date.now() - new Date(dateString).getTime();
   return diffMs < 1000 * 60 * 60 * 24; // 24 soat
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

      roleDrafts.value = Object.fromEntries(
         data.data.map((u: UserItem) => [u.id, u.role]),
      );
   } catch (exception) {
      console.error("Foydalanuvchilarni yuklab bo'lmadi.", exception);
      error.value = "Foydalanuvchilarni yuklab bo'lmadi. Qaytadan urinib ko'ring.";
   } finally {
      loading.value = false;
   }
}

function search() {
   load(1);
}

async function saveRole(user: UserItem) {
   const nextRole = roleDrafts.value[user.id];
   if (!nextRole || nextRole === user.role) return;

   savingId.value = user.id;
   error.value = "";
   notice.value = "";
   try {
      await AdminRepo.updateUserRole(user.id, nextRole);
      user.role = nextRole;
      notice.value = `“${user.name}” roli yangilandi.`;
   } catch (exception: any) {
      console.error("Rolni o'zgartirib bo'lmadi.", exception);
      roleDrafts.value[user.id] = user.role;
      error.value = exception?.response?.data?.message || "Rolni o'zgartirib bo'lmadi.";
   } finally {
      savingId.value = null;
   }
}

onMounted(() => {
   load(1);
});
</script>
