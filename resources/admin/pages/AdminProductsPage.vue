<template>
   <section class="admin-page">
      <!-- Xavfli amallar uchun bitta tasdiqlash oynasi (arxivlash, butunlay o'chirish) -->
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
            <TriangleAlert class="size-5 text-(--z-danger)" />
         </template>
         <p class="text-sm text-(--z-muted-text)">{{ confirmState?.note }}</p>
      </BaseModal>

      <AdminMessageDialog
         :open="messageTarget !== null"
         :recipient="messageTarget?.user ?? null"
         :product="messageTarget"
         @close="messageTarget = null"
         @sent="(text) => (notice = text)"
      />

      <AdminProductEditDrawer :product-id="editId" @close="editId = null" @saved="onEdited" />

      <header class="admin-page-heading">
         <div>
            <h1>E'lonlar</h1>
            <p class="admin-muted">Qatorni bosing — to'liq ma'lumot, shikoyatlar va amallar ochiladi.</p>
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
                  placeholder="Sarlavha, #ID, telefon yoki muallif"
                  @keyup.enter="search"
               />
            </label>
            <div class="sm:w-40">
               <FieldSelect
                  v-model="isBot"
                  size="sm"
                  :options="[
                     { label: 'Barcha manbalar', value: 'all' },
                     { label: 'Odamlar', value: 'user' },
                     { label: 'Bot', value: 'bot' },
                  ]"
                  @change="load(1)"
               />
            </div>
            <div class="sm:w-40">
               <FieldSelect
                  v-model="sort"
                  size="sm"
                  :options="[
                     { label: 'Yangilari', value: 'new' },
                     { label: 'Ko\'p ko\'rilgan', value: 'views' },
                     { label: 'Ko\'p shikoyat', value: 'reports' },
                  ]"
                  @change="load(1)"
               />
            </div>
            <!-- Kategoriya: ichki bo'limlari bilan birga -->
            <div class="sm:w-52">
               <FieldSelect v-model="categoryId" size="sm" :options="categoryOptions" @change="load(1)" />
            </div>
            <div class="sm:w-44">
               <FieldSelect v-model="districtId" size="sm" :options="districtOptions" @change="load(1)" />
            </div>
            <BaseButton severity="secondary" size="sm" @click="search">
               <template #icon><Search class="size-3.5" /></template>
               Qidirish
            </BaseButton>
         </div>

         <!-- Holatlar: bitta bosishda; sonlar joriy qidiruv bo'yicha -->
         <nav class="admin-filter-chips" aria-label="Holat bo'yicha">
            <button
               v-for="chip in statusChips"
               :key="chip.value"
               type="button"
               class="admin-filter-chip"
               :class="{ 'is-active': status === chip.value, 'is-danger': chip.value === 'reported' && counts.reported }"
               @click="setStatus(chip.value)"
            >
               <Flag v-if="chip.value === 'reported'" class="size-3.5" />
               {{ chip.label }}
               <span>{{ counts[chip.value] ?? "·" }}</span>
            </button>
         </nav>

         <div v-if="userFilter" class="admin-filter-note">
            <span class="min-w-0 truncate">Muallif: <b>{{ userFilter.name }}</b></span>
            <BaseButton size="xs" severity="secondary" @click="clearUserFilter">
               <template #icon><X class="size-3.5" /></template>
               Olib tashlash
            </BaseButton>
         </div>

         <!-- Ommaviy amallar: belgilanganda jadval ustida paydo bo'ladi -->
         <div v-if="selected.length" class="admin-bulk-bar">
            <span class="font-semibold tabular-nums">{{ selected.length }} ta tanlandi</span>
            <div class="flex flex-wrap gap-1.5">
               <template v-if="status === 'deleted'">
                  <BaseButton size="xs" severity="secondary" :loading="bulkBusy" @click="runBulk('restore')">Tiklash</BaseButton>
               </template>
               <template v-else>
                  <BaseButton size="xs" severity="secondary" :loading="bulkBusy" @click="runBulk('activate')">Faollashtirish</BaseButton>
                  <BaseButton size="xs" severity="secondary" :loading="bulkBusy" @click="runBulk('deactivate')">To'xtatish</BaseButton>
                  <BaseButton size="xs" severity="secondary" :loading="bulkBusy" @click="runBulk('resolve')">Shikoyatlarni yopish</BaseButton>
                  <BaseButton size="xs" severity="danger" :disabled="bulkBusy" @click="askBulkArchive">Arxivlash</BaseButton>
               </template>
               <BaseButton size="xs" severity="secondary" icon-only aria-label="Tanlovni bekor qilish" @click="selected = []">
                  <template #icon><X class="size-3.5" /></template>
               </BaseButton>
            </div>
         </div>

         <div v-if="loading && !products.length" class="admin-loading">E'lonlar yuklanmoqda…</div>
         <div v-else class="admin-table-wrap transition-opacity" :class="{ 'opacity-60': loading }">
            <table class="admin-xtable">
               <thead>
                  <tr>
                     <th class="admin-col-check">
                        <input
                           type="checkbox"
                           class="admin-check"
                           aria-label="Sahifadagi hammasini tanlash"
                           :checked="products.length > 0 && selected.length === products.length"
                           :indeterminate="selected.length > 0 && selected.length < products.length"
                           @change="toggleAll"
                        />
                     </th>
                     <th class="admin-col-main">E'lon</th>
                     <th class="admin-col-wide">Muallif</th>
                     <th class="admin-col-wide">Narx</th>
                     <th class="admin-col-wide">Ko'rishlar</th>
                     <th class="admin-col-wide">Joylangan</th>
                     <th class="admin-col-shrink">Holat</th>
                     <th class="admin-col-shrink"><span class="sr-only">Ochish</span></th>
                  </tr>
               </thead>
               <tbody>
                  <template v-for="product in products" :key="product.id">
                     <tr
                        class="admin-xrow"
                        :class="{ 'is-open': openId === product.id, 'is-muted': !!product.deleted_at }"
                        :aria-expanded="openId === product.id"
                        tabindex="0"
                        @click="toggle(product.id)"
                        @keydown.enter.prevent="toggle(product.id)"
                     >
                        <!-- Belgilash qatorni ochmaydi -->
                        <td class="admin-col-check" @click.stop>
                           <input v-model="selected" type="checkbox" class="admin-check" :value="product.id" :aria-label="`${product.title} — tanlash`" />
                        </td>
                        <td class="admin-col-main">
                           <div class="admin-xmain">
                              <img
                                 v-if="product.images?.[0]"
                                 class="admin-xthumb"
                                 :src="imageUrl(product.images[0].thumb_src || product.images[0].src)"
                                 alt=""
                                 loading="lazy"
                              />
                              <span v-else class="admin-xthumb"><ImageOff class="size-4" /></span>
                              <div class="min-w-0">
                                 <div class="admin-xtitle">{{ product.title }}</div>
                                 <!-- Telefonda: kompyuterdagi ustunlar o'rniga bitta ixcham qator -->
                                 <div class="admin-xmeta admin-only-narrow">
                                    <b v-if="product.price">{{ formatProductPrice(product.price, product.price_type) }}</b>
                                    <span class="inline-flex items-center gap-1"><Eye class="size-3" />{{ product.views_count ?? 0 }}</span>
                                    <span>{{ timeAgo(product.created_at) }}</span>
                                 </div>
                                 <div class="admin-xmeta">
                                    <span>#{{ product.id }} · {{ product.category?.name || "Kategoriyasiz" }}</span>
                                    <span v-if="product.is_bot" class="inline-flex items-center gap-0.5 text-emerald-600">
                                       <Bot class="size-3" /> Bot
                                    </span>
                                 </div>
                              </div>
                           </div>
                        </td>
                        <td class="admin-col-wide">
                           <!-- Uzun ism ustunni kengaytirib yubormasin -->
                           <div class="max-w-40 truncate font-medium" :title="product.user?.name">{{ product.user?.name || "—" }}</div>
                           <div class="admin-xmeta max-w-40 truncate block!">{{ product.user?.username ? `@${product.user.username}` : "" }}</div>
                        </td>
                        <td class="admin-col-wide whitespace-nowrap">{{ formatProductPrice(product.price, product.price_type) }}</td>
                        <td class="admin-col-wide tabular-nums">{{ (product.views_count ?? 0).toLocaleString("ru-RU") }}</td>
                        <td class="admin-col-wide whitespace-nowrap">{{ timeAgo(product.created_at) }}</td>
                        <td class="admin-col-shrink">
                           <div class="flex flex-col items-end gap-1">
                              <span class="admin-badge" :class="badgeClass(product)">{{ statusLabel(product) }}</span>
                              <span v-if="product.open_reports_count" class="admin-badge admin-badge-danger">
                                 <Flag class="size-3" />{{ product.open_reports_count }}
                              </span>
                           </div>
                        </td>
                        <td class="admin-col-shrink">
                           <ChevronDown class="admin-xchevron size-4" />
                        </td>
                     </tr>

                     <tr v-if="openId === product.id" class="admin-xdetail">
                        <td colspan="8">
                           <div class="admin-xdetail-inner">
                              <!-- Shikoyatlar birinchi: admin aynan shu sababdan ochgan bo'lishi mumkin -->
                              <section v-if="product.open_reports?.length" class="admin-xfull">
                                 <p class="admin-xsection-title">Shikoyatlar ({{ product.open_reports.length }})</p>
                                 <div class="admin-report-list">
                                    <div v-for="report in product.open_reports" :key="report.id" class="admin-report">
                                       <b>{{ report.reason_label }}</b>
                                       <span v-if="report.comment"> — {{ report.comment }}</span>
                                       <small>{{ report.user?.name || "Foydalanuvchi" }} · {{ timeAgo(report.created_at) }}</small>
                                    </div>
                                 </div>
                              </section>

                              <div class="grid gap-3.5 content-start">
                                 <section v-if="product.images?.length">
                                    <p class="admin-xsection-title">Rasmlar ({{ product.images.length }})</p>
                                    <div class="admin-thumbs">
                                       <a v-for="image in product.images" :key="image.id" :href="imageUrl(image.src)" target="_blank" rel="noopener">
                                          <img :src="imageUrl(image.thumb_src || image.src)" alt="" loading="lazy" />
                                       </a>
                                    </div>
                                 </section>
                                 <section v-if="product.description">
                                    <p class="admin-xsection-title">Tavsif</p>
                                    <p class="admin-xdescription">{{ product.description }}</p>
                                 </section>
                              </div>

                              <dl class="admin-dl">
                                 <div>
                                    <dt>Muallif</dt>
                                    <dd>
                                       {{ product.user?.name || "—" }}
                                       <a v-if="product.user?.username" :href="`https://t.me/${product.user.username}`" target="_blank" rel="noopener">
                                          @{{ product.user.username }}
                                       </a>
                                       <span v-if="product.user?.blocked_at" class="admin-badge admin-badge-danger ml-1">Bloklangan</span>
                                    </dd>
                                 </div>
                                 <div>
                                    <dt>Telefon</dt>
                                    <dd>
                                       <a v-if="product.phone" :href="`tel:${product.phone}`">{{ product.phone }}</a>
                                       <template v-else>—</template>
                                    </dd>
                                 </div>
                                 <div>
                                    <dt>Narx</dt>
                                    <dd>{{ formatProductPrice(product.price, product.price_type) }}</dd>
                                 </div>
                                 <div>
                                    <dt>Hudud</dt>
                                    <dd>{{ product.district?.name || "Butun viloyat" }}</dd>
                                 </div>
                                 <div>
                                    <dt>Kategoriya</dt>
                                    <dd>{{ product.category?.name || "Kategoriyasiz" }}</dd>
                                 </div>
                                 <div>
                                    <dt>Ko'rishlar · Sevimli</dt>
                                    <dd class="tabular-nums">{{ product.views_count ?? 0 }} · {{ product.favorites_count ?? 0 }}</dd>
                                 </div>
                                 <div>
                                    <dt>Joylangan</dt>
                                    <dd>{{ formatDateTime(product.created_at) }}</dd>
                                 </div>
                                 <div>
                                    <dt>Muddati</dt>
                                    <dd>
                                       {{ product.is_permanent ? "Muddatsiz" : product.expires_at ? formatDateTime(product.expires_at) : "—" }}
                                       <!-- Har qanday e'lon (Telegram assistentniki ham): N kun, muddatsiz yoki kategoriya bo'yicha -->
                                       <select
                                          v-if="!product.deleted_at"
                                          class="admin-input mt-1 h-8! py-0! text-xs"
                                          :disabled="busyId === product.id"
                                          @change="changeExpiry(product, $event)"
                                       >
                                          <option value="">Muddatni o'zgartirish…</option>
                                          <option v-for="option in EXPIRY_OPTIONS" :key="option.value" :value="option.value">
                                             {{ option.label }}
                                          </option>
                                       </select>
                                    </dd>
                                 </div>
                                 <div v-if="product.is_bot">
                                    <dt>Manba</dt>
                                    <dd>{{ product.source_channel ? `@${product.source_channel.replace(/^@/, "")}` : "Bot" }}</dd>
                                 </div>
                                 <div v-for="value in product.parameter_values" :key="value.id">
                                    <dt>{{ value.parameter?.title }}</dt>
                                    <dd>{{ value.value }} {{ value.parameter?.unit || "" }}</dd>
                                 </div>
                              </dl>

                              <div class="admin-xactions admin-xfull">
                                 <a :href="`/product/${product.id}`" target="_blank" rel="noopener">
                                    <BaseButton size="sm" severity="secondary">
                                       <template #icon><ExternalLink class="size-3.5" /></template>
                                       Ilovada ochish
                                    </BaseButton>
                                 </a>

                                 <template v-if="product.deleted_at">
                                    <BaseButton size="sm" severity="secondary" :loading="busyId === product.id" @click="restore(product)">
                                       <template #icon><RotateCcw class="size-3.5" /></template>
                                       Tiklash
                                    </BaseButton>
                                    <BaseButton size="sm" severity="danger" @click="askForceDelete(product)">
                                       <template #icon><Trash2 class="size-3.5" /></template>
                                       Butunlay o'chirish
                                    </BaseButton>
                                 </template>

                                 <template v-else>
                                    <!-- Admin panelning o'zida: ilovadagi forma (rasmlar, parametrlar) yon oynada ochiladi -->
                                    <BaseButton size="sm" severity="secondary" @click="editId = product.id">
                                       <template #icon><Pencil class="size-3.5" /></template>
                                       Tahrirlash
                                    </BaseButton>
                                    <BaseButton size="sm" severity="secondary" :loading="busyId === product.id" @click="changeStatus(product)">
                                       <template #icon>
                                          <component :is="product.is_active ? PauseCircle : PlayCircle" class="size-3.5" />
                                       </template>
                                       {{ product.is_active ? "To'xtatish" : "Faollashtirish" }}
                                    </BaseButton>
                                    <!-- Muallifga ogohlantirish/tushuntirish (Telegram assistent e'lonida asl muallif yo'q) -->
                                    <BaseButton
                                       v-if="!product.is_bot && product.user"
                                       size="sm"
                                       severity="secondary"
                                       @click="messageTarget = product"
                                    >
                                       <template #icon><MessageSquare class="size-3.5" /></template>
                                       Muallifga yozish
                                    </BaseButton>
                                    <BaseButton
                                       v-if="product.open_reports_count"
                                       size="sm"
                                       severity="secondary"
                                       :loading="busyId === product.id"
                                       @click="resolveReports(product)"
                                    >
                                       <template #icon><CheckCheck class="size-3.5" /></template>
                                       Shikoyatlarni yopish
                                    </BaseButton>
                                    <BaseButton size="sm" severity="danger" @click="askArchive(product)">
                                       <template #icon><Archive class="size-3.5" /></template>
                                       Arxivlash
                                    </BaseButton>
                                 </template>

                                 <BaseButton
                                    v-if="product.user && userFilter?.id !== product.user.id"
                                    size="sm"
                                    severity="secondary"
                                    @click="filterByUser(product.user)"
                                 >
                                    <template #icon><UserRound class="size-3.5" /></template>
                                    Muallifning e'lonlari
                                 </BaseButton>
                              </div>
                           </div>
                        </td>
                     </tr>
                  </template>

                  <tr v-if="products.length === 0">
                     <td colspan="8" class="admin-empty-cell">Qidiruvga mos e'lon topilmadi.</td>
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
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
   Archive,
   Bot,
   CheckCheck,
   ChevronDown,
   ExternalLink,
   Eye,
   Flag,
   ImageOff,
   MessageSquare,
   PauseCircle,
   Pencil,
   PlayCircle,
   RotateCcw,
   Search,
   Trash2,
   TriangleAlert,
   UserRound,
   X,
} from "lucide-vue-next";
import AdminRepo from "@admin/entities/AdminRepo";
import BaseButton from "@shared/ui/BaseButton.vue";
import BaseModal from "@shared/ui/BaseModal.vue";
import FieldSelect from "@shared/ui/FieldSelect.vue";
import AdminMessageDialog from "@admin/components/AdminMessageDialog.vue";
import AdminProductEditDrawer from "@admin/components/AdminProductEditDrawer.vue";
import { useCategory } from "@shared/entities/Category/useCategory";
import { useCity } from "@shared/entities/District/useCity";
import { formatDateTime, formatProductPrice, timeAgo } from "@shared/modules/formatters";
import type { ICategory } from "@shared/types";

