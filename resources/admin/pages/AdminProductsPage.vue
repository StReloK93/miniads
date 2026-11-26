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
            <!-- Holatdan mustaqil: raqami boshqa e'londa ham uchraydiganlar (bir xil e'lon ikki marta kiritilgan bo'lishi mumkin) -->
            <button
               type="button"
               class="admin-filter-chip"
               :class="{ 'is-active': duplicates }"
               title="Telefon raqami boshqa e'londa ham uchraydigan e'lonlar — bir raqamlilar yonma-yon"
               @click="toggleDuplicates"
            >
               <Copy class="size-3.5" />
               Bir xil raqam
               <span>{{ counts.duplicates ?? "·" }}</span>
            </button>
         </nav>

         <div v-if="dateFilter" class="admin-filter-note">
            <button type="button" class="adm-filter-tag" @click="clearDateFilter">
               {{ relativeLabel(dateFilter) }} joylanganlar
               <X class="size-3.5" aria-label="Olib tashlash" />
            </button>
         </div>

         <div v-if="userFilter" class="admin-filter-note">
            <span class="min-w-0 truncate">Muallif: <b>{{ userFilter.name }}</b></span>
            <BaseButton size="xs" severity="secondary" @click="clearUserFilter">
               <template #icon><X class="size-3.5" /></template>
               Olib tashlash
            </BaseButton>
         </div>

         <!-- Tanlash rejimi (Telegramdagidek): qatorni bosib turing — tanlanadi, keyin bosib qo'shib boring -->
         <div v-if="selected.length" class="admin-bulk-bar adm-select-bar">
            <button type="button" class="adm-icon-btn is-small" aria-label="Tanlovni bekor qilish" @click="selected = []">
               <X class="size-4.5" />
            </button>
            <span class="font-semibold tabular-nums">{{ selected.length }} ta</span>
            <button
               v-if="selected.length < products.length"
               type="button"
               class="admin-text-link"
               @click="selected = products.map((product) => product.id)"
            >
               Hammasi
            </button>
            <div class="adm-select-actions">
               <template v-if="status === 'deleted'">
                  <BaseButton size="xs" severity="secondary" :loading="bulkBusy" @click="runBulk('restore')">Tiklash</BaseButton>
               </template>
               <template v-else>
                  <BaseButton size="xs" severity="secondary" :loading="bulkBusy" @click="runBulk('activate')">Faollashtirish</BaseButton>
                  <BaseButton size="xs" severity="secondary" :loading="bulkBusy" @click="runBulk('deactivate')">To'xtatish</BaseButton>
                  <BaseButton size="xs" severity="secondary" :loading="bulkBusy" @click="runBulk('resolve')">Shikoyatni yopish</BaseButton>
                  <BaseButton size="xs" severity="danger" :disabled="bulkBusy" @click="askBulkArchive">Arxivlash</BaseButton>
               </template>
            </div>
         </div>

         <div v-if="loading && !products.length" class="admin-loading">E'lonlar yuklanmoqda…</div>
         <template v-else>
            <p v-if="products.length && !selected.length" class="adm-list-head">
               <span class="inline-flex items-center gap-1.5"><i class="adm-dot is-active" />faol</span>
               <span class="inline-flex items-center gap-1.5"><i class="adm-dot is-expired" />tugagan</span>
               <span class="ml-auto text-xs">bosib turing — tanlash</span>
            </p>

            <ul class="adm-list transition-opacity" :class="{ 'opacity-60': loading, 'is-selecting': selected.length > 0 }">
               <li v-for="product in products" :key="product.id">
                  <div
                     class="adm-item is-pressable"
                     :class="{ 'is-muted': !!product.deleted_at, 'is-selected': selected.includes(product.id) }"
                     role="button"
                     tabindex="0"
                     :aria-pressed="selected.length ? selected.includes(product.id) : undefined"
                     v-on="pressHandlers(product.id)"
                     @keydown.enter.prevent="tap(product.id)"
                  >
                     <span class="adm-item-main">
                        <span class="adm-item-title is-wrap"><span>{{ product.title }}</span></span>
                        <span class="adm-item-meta">
                           <b v-if="product.price">{{ formatProductPrice(product.price, product.price_type) }}</b>
                           <span>{{ product.category?.name || "Kategoriyasiz" }}</span>
                           <span v-if="product.district?.name">{{ product.district.name }}</span>
                           <span v-if="product.is_bot" class="inline-flex items-center gap-0.5"><Bot class="size-3" />AI</span>
                           <span v-if="product.same_phone_count" class="admin-badge admin-badge-warning">
                              <Copy class="size-3" />+{{ product.same_phone_count }}
                           </span>
                        </span>
                        <!-- Ko'rishlar · qo'ng'iroq · Telegram · sevimli -->
                        <span class="adm-item-stats">
                           <span title="Ko'rishlar"><Eye class="size-3.5" aria-hidden="true" />{{ num(product.views_count) }}<span class="sr-only"> ko'rish</span></span>
                           <span title="Qo'ng'iroq tugmasini bosishgan"><Phone class="size-3.5" aria-hidden="true" />{{ num(product.phone_clicks) }}<span class="sr-only"> qo'ng'iroq</span></span>
                           <span title="Telegram tugmasini bosishgan"><Send class="size-3.5" aria-hidden="true" />{{ num(product.telegram_clicks) }}<span class="sr-only"> Telegram</span></span>
                           <span title="Sevimlilarga qo'shganlar"><Heart class="size-3.5" aria-hidden="true" />{{ num(product.favorites_count) }}<span class="sr-only"> sevimli</span></span>
                        </span>
                     </span>
                     <span class="adm-item-side">
                        <span class="inline-flex items-center gap-2">
                           <span v-if="product.open_reports_count" class="admin-badge admin-badge-danger">
                              <Flag class="size-3" />{{ product.open_reports_count }}
                           </span>
                           <CircleCheck v-if="selected.includes(product.id)" class="adm-item-tick size-5" aria-hidden="true" />
                           <i v-else class="adm-dot" :class="dotClass(product)" :title="statusLabel(product)" />
                           <span class="sr-only">{{ statusLabel(product) }}</span>
                        </span>
                        <span>{{ timeAgo(product.created_at) }}</span>
                     </span>
                  </div>
               </li>
               <li v-if="products.length === 0" class="admin-empty-cell">Qidiruvga mos e'lon topilmadi.</li>
            </ul>
         </template>

         <!-- Tafsilot: bosilgan e'lon oynada (ro'yxat joyida qoladi) -->
         <AdminSheet
            :open="openProduct !== null"
            :title="openProduct?.title ?? ''"
            :subtitle="openProduct ? `#${openProduct.id} · ${statusLabel(openProduct)} · ${timeAgo(openProduct.created_at)}` : ''"
            @close="openId = null"
         >
            <template v-if="openProduct">
               <!-- Amal natijasi oynaning o'zida ko'rinsin -->
               <p v-if="error" class="admin-alert admin-alert-error">{{ error }}</p>
               <p v-else-if="notice" class="admin-alert admin-alert-success">{{ notice }}</p>

               <!-- Shikoyatlar birinchi: admin aynan shu sababdan ochgan bo'lishi mumkin -->
               <section v-if="openProduct.open_reports?.length" class="adm-detail-section">
                  <h3>Shikoyatlar ({{ openProduct.open_reports.length }})</h3>
                  <div class="admin-report-list">
                     <div v-for="report in openProduct.open_reports" :key="report.id" class="admin-report">
                        <b>{{ report.reason_label }}</b>
                        <span v-if="report.comment"> — {{ report.comment }}</span>
                        <small>{{ report.user?.name || "Foydalanuvchi" }} · {{ timeAgo(report.created_at) }}</small>
                     </div>
                  </div>
               </section>

               <!-- Qiziqish: necha kishi ko'rdi, bog'landi, saqlab qo'ydi -->
               <div class="adm-detail-stats">
                  <div><Eye class="size-4" aria-hidden="true" /><b>{{ num(openProduct.views_count) }}</b><small>ko'rish</small></div>
                  <div><Phone class="size-4" aria-hidden="true" /><b>{{ num(openProduct.phone_clicks) }}</b><small>qo'ng'iroq</small></div>
                  <div><Send class="size-4" aria-hidden="true" /><b>{{ num(openProduct.telegram_clicks) }}</b><small>Telegram</small></div>
                  <div><Heart class="size-4" aria-hidden="true" /><b>{{ num(openProduct.favorites_count) }}</b><small>sevimli</small></div>
               </div>

               <section v-if="openProduct.images?.length" class="adm-detail-section">
                  <h3>Rasmlar ({{ openProduct.images.length }})</h3>
                  <div class="admin-thumbs">
                     <a v-for="image in openProduct.images" :key="image.id" :href="imageUrl(image.src)" target="_blank" rel="noopener">
                        <img :src="imageUrl(image.thumb_src || image.src)" alt="" loading="lazy" />
                     </a>
                  </div>
               </section>

               <section v-if="openProduct.description" class="adm-detail-section">
                  <h3>Tavsif</h3>
                  <p class="admin-xdescription">{{ openProduct.description }}</p>
               </section>

               <dl class="admin-dl">
                  <div>
                     <dt>Muallif</dt>
                     <dd>
                        {{ openProduct.user?.name || "—" }}
                        <a v-if="openProduct.user?.username" :href="`https://t.me/${openProduct.user.username}`" target="_blank" rel="noopener">
                           @{{ openProduct.user.username }}
                        </a>
                        <span v-if="openProduct.user?.blocked_at" class="admin-badge admin-badge-danger ml-1">Bloklangan</span>
                     </dd>
                  </div>
                  <div>
                     <dt>Telefon</dt>
                     <dd>
                        <a v-if="openProduct.phone" :href="`tel:${openProduct.phone}`">{{ openProduct.phone }}</a>
                        <template v-else>—</template>
                        <button
                           v-if="openProduct.phone"
                           type="button"
                           class="block mt-1 text-xs underline underline-offset-2 text-(--z-muted-text) cursor-pointer"
                           @click="searchPhone(openProduct.phone)"
                        >
                           shu raqamli e'lonlar{{ openProduct.same_phone_count ? ` (+${openProduct.same_phone_count})` : "" }}
                        </button>
                     </dd>
                  </div>
                  <div>
                     <dt>Narx</dt>
                     <dd>{{ formatProductPrice(openProduct.price, openProduct.price_type) }}</dd>
                  </div>
                  <div>
                     <dt>Hudud</dt>
                     <dd>{{ openProduct.district?.name || "Butun viloyat" }}</dd>
                  </div>
                  <div>
                     <dt>Kategoriya</dt>
                     <dd>{{ openProduct.category?.name || "Kategoriyasiz" }}</dd>
                  </div>
                  <div>
                     <dt>Joylangan</dt>
                     <dd>{{ formatDateTime(openProduct.created_at) }}</dd>
                  </div>
                  <div>
                     <dt>Muddati</dt>
                     <dd>
                        {{ openProduct.is_permanent ? "Muddatsiz" : openProduct.expires_at ? formatDateTime(openProduct.expires_at) : "—" }}
                        <div v-if="!openProduct.deleted_at" class="mt-1.5">
                           <FieldSelect
                              :key="`expiry-${openProduct.id}-${openProduct.expires_at}`"
                              size="sm"
                              placeholder="Muddatni o'zgartirish…"
                              :options="EXPIRY_OPTIONS"
                              :disabled="busyId === openProduct.id"
                              @change="(value: string) => changeExpiry(openProduct!, value)"
                           />
                        </div>
                     </dd>
                  </div>
                  <div v-if="openProduct.is_bot">
                     <dt>Manba</dt>
                     <dd>{{ openProduct.source_channel ? `@${openProduct.source_channel.replace(/^@/, "")}` : "Bot" }}</dd>
                  </div>
                  <div v-for="value in openProduct.parameter_values" :key="value.id">
                     <dt>{{ value.parameter?.title }}</dt>
                     <dd>{{ value.value }} {{ value.parameter?.unit || "" }}</dd>
                  </div>
               </dl>
            </template>

            <template v-if="openProduct" #actions>
               <a :href="`/product/${openProduct.id}`" target="_blank" rel="noopener">
                  <BaseButton size="sm" severity="secondary">
                     <template #icon><ExternalLink class="size-3.5" /></template>
                     Ilovada ochish
                  </BaseButton>
               </a>

               <template v-if="openProduct.deleted_at">
                  <BaseButton size="sm" severity="secondary" :loading="busyId === openProduct.id" @click="restore(openProduct)">
                     <template #icon><RotateCcw class="size-3.5" /></template>
                     Tiklash
                  </BaseButton>
                  <BaseButton size="sm" severity="danger" class="admin-xactions-wide" @click="askForceDelete(openProduct)">
                     <template #icon><Trash2 class="size-3.5" /></template>
                     Butunlay o'chirish
                  </BaseButton>
               </template>

               <template v-else>
                  <BaseButton size="sm" severity="secondary" @click="editId = openProduct.id">
                     <template #icon><Pencil class="size-3.5" /></template>
                     Tahrirlash
                  </BaseButton>
                  <BaseButton size="sm" severity="secondary" :loading="busyId === openProduct.id" @click="changeStatus(openProduct)">
                     <template #icon>
                        <component :is="openProduct.is_active ? PauseCircle : PlayCircle" class="size-3.5" />
                     </template>
                     {{ openProduct.is_active ? "To'xtatish" : "Faollashtirish" }}
                  </BaseButton>
                  <BaseButton
                     v-if="!openProduct.is_bot && openProduct.user"
                     size="sm"
                     severity="secondary"
                     @click="messageTarget = openProduct"
                  >
                     <template #icon><MessageSquare class="size-3.5" /></template>
                     Muallifga yozish
                  </BaseButton>
                  <BaseButton
                     v-if="openProduct.open_reports_count"
                     size="sm"
                     severity="secondary"
                     :loading="busyId === openProduct.id"
                     @click="resolveReports(openProduct)"
                  >
                     <template #icon><CheckCheck class="size-3.5" /></template>
                     Shikoyatlarni yopish
                  </BaseButton>
                  <BaseButton size="sm" severity="danger" class="admin-xactions-wide" @click="askArchive(openProduct)">
                     <template #icon><Archive class="size-3.5" /></template>
                     Arxivlash
                  </BaseButton>
               </template>

               <BaseButton
                  v-if="openProduct.user && userFilter?.id !== openProduct.user.id"
                  size="sm"
                  severity="secondary"
                  @click="filterByUser(openProduct.user)"
               >
                  <template #icon><UserRound class="size-3.5" /></template>
                  Muallifning e'lonlari
               </BaseButton>
            </template>
         </AdminSheet>

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
import { computed, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
   Archive,
   Bot,
   CheckCheck,
   CircleCheck,
   Copy,
   ExternalLink,
   Eye,
   Flag,
   Heart,
   MessageSquare,
   PauseCircle,
   Phone,
   Pencil,
   PlayCircle,
   RotateCcw,
   Search,
   Send,
   Trash2,
   TriangleAlert,
   UserRound,
   X,
} from "lucide-vue-next";
import { isValidKey, relativeLabel } from "@admin/modules/dates";
import AdminRepo from "@admin/entities/AdminRepo";
import BaseButton from "@shared/ui/BaseButton.vue";
import BaseModal from "@shared/ui/BaseModal.vue";
import FieldSelect from "@shared/ui/FieldSelect.vue";
import AdminMessageDialog from "@admin/components/AdminMessageDialog.vue";
import AdminProductEditDrawer from "@admin/components/AdminProductEditDrawer.vue";
import AdminSheet from "@admin/components/AdminSheet.vue";
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
const counts = ref<Partial<Record<Status | "duplicates", number>>>({});
// Boshqa sahifadan aniq e'longa havola: /admin/products?search=#12
const searchInput = ref(typeof route.query.search === "string" ? route.query.search : "");
const searchValue = ref(searchInput.value);
const status = ref<Status>((route.query.status as Status) || "all");
const isBot = ref("all");
const duplicates = ref(route.query.duplicates === "1");
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
// Bosh sahifadan: "shu kuni joylangan e'lonlar"
const dateFilter = ref<string | null>(isValidKey(route.query.date) ? route.query.date : null);
const loading = ref(true);
const error = ref("");
const notice = ref("");
// ?open=ID — bosh sahifadan kelganda shu yozuv darhol oynada ochiladi
const openId = ref<number | null>(Number(route.query.open) || null);
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
         duplicates: duplicates.value ? 1 : undefined,
         date: dateFilter.value || undefined,
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

