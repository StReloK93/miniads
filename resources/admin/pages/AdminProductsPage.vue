<template>
   <section class="admin-page">
      <!-- Archive Confirmation Modal -->
      <BaseModal
         :open="productToArchive !== null"
         title="E'lonni arxivga ko'chirish"
         :description="`“${productToArchive?.title}” e'lonini arxivga ko'chirmoqchimisiz?`"
         confirm-text="Arxivlash"
         cancel-text="Bekor qilish"
         danger
         @close="productToArchive = null"
         @confirm="confirmArchive"
      >
         <template #icon>
            <TriangleAlert class="size-5 text-(--z-danger)" />
         </template>
         <p class="text-sm text-(--z-muted-text)">
            Ushbu e'lon foydalanuvchilar qidiruvidan olib tashlanadi. Keyinchalik uni qayta tiklash imkoni mavjud.
         </p>
      </BaseModal>

      <header class="admin-page-heading">
         <div>
            <h1>E'lonlar</h1>
            <p class="admin-muted">E'lonlarni qidiring, holatini boshqaring yoki arxivdan tiklang.</p>
         </div>
      </header>

      <p v-if="error" class="admin-alert admin-alert-error">{{ error }}</p>

      <section class="admin-panel admin-table-panel">
         <!-- Integrated Toolbar directly connected to Table -->
         <div class="admin-toolbar">
            <label class="admin-search">
               <Search class="size-4" aria-hidden="true" />
               <input
                  v-model="searchInput"
                  type="search"
                  placeholder="Sarlavha, telefon yoki muallif"
                  @keyup.enter="search"
               />
            </label>
            <div class="w-44">
               <FieldSelect
                  v-model="status"
                  size="sm"
                  :options="[
                     { label: 'Barcha holatlar', value: 'all' },
                     { label: 'Faol', value: 'active' },
                     { label: 'Muddati tugagan', value: 'expired' },
                     { label: 'Arxivlangan', value: 'deleted' },
                  ]"
                  @change="load(1)"
               />
            </div>
            <div class="w-40">
               <FieldSelect
                  v-model="isBot"
                  size="sm"
                  :options="[
                     { label: 'Barcha manbalar', value: 'all' },
                     { label: 'Faqat odamlar', value: 'user' },
                     { label: 'Faqat bot', value: 'bot' },
                  ]"
                  @change="load(1)"
               />
            </div>
            <BaseButton severity="secondary" size="sm" @click="search">
               <template #icon><Search class="size-3.5" /></template>
               Qidirish
            </BaseButton>
         </div>

         <div v-if="loading" class="admin-loading">E'lonlar yuklanmoqda…</div>
         <div v-else class="admin-table-wrap">
            <table class="admin-table">
               <thead>
                  <tr>
                     <th>E'lon</th>
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
                           <span class="flex items-center gap-1.5 flex-wrap">
                              <strong>{{ product.title }}</strong>
                              <span
                                 v-if="product.is_bot"
                                 class="inline-flex items-center gap-0.5 text-[10px] font-semibold text-emerald-600 bg-emerald-500/10 px-1.5 py-0.5 rounded-full"
                                 title="Telegramdan avtomatik yuklangan"
                              >
                                 <Bot class="size-2.5" /> Bot
                              </span>
                           </span>
                           <small>#{{ product.id }} · {{ product.category?.name || "Kategoriyasiz" }}</small>
                        </span>
                     </td>
                     <td>
                        {{ product.user?.name || "—" }}
                        <small class="admin-cell-subtitle">
                           {{ product.user?.username ? `@${product.user.username}` : "" }}
                        </small>
                     </td>
                     <td>{{ product.district?.name || "Navoiy viloyati" }}</td>
                     <td>{{ formatPrice(product.price) }}</td>
                     <td>
                        <span class="admin-badge" :class="badgeClass(product)">
                           {{ statusLabel(product) }}
                        </span>
                     </td>
                     <td class="admin-actions">
                        <div class="flex items-center justify-end gap-1.5">
                           <BaseButton
                              v-if="product.deleted_at"
                              size="xs"
                              severity="secondary"
                              title="Tiklash"
                              @click="restore(product)"
                           >
                              <template #icon><RotateCcw class="size-3.5" /></template>
                              Tiklash
                           </BaseButton>
                           <template v-else>
                              <BaseButton
                                 size="xs"
                                 severity="secondary"
                                 :title="product.is_active ? 'To\'xtatish' : 'Faollashtirish'"
                                 @click="changeStatus(product)"
                              >
                                 {{ product.is_active ? "To'xtatish" : "Faollashtirish" }}
                              </BaseButton>
                              <BaseButton
                                 size="xs"
                                 severity="danger"
                                 icon-only
                                 title="Arxivga ko'chirish"
                                 :aria-label="`${product.title} e'lonini arxivlash`"
                                 @click="productToArchive = product"
                              >
                                 <template #icon><Trash2 class="size-3.5" /></template>
                              </BaseButton>
                           </template>
                        </div>
                     </td>
                  </tr>
                  <tr v-if="products.length === 0">
                     <td colspan="6" class="admin-empty-cell">Qidiruvga mos e'lon topilmadi.</td>
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
import { Bot, RotateCcw, Search, Trash2, TriangleAlert } from "lucide-vue-next";
import AdminRepo from "@admin/entities/AdminRepo";
import BaseButton from "@shared/ui/BaseButton.vue";
import BaseModal from "@shared/ui/BaseModal.vue";
import FieldSelect from "@shared/ui/FieldSelect.vue";
import { formatPrice as formatPriceUtil } from "@shared/modules/formatters";