type Status = "all" | "active" | "expired" | "reported" | "deleted";
type AdminProduct = Record<string, any> & {
   id: number;
   title: string;
   is_active: boolean;
   deleted_at: string | null;
   open_reports_count?: number;
};
type Pagination = {
   current_page: number;
   last_page: number;
   from: number | null;
   to: number | null;
   total: number;
};
type ConfirmState = {
   title: string;
   description: string;
   note: string;
   confirmText: string;
   danger: boolean;
   run: () => Promise<unknown>;
};

const route = useRoute();
const router = useRouter();

const statusChips: { value: Status; label: string }[] = [
   { value: "all", label: "Hammasi" },
   { value: "active", label: "Faol" },
   { value: "reported", label: "Shikoyat" },
   { value: "expired", label: "Muddati tugagan" },
   { value: "deleted", label: "Arxiv" },
];

const products = ref<AdminProduct[]>([]);
const pagination = ref<Pagination | null>(null);
const counts = ref<Partial<Record<Status, number>>>({});
// Boshqa sahifadan aniq e'longa havola: /admin/products?search=#12
const searchInput = ref(typeof route.query.search === "string" ? route.query.search : "");
const searchValue = ref(searchInput.value);
const status = ref<Status>((route.query.status as Status) || "all");
const isBot = ref("all");
const sort = ref("new");
// "" — hammasi; hudud uchun "none" — butun viloyat (hudud tanlanmagan e'lonlar)
const categoryId = ref<string>(typeof route.query.category_id === "string" ? route.query.category_id : "");
const districtId = ref<string>(typeof route.query.district_id === "string" ? route.query.district_id : "");
// Admin panelning o'zida tahrirlanayotgan e'lon
const editId = ref<number | null>(null);

