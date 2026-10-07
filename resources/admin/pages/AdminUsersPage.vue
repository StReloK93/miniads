<template>
   <section class="admin-page">
      <header class="admin-page-heading">
         <div>
            <p class="admin-eyebrow">AKKAUNTLAR</p>
            <h1>Foydalanuvchilar</h1>
            <p class="admin-muted">Profil, e’lonlar soni va administrator huquqlarini boshqaring.</p>
         </div>
      </header>

      <div class="admin-toolbar admin-panel">
         <label class="admin-search">
            <Search class="size-4" aria-hidden="true" />
            <input v-model="searchInput" type="search" placeholder="Ism, username yoki Telegram ID" @keyup.enter="search" />
         </label>
         <select v-model="roleFilter" class="admin-select" @change="load(1)">
            <option value="">Barcha rollar</option>
            <option value="user">Foydalanuvchilar</option>
            <option value="admin">Administratorlar</option>
         </select>
         <button class="admin-button admin-button-secondary" type="button" @click="search">Qidirish</button>
      </div>

      <p v-if="error" class="admin-alert admin-alert-error">{{ error }}</p>
      <p v-if="notice" class="admin-alert admin-alert-success">{{ notice }}</p>
      <section class="admin-panel admin-table-panel">
         <div v-if="loading" class="admin-loading">Foydalanuvchilar yuklanmoqda…</div>
         <div v-else class="admin-table-wrap">
            <table class="admin-table">
               <thead>
                  <tr>
                     <th>Foydalanuvchi</th>
                     <th>Telegram ID</th>
                     <th>Shahar</th>
                     <th>E’lonlar</th>
                     <th>Roli</th>
                     <th>Huquqni o‘zgartirish</th>
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
                     <td><span class="admin-badge" :class="user.role === 'admin' ? 'admin-badge-info' : 'admin-badge-muted'">{{ user.role === "admin" ? "Admin" : "Foydalanuvchi" }}</span></td>
                     <td>
                        <div class="admin-role-control">
                           <select v-model="roleDrafts[user.id]" class="admin-select admin-select-small" :disabled="user.id === currentUserId">
                              <option value="user">Foydalanuvchi</option>
                              <option value="admin">Admin</option>
                           </select>
                           <button
                              class="admin-button admin-button-small admin-button-secondary"
                              type="button"
                              :disabled="user.id === currentUserId || roleDrafts[user.id] === user.role || savingId === user.id"
                              @click="saveRole(user)"
                           >{{ savingId === user.id ? "Saqlanmoqda…" : "Saqlash" }}</button>
                        </div>
                     </td>
                  </tr>
                  <tr v-if="users.length === 0">
                     <td colspan="6" class="admin-empty-cell">Foydalanuvchi topilmadi.</td>
                  </tr>
               </tbody>
            </table>
         </div>
         <footer v-if="pagination" class="admin-pagination">
            <span>{{ pagination.from || 0 }}–{{ pagination.to || 0 }} / {{ pagination.total }}</span>
            <div>
               <button class="admin-button admin-button-secondary admin-button-small" :disabled="pagination.current_page <= 1 || loading" @click="load(pagination.current_page - 1)">Oldingi</button>
               <span class="admin-page-number">{{ pagination.current_page }} / {{ pagination.last_page || 1 }}</span>
               <button class="admin-button admin-button-secondary admin-button-small" :disabled="pagination.current_page >= pagination.last_page || loading" @click="load(pagination.current_page + 1)">Keyingi</button>
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

type AdminUser = { id: number; name: string; username: string | null; telegram_user_id: number | null; role: "admin" | "user"; products_count: number; active_district?: { name: string } | null };
type Pagination = { current_page: number; last_page: number; from: number | null; to: number | null; total: number };

const auth = useAuth();
const currentUserId = Number(auth.user?.id);
const users = ref<AdminUser[]>([]);
const pagination = ref<Pagination | null>(null);
const roleDrafts = ref<Record<number, "admin" | "user">>({});
const searchInput = ref("");
const searchValue = ref("");
const roleFilter = ref("");
const loading = ref(false);
const savingId = ref<number | null>(null);
const error = ref("");
const notice = ref("");

async function load(page = 1) {
   loading.value = true;
   error.value = "";
   notice.value = "";
   try {
      const { data } = await AdminRepo.users({
         search: searchValue.value,
         role: roleFilter.value,
         page,
         per_page: 20,
      });
      users.value = data.data;
      pagination.value = data;
      roleDrafts.value = Object.fromEntries(data.data.map((user: AdminUser) => [user.id, user.role]));
   } catch (exception) {
      console.error("Admin foydalanuvchilari yuklanmadi.", exception);
      error.value = "Foydalanuvchilarni yuklab bo‘lmadi. Qayta urinib ko‘ring.";
   } finally {
      loading.value = false;
   }
}

function search() {
   searchValue.value = searchInput.value.trim();
   load(1);
}

async function saveRole(user: AdminUser) {
   savingId.value = user.id;
   error.value = "";
   notice.value = "";
   try {
      await AdminRepo.updateUserRole(user.id, roleDrafts.value[user.id]);
      notice.value = `${user.name} foydalanuvchisining roli yangilandi.`;
      await load(pagination.value?.current_page || 1);
   } catch (exception: any) {
      console.error("Foydalanuvchi rolini yangilab bo‘lmadi.", exception);
      error.value = exception.response?.data?.message || "Foydalanuvchi rolini yangilab bo‘lmadi.";
   } finally {
      savingId.value = null;
   }
}

onMounted(() => load());
</script>
