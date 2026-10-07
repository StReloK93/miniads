<template>
   <section class="admin-page">
      <header class="admin-page-heading">
         <div>
            <p class="admin-eyebrow">MODERATSIYA</p>
            <h1>E’lonlar</h1>
            <p class="admin-muted">E’lonlarni qidiring, holatini boshqaring yoki arxivdan tiklang.</p>
         </div>
      </header>

      <div class="admin-toolbar admin-panel">
         <label class="admin-search">
            <Search class="size-4" aria-hidden="true" />
            <input v-model="searchInput" type="search" placeholder="Sarlavha, telefon yoki muallif" @keyup.enter="search" />
         </label>
         <select v-model="status" class="admin-select" @change="load(1)">
            <option value="all">Barcha holatlar</option>
            <option value="active">Faol</option>
            <option value="expired">Muddati tugagan</option>
            <option value="deleted">Arxivlangan</option>
         </select>
         <button class="admin-button admin-button-secondary" type="button" @click="search">Qidirish</button>
      </div>

      <p v-if="error" class="admin-alert admin-alert-error">{{ error }}</p>
      <section class="admin-panel admin-table-panel">
         <div v-if="loading" class="admin-loading">E’lonlar yuklanmoqda…</div>
         <div v-else class="admin-table-wrap">
            <table class="admin-table">
               <thead>
                  <tr>
                     <th>E’lon</th>
                     <th>Muallif</th>
                     <th>Hudud</th>
                     <th>Narx</th>
                     <th>Holati</th>
                     <th class="admin-actions-heading">Amallar</th>
                  </tr>
               </thead>
               <tbody>
                  <tr v-for="product in products" :key="product.id">
                     <td class="admin-product-cell">
                        <img v-if="product.images?.[0]?.src" :src="imageUrl(product.images[0].src)" alt="" />
                        <span class="admin-product-copy">
                           <strong>{{ product.title }}</strong>
                           <small>#{{ product.id }} · {{ product.category?.name || "Kategoriyasiz" }}</small>
                        </span>
                     </td>
                     <td>{{ product.user?.name || "—" }}<small class="admin-cell-subtitle">{{ product.user?.username ? `@${product.user.username}` : "" }}</small></td>
                     <td>{{ product.district?.name || "Navoiy viloyati" }}</td>
                     <td>{{ formatPrice(product.price) }}</td>
                     <td><span class="admin-badge" :class="badgeClass(product)">{{ statusLabel(product) }}</span></td>
                     <td class="admin-actions">
                        <button
                           v-if="product.deleted_at"
                           class="admin-button admin-button-small admin-button-secondary"
                           type="button"
                           @click="restore(product)"
                        >Tiklash</button>
                        <template v-else>
                           <button
                              class="admin-button admin-button-small admin-button-secondary"
                              type="button"
                              @click="changeStatus(product)"
                           >{{ product.is_active ? "To‘xtatish" : "Faollashtirish" }}</button>
                           <button
                              class="admin-icon-button admin-danger"
                              type="button"
                              :aria-label="`${product.title} e’lonini arxivlash`"
                              title="Arxivga ko‘chirish"
                              @click="archive(product)"
                           ><Trash2 class="size-4" aria-hidden="true" /></button>
                        </template>
                     </td>
                  </tr>
                  <tr v-if="products.length === 0">
                     <td colspan="6" class="admin-empty-cell">Qidiruvga mos e’lon topilmadi.</td>
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
import { Search, Trash2 } from "lucide-vue-next";
import AdminRepo from "@admin/entities/AdminRepo";

type AdminProduct = Record<string, any> & { id: number; title: string; is_active: boolean; deleted_at: string | null };
type Pagination = { current_page: number; last_page: number; from: number | null; to: number | null; total: number };

const products = ref<AdminProduct[]>([]);
const pagination = ref<Pagination | null>(null);
const searchInput = ref("");
const searchValue = ref("");
const status = ref("all");
const loading = ref(false);
const error = ref("");

async function load(page = 1) {
   loading.value = true;
   error.value = "";
   try {
      const { data } = await AdminRepo.products({
         search: searchValue.value,
         status: status.value,
         page,
         per_page: 20,
      });
      products.value = data.data;
      pagination.value = data;
   } catch (exception) {
      console.error("Admin e’lonlari yuklanmadi.", exception);
      error.value = "E’lonlarni yuklab bo‘lmadi. Qayta urinib ko‘ring.";
   } finally {
      loading.value = false;
   }
}

function search() {
   searchValue.value = searchInput.value.trim();
   load(1);
}

async function changeStatus(product: AdminProduct) {
   const status = product.is_active ? "inactive" : "active";
   try {
      await AdminRepo.updateProductStatus(product.id, status);
      await load(pagination.value?.current_page || 1);
   } catch (exception) {
      console.error("E’lon holatini yangilab bo‘lmadi.", exception);
      error.value = "E’lon holatini o‘zgartirib bo‘lmadi.";
   }
}

async function archive(product: AdminProduct) {
   if (!window.confirm(`“${product.title}” e’lonini arxivga ko‘chirasizmi?`)) return;
   try {
      await AdminRepo.deleteProduct(product.id);
      await load(pagination.value?.current_page || 1);
   } catch (exception) {
      console.error("E’lonni arxivlab bo‘lmadi.", exception);
      error.value = "E’lonni arxivga ko‘chirib bo‘lmadi.";
   }
}

async function restore(product: AdminProduct) {
   try {
      await AdminRepo.restoreProduct(product.id);
      await load(pagination.value?.current_page || 1);
   } catch (exception) {
      console.error("E’lonni tiklab bo‘lmadi.", exception);
      error.value = "E’lonni tiklab bo‘lmadi.";
   }
}

function imageUrl(src: string) {
   return src.startsWith("http") ? src : `/storage/${src.replace(/^\/+/, "")}`;
}

function formatPrice(value: number | null) {
   return value === null ? "Kelishiladi" : `${new Intl.NumberFormat("uz-UZ").format(value)} so‘m`;
}

function statusLabel(product: AdminProduct) {
   return product.deleted_at ? "Arxivlangan" : product.is_active ? "Faol" : "Muddati tugagan";
}

function badgeClass(product: AdminProduct) {
   return product.deleted_at ? "admin-badge-muted" : product.is_active ? "admin-badge-success" : "admin-badge-warning";
}

onMounted(() => load());
</script>