function toggleDuplicates() {
   duplicates.value = !duplicates.value;
   openId.value = null;
   load(1);
}

/** Shu telefon raqami bilan qo'yilgan barcha e'lonlar (har qanday holatda, arxiv ham) */
function searchPhone(phone: string) {
   searchInput.value = phone;
   searchValue.value = phone;
   status.value = "all";
   duplicates.value = false;
   openId.value = null;
   load(1);
}

function setStatus(value: Status) {
   if (status.value === value) return;
   status.value = value;
   openId.value = null;
   load(1);
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
   router.replace({ query: { ...route.query, user_id: undefined, user_name: undefined } });
   load(1);
}

function clearDateFilter() {
   dateFilter.value = null;
   router.replace({ query: { ...route.query, date: undefined } });
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

function changeExpiry(product: AdminProduct, value: string) {
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

/*
   Telegramdagidek tanlash: qatorni ~0.45 soniya bosib turish — tanlanadi va tanlash rejimi yoqiladi.
   Rejimda oddiy bosish qo'shadi/olib tashlaydi; hech narsa tanlanmagan bo'lsa bosish — oynani ochadi.
*/
const LONG_PRESS_MS = 450;
let pressTimer: ReturnType<typeof setTimeout> | null = null;
let pressStart: { x: number; y: number } | null = null;
let longPressed = false;

function toggleSelected(id: number) {
   selected.value = selected.value.includes(id) ? selected.value.filter((value) => value !== id) : [...selected.value, id];
}

function tap(id: number) {
   if (selected.value.length) toggleSelected(id);
   else openId.value = id;
}

function cancelPress() {
   if (pressTimer) clearTimeout(pressTimer);
   pressTimer = null;
   pressStart = null;
}

function pressHandlers(id: number) {
   return {
      pointerdown: (event: PointerEvent) => {
         if (event.button !== 0) return;
         longPressed = false;
         pressStart = { x: event.clientX, y: event.clientY };
         pressTimer = setTimeout(() => {
            longPressed = true;
            pressTimer = null;
            if (!selected.value.includes(id)) toggleSelected(id);
            navigator.vibrate?.(12);
         }, LONG_PRESS_MS);
      },
      // Barmoq siljisa (ro'yxatni aylantirish) — tanlanmaydi
      pointermove: (event: PointerEvent) => {
         if (pressStart && Math.hypot(event.clientX - pressStart.x, event.clientY - pressStart.y) > 10) cancelPress();
      },
      pointerup: cancelPress,
      pointercancel: cancelPress,
      pointerleave: cancelPress,
      // Bosib turish tugagach keladigan "click" oynani ochmasin
      click: () => {
         if (longPressed) {
            longPressed = false;
            return;
         }
         tap(id);
      },
      // Telefonda bosib turganda chiqadigan tizim menyusi kerak emas
      contextmenu: (event: Event) => event.preventDefault(),
   };
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

const num = (value?: number | null) => (value ?? 0).toLocaleString("ru-RU");
/** Yashil — faol, sariq — muddati tugagan/to'xtatilgan, kulrang — arxiv */
function dotClass(product: AdminProduct) {
   return product.deleted_at ? "" : product.is_active ? "is-active" : "is-expired";
}

// Oynada ko'rsatiladigan e'lon: ro'yxat yangilansa (amal bajarilgach) yangi ma'lumot ko'rinadi
const openProduct = computed(() => products.value.find((product) => product.id === openId.value) ?? null);
// Boshqa e'lon ochilganda oldingi amal xabari ko'rinmasin
watch(openId, () => {
   notice.value = "";
});

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