const categoryStore = useCategory();
const cityStore = useCity();

/** Kategoriyalar daraxti bitta ro'yxatda: ichki bo'limlar chekinish bilan */
const categoryOptions = computed(() => {
   const options: { label: string; value: string }[] = [{ label: "Barcha kategoriyalar", value: "" }];
   const walk = (nodes: ICategory[] | undefined, depth: number) => {
      for (const node of nodes ?? []) {
         options.push({ label: `${"   ".repeat(depth)}${depth ? "› " : ""}${node.name}`, value: String(node.id) });
         walk(node.children, depth + 1);
      }
   };
   walk(categoryStore.parentCategories as ICategory[] | undefined, 0);
   return options;
});

const districtOptions = computed(() => [
   { label: "Barcha hududlar", value: "" },
   { label: "Butun viloyat", value: "none" },
   ...(cityStore.cities ?? []).map((city) => ({ label: city.name, value: String(city.id) })),
]);
const userFilter = ref<{ id: number; name: string } | null>(
   route.query.user_id ? { id: Number(route.query.user_id), name: String(route.query.user_name || `#${route.query.user_id}`) } : null,
);
const loading = ref(true);
const error = ref("");
const notice = ref("");
const openId = ref<number | null>(null);
const busyId = ref<number | null>(null);
const confirmState = ref<ConfirmState | null>(null);
// "Muallifga yozish" oynasi ochilgan e'lon
const messageTarget = ref<AdminProduct | null>(null);