type AdminProduct = Record<string, any> & {
   id: number;
   title: string;
   is_active: boolean;
   deleted_at: string | null;
};
type Pagination = {
   current_page: number;
   last_page: number;
   from: number | null;
   to: number | null;
   total: number;
};

const products = ref<AdminProduct[]>([]);
const pagination = ref<Pagination | null>(null);
const searchInput = ref("");
const searchValue = ref("");
const status = ref("all");
const isBot = ref("all");
const loading = ref(false);
const error = ref("");
const productToArchive = ref<AdminProduct | null>(null);

async function load(page = 1) {
   loading.value = true;
   error.value = "";
   try {
      const { data } = await AdminRepo.products({
         page,
         status: status.value,
         is_bot: isBot.value !== "all" ? isBot.value : undefined,
         search: searchValue.value || undefined,
      });
      products.value = data.data;
      pagination.value = {
         current_page: data.current_page,
         last_page: data.last_page,
         from: data.from,
         to: data.to,
         total: data.total,
      };
   } catch (exception) {
      console.error("Admin e'lonlari yuklanmadi.", exception);
      error.value = "E'lonlar ro'yxatini yuklab bo'lmadi.";
   } finally {
      loading.value = false;
   }
}

function search() {
   searchValue.value = searchInput.value.trim();
   load(1);
}

async function changeStatus(product: AdminProduct) {
   const newStatus = product.is_active ? "inactive" : "active";
   try {
      await AdminRepo.updateProductStatus(product.id, newStatus);
      await load(pagination.value?.current_page || 1);
   } catch (exception) {
      console.error("E'lon holatini yangilab bo'lmadi.", exception);
      error.value = "E'lon holatini o'zgartirib bo'lmadi.";
   }
}

async function confirmArchive() {
   if (!productToArchive.value) return;
   const target = productToArchive.value;
   productToArchive.value = null;

   try {
      await AdminRepo.deleteProduct(target.id);
      await load(pagination.value?.current_page || 1);
   } catch (exception) {
      console.error("E'lonni arxivlab bo'lmadi.", exception);
      error.value = "E'lonni arxivga ko'chirib bo'lmadi.";
   }
}

async function restore(product: AdminProduct) {
   try {
      await AdminRepo.restoreProduct(product.id);
      await load(pagination.value?.current_page || 1);
   } catch (exception) {
      console.error("E'lonni tiklab bo'lmadi.", exception);
      error.value = "E'lonni tiklab bo'lmadi.";
   }
}

function imageUrl(src: string) {
   return src.startsWith("http") ? src : `/storage/${src.replace(/^\/+/, "")}`;
}

function formatPrice(value: number | null) {
   return formatPriceUtil(value, { fallback: "Kelishiladi", unit: "so'm" });
}

function statusLabel(product: AdminProduct) {
   return product.deleted_at ? "Arxivlangan" : product.is_active ? "Faol" : "Muddati tugagan";
}

function badgeClass(product: AdminProduct) {
   return product.deleted_at
      ? "admin-badge-muted"
      : product.is_active
        ? "admin-badge-success"
        : "admin-badge-warning";
}

onMounted(() => load());
</script>