async function load(page = 1) {
   loading.value = true;
   error.value = "";
   try {
      const { data } = await AdminRepo.products({
         page,
         status: status.value,
         sort: sort.value,
         is_bot: isBot.value !== "all" ? isBot.value : undefined,
         search: searchValue.value || undefined,
         user_id: userFilter.value?.id,
         category_id: categoryId.value || undefined,
         district_id: districtId.value || undefined,
      });
      products.value = data.data;
      counts.value = data.counts ?? {};
      selected.value = [];
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

const reload = () => load(pagination.value?.current_page || 1);

function search() {
   searchValue.value = searchInput.value.trim();
   load(1);
}

function setStatus(value: Status) {
   if (status.value === value) return;
   status.value = value;
   openId.value = null;
   load(1);
}

function toggle(id: number) {
   openId.value = openId.value === id ? null : id;
}

function onEdited(message: string) {
   error.value = "";
   notice.value = message;
   reload();
}

function filterByUser(user: { id: number; name: string }) {
   userFilter.value = { id: user.id, name: user.name };
   router.replace({ query: { user_id: user.id, user_name: user.name } });
   openId.value = null;
   load(1);
}

function clearUserFilter() {
   userFilter.value = null;
   router.replace({ query: {} });
   load(1);
}

/** Amalni bajarish: xabar ko'rsatadi va ro'yxatni (sonlar bilan) yangilaydi */
async function act(product: AdminProduct, action: () => Promise<unknown>, success: string, failure: string) {
   busyId.value = product.id;
   error.value = "";
   notice.value = "";
   try {
      await action();
      notice.value = success;
      await reload();
   } catch (exception: any) {
      console.error(failure, exception);
      error.value = exception?.response?.data?.message || failure;
   } finally {
      busyId.value = null;
   }
}

function changeStatus(product: AdminProduct) {
   const next = product.is_active ? "inactive" : "active";
   return act(
      product,
      () => AdminRepo.updateProductStatus(product.id, next),
      next === "active" ? "E'lon faollashtirildi va lenta boshiga chiqdi." : "E'lon to'xtatildi.",
      "E'lon holatini o'zgartirib bo'lmadi.",
   );
}

const EXPIRY_OPTIONS = [
   ...[1, 3, 7, 15, 30, 60, 90, 180, 365].map((days) => ({ value: String(days), label: `Bugundan ${days} kun` })),
   { value: "permanent", label: "Muddatsiz (o'chmaydi)" },
   { value: "category", label: "Kategoriya muddati bo'yicha" },
];

function changeExpiry(product: AdminProduct, event: Event) {
   const select = event.target as HTMLSelectElement;
   const value = select.value;
   select.value = "";
   if (!value) return;
   const duration = value === "permanent" || value === "category" ? value : "days";
   return act(
      product,
      () => AdminRepo.updateProductExpiry(product.id, duration, duration === "days" ? Number(value) : undefined),
      value === "permanent" ? "E'lon endi muddatsiz." : "E'lon muddati yangilandi.",
      "Muddatni o'zgartirib bo'lmadi.",
   );
}

function resolveReports(product: AdminProduct) {
   return act(product, () => AdminRepo.resolveReports(product.id), "Shikoyatlar yopildi.", "Shikoyatlarni yopib bo'lmadi.");
}

function restore(product: AdminProduct) {
   return act(product, () => AdminRepo.restoreProduct(product.id), "E'lon arxivdan tiklandi.", "E'lonni tiklab bo'lmadi.");
}

function askArchive(product: AdminProduct) {
   confirmState.value = {
      title: "E'lonni arxivlash",
      description: `“${product.title}”`,
      note: "E'lon ilovada ko'rinmaydi, kanaldagi posti o'chadi, shikoyatlari yopiladi. Arxivdan qayta tiklash mumkin.",
      confirmText: "Arxivlash",
      danger: true,
      run: () => act(product, () => AdminRepo.deleteProduct(product.id), "E'lon arxivga ko'chirildi.", "E'lonni arxivlab bo'lmadi."),
   };
}

function askForceDelete(product: AdminProduct) {
   confirmState.value = {
      title: "Butunlay o'chirish",
      description: `“${product.title}”`,
      note: "E'lon va uning rasmlari serverdan o'chiriladi. Bu amalni ortga qaytarib bo'lmaydi.",
      confirmText: "Butunlay o'chirish",
      danger: true,
      run: () => act(product, () => AdminRepo.forceDeleteProduct(product.id), "E'lon butunlay o'chirildi.", "E'lonni o'chirib bo'lmadi."),
   };
}

/* ---------- Ommaviy amallar ---------- */

const selected = ref<number[]>([]);
const bulkBusy = ref(false);
type BulkAction = Parameters<typeof AdminRepo.bulkProducts>[1];

function toggleAll() {
   selected.value = selected.value.length === products.value.length ? [] : products.value.map((product) => product.id);
}

async function runBulk(action: BulkAction) {
   bulkBusy.value = true;
   error.value = "";
   notice.value = "";
   try {
      const { data } = await AdminRepo.bulkProducts(selected.value, action);
      notice.value = data.message;
      await reload();
   } catch (exception: any) {
      console.error("Ommaviy amal bajarilmadi.", exception);
      error.value = exception?.response?.data?.message || "Amalni bajarib bo'lmadi.";
   } finally {
      bulkBusy.value = false;
   }
}

function askBulkArchive() {
   confirmState.value = {
      title: "Tanlanganlarni arxivlash",
      description: `${selected.value.length} ta e'lon`,
      note: "E'lonlar ilovada ko'rinmaydi, kanaldagi postlari o'chadi, shikoyatlari yopiladi. Arxivdan qayta tiklash mumkin.",
      confirmText: "Arxivlash",
      danger: true,
      run: () => runBulk("archive"),
   };
}

function runConfirm() {
   const state = confirmState.value;
   confirmState.value = null;
   state?.run();
}

function imageUrl(src: string) {
   return src.startsWith("http") ? src : `/storage/${src.replace(/^\/+/, "")}`;
}

function statusLabel(product: AdminProduct) {
   return product.deleted_at ? "Arxiv" : product.is_active ? "Faol" : "Tugagan";
}

function badgeClass(product: AdminProduct) {
   return product.deleted_at ? "admin-badge-muted" : product.is_active ? "admin-badge-success" : "admin-badge-warning";
}

onMounted(() => {
   load();
   // Filtr ro'yxatlari (tahrirlash formasi ham shulardan foydalanadi)
   if (!categoryStore.parentCategories) {
      categoryStore.getParentCategories().catch((exception) => console.error("Kategoriyalar yuklanmadi.", exception));
   }
   if (!cityStore.cities?.length) {
      cityStore.getCities().catch((exception) => console.error("Hududlar yuklanmadi.", exception));
   }
});
</script>
