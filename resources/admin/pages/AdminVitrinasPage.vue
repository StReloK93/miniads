<template>
   <section class="admin-page">
      <header class="admin-page-heading">
         <div>
            <h1>Vitrinalar</h1>
            <p class="admin-muted">Biznes sahifalari: tasdiqlanmaguncha ilovada ko'rinmaydi.</p>
         </div>
      </header>

      <!-- Holat izohi + filtr: kartani bosish — shu holatdagilar; qayta bosish — hammasi -->
      <div class="adm-vstatus">
         <button
            v-for="option in statuses"
            :key="option.value"
            type="button"
            class="adm-vstatus-card"
            :class="[`is-${option.value}`, { 'is-active': status === option.value }]"
            :aria-pressed="status === option.value"
            @click="status = status === option.value ? '' : option.value"
         >
            <span class="adm-vstatus-icon"><component :is="option.icon" class="size-4" aria-hidden="true" /></span>
            <strong>{{ statusCounts[option.value] ?? 0 }}</strong>
            <span>{{ option.label }}</span>
            <small>{{ option.hint }}</small>
         </button>
      </div>

      <section class="admin-table-panel">
         <!-- Turlar bo'yicha filtr -->
         <nav class="admin-filter-chips" aria-label="Vitrina turi">
            <button type="button" class="admin-filter-chip" :class="{ 'is-active': type === '' }" @click="type = ''">
               Hammasi <span>{{ byStatus.length }}</span>
            </button>
            <button
               v-for="option in typeOptions"
               :key="option.value"
               type="button"
               class="admin-filter-chip"
               :class="{ 'is-active': type === option.value }"
               @click="type = type === option.value ? '' : option.value"
            >
               <component :is="option.icon" class="size-3.5" aria-hidden="true" />
               {{ option.label }} <span>{{ typeCounts[option.value] ?? 0 }}</span>
            </button>
         </nav>

         <p v-if="error" class="admin-alert admin-alert-error m-3">{{ error }}</p>
         <div v-if="loading" class="admin-loading">Yuklanmoqda…</div>
         <ul v-else class="adm-list">
            <li v-for="item in visible" :key="item.id">
               <button type="button" class="adm-item" @click="openId = item.id">
                  <span class="adm-vstate" :class="`is-${item.status}`" :title="statusLabel(item.status)">
                     <component :is="statusIcon(item.status)" class="size-4" aria-hidden="true" />
                     <span class="sr-only">{{ statusLabel(item.status) }}</span>
                  </span>
                  <span class="adm-item-main">
                     <span class="adm-item-title">
                        <span>{{ item.name }}</span>
                        <BadgeCheck v-if="item.is_verified" class="size-4 shrink-0 text-(--admin-info)" aria-label="Ishonchli belgisi bor" />
                     </span>
                     <span class="adm-item-meta">
                        <span class="inline-flex items-center gap-1">
                           <component :is="typeIcon(item.type)" class="size-3" aria-hidden="true" />{{ typeLabel(item.type) }}
                        </span>
                        <span>{{ item.district?.name ?? "Butun viloyat" }}</span>
                        <span>{{ item.items_count ?? 0 }} ta mahsulot</span>
                     </span>
                     <span class="adm-item-meta">
                        <span class="truncate">{{ item.user?.name }}<template v-if="item.user?.username"> · @{{ item.user.username }}</template></span>
                     </span>
                  </span>
                  <span class="adm-item-side">
                     <span class="inline-flex items-center gap-1"><Eye class="size-3.5" aria-hidden="true" />{{ item.views_count ?? 0 }}</span>
                     <span v-if="item.updated_at">{{ timeAgo(item.updated_at) }}</span>
                  </span>
               </button>
            </li>
            <li v-if="!visible.length" class="admin-empty-cell">
               {{ items.length ? "Bu filtrga mos vitrina yo'q." : "Hali vitrina yo'q." }}
            </li>
         </ul>
      </section>

      <!-- Tafsilot va amallar -->
      <AdminSheet
         :open="openItem !== null"
         :title="openItem?.name ?? ''"
         :subtitle="openItem ? `${typeLabel(openItem.type)} · ${statusLabel(openItem.status)}` : ''"
         @close="openId = null"
      >
         <template v-if="openItem">
            <p v-if="error" class="admin-alert admin-alert-error">{{ error }}</p>

            <div class="adm-vstate-line" :class="`is-${openItem.status}`">
               <component :is="statusIcon(openItem.status)" class="size-5 shrink-0" aria-hidden="true" />
               <span>
                  <b>{{ statusLabel(openItem.status) }}</b>
                  <small>{{ statuses.find((option) => option.value === openItem!.status)?.hint }}</small>
                  <small v-if="openItem.status === 'rejected' && openItem.reject_reason">Sabab: {{ openItem.reject_reason }}</small>
               </span>
            </div>

            <p v-if="openItem.description" class="admin-xdescription">{{ openItem.description }}</p>

            <dl class="admin-dl">
               <div>
                  <dt>Egasi</dt>
                  <dd>
                     {{ openItem.user?.name ?? "—" }}
                     <a v-if="openItem.user?.username" :href="`https://t.me/${openItem.user.username}`" target="_blank" rel="noopener">
                        @{{ openItem.user.username }}
                     </a>
                  </dd>
               </div>
               <div>
                  <dt>Hudud</dt>
                  <dd>{{ openItem.district?.name ?? "Butun viloyat" }}</dd>
               </div>
               <div v-if="openItem.phone">
                  <dt>Telefon</dt>
                  <dd><a :href="`tel:${openItem.phone}`">{{ openItem.phone }}</a></dd>
               </div>
               <div v-if="openItem.address">
                  <dt>Manzil</dt>
                  <dd>{{ openItem.address }}</dd>
               </div>
               <div>
                  <dt>Mahsulotlar</dt>
                  <dd>{{ openItem.items_count ?? 0 }} ta</dd>
               </div>
               <div>
                  <dt>Ko'rishlar</dt>
                  <dd class="tabular-nums">{{ openItem.views_count ?? 0 }}</dd>
               </div>
               <div>
                  <dt>Qo'ng'iroq · Telegram (30 kun)</dt>
                  <dd class="tabular-nums">{{ openItem.phone_clicks ?? 0 }} · {{ openItem.telegram_clicks ?? 0 }}</dd>
               </div>
               <div>
                  <dt>Ishonchli belgisi</dt>
                  <dd>{{ openItem.is_verified ? "Bor" : "Yo'q" }}</dd>
               </div>
            </dl>
         </template>

         <template v-if="openItem" #actions>
            <BaseButton
               v-if="openItem.status !== 'approved'"
               size="sm"
               severity="success"
               :loading="busyId === openItem.id"
               @click="update(openItem, { status: 'approved' })"
            >
               <template #icon><CircleCheck class="size-3.5" /></template>
               Tasdiqlash
            </BaseButton>
            <a :href="`/v/${openItem.slug}`" target="_blank" rel="noopener">
               <BaseButton size="sm" severity="secondary">
                  <template #icon><ExternalLink class="size-3.5" /></template>
                  Ochib ko'rish
               </BaseButton>
            </a>
            <BaseButton
               v-if="openItem.status === 'approved'"
               size="sm"
               severity="secondary"
               :loading="busyId === openItem.id"
               @click="update(openItem, { is_verified: !openItem.is_verified })"
            >
               <template #icon><BadgeCheck class="size-3.5" /></template>
               {{ openItem.is_verified ? "Belgini olish" : "Ishonchli belgisi" }}
            </BaseButton>
            <BaseButton v-if="openItem.status !== 'rejected'" size="sm" severity="secondary" @click="openReject(openItem)">
               <template #icon><CircleX class="size-3.5" /></template>
               Rad etish
            </BaseButton>
            <BaseButton size="sm" severity="danger" class="admin-xactions-wide" @click="deleting = openItem">
               <template #icon><Trash2 class="size-3.5" /></template>
               O'chirish
            </BaseButton>
         </template>
      </AdminSheet>

      <!-- Rad etish sababi -->
      <BaseModal
         :open="rejecting !== null"
         title="Vitrinani rad etish"
         :description="`“${rejecting?.name}” egasi sababni ilovada ko'radi.`"
         confirm-text="Rad etish"
         cancel-text="Bekor qilish"
         danger
         @close="rejecting = null"
         @confirm="confirmReject"
      >
         <textarea v-model="rejectReason" class="admin-input w-full" rows="3" placeholder="Masalan: rasmlar boshqa saytdan olingan" />
      </BaseModal>

      <!-- Butunlay o'chirish: mahsulotlar va rasmlar diskdan ham -->
      <BaseModal
         :open="deleting !== null"
         title="Vitrinani o'chirish"
         :description="`“${deleting?.name}” mahsulotlari va rasmlari bilan butunlay o'chadi. Qaytarib bo'lmaydi.`"
         confirm-text="O'chirish"
         cancel-text="Bekor qilish"
         danger
         @close="deleting = null"
         @confirm="confirmDelete"
      >
         <template #icon><Trash2 class="size-4 text-(--z-danger)" /></template>
      </BaseModal>
   </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
   BadgeCheck,
   Building2,
   CircleCheck,
   CircleX,
   Clock,
   ExternalLink,
   Eye,
   Images,
   ReceiptText,
   Store,
   Trash2,
   UtensilsCrossed,
} from "lucide-vue-next";
import AdminRepo from "@admin/entities/AdminRepo";
import AdminSheet from "@admin/components/AdminSheet.vue";
import BaseButton from "@shared/ui/BaseButton.vue";
import BaseModal from "@shared/ui/BaseModal.vue";
import { timeAgo } from "@shared/modules/formatters";
import type { IVitrina, VitrinaType } from "@shared/types";

type AdminVitrina = IVitrina & {
   user?: { id: number; name: string; username: string | null };
   phone_clicks?: number;
   telegram_clicks?: number;
};
type Status = "pending" | "approved" | "rejected";

const statuses: { value: Status; label: string; hint: string; icon: any }[] = [
   { value: "pending", label: "Kutilmoqda", hint: "Ko'rib chiqish kerak — ilovada hali ko'rinmaydi", icon: Clock },
   { value: "approved", label: "Tasdiqlangan", hint: "Ilovada hamma ko'radi", icon: CircleCheck },
   { value: "rejected", label: "Rad etilgan", hint: "Ko'rinmaydi, egasi sababni ko'radi", icon: CircleX },
];

const typeOptions: { value: VitrinaType; label: string; icon: any }[] = [
   { value: "shop", label: "Do'kon", icon: Store },
   { value: "menu", label: "Menyu", icon: UtensilsCrossed },
   { value: "portfolio", label: "Portfolio", icon: Images },
   { value: "pricelist", label: "Xizmatlar", icon: ReceiptText },
   { value: "realty", label: "Ko'chmas mulk", icon: Building2 },
];

const route = useRoute();
const router = useRouter();

// Kirganda hammasi ko'rinadi; filtr URL'da saqlanadi (orqaga qaytilsa yo'qolmaydi)
const status = ref<Status | "">(statuses.some((option) => option.value === route.query.status) ? (route.query.status as Status) : "");
const type = ref<VitrinaType | "">(typeOptions.some((option) => option.value === route.query.type) ? (route.query.type as VitrinaType) : "");

const items = ref<AdminVitrina[]>([]);
const loading = ref(true);
const error = ref("");
const busyId = ref<number | null>(null);
const openId = ref<number | null>(null);
const rejecting = ref<AdminVitrina | null>(null);
const rejectReason = ref("");
const deleting = ref<AdminVitrina | null>(null);

const byStatus = computed(() => items.value.filter((item) => !status.value || item.status === status.value));
const visible = computed(() => byStatus.value.filter((item) => !type.value || item.type === type.value));
const openItem = computed(() => items.value.find((item) => item.id === openId.value) ?? null);

const statusCounts = computed(() => countBy(items.value.filter((item) => !type.value || item.type === type.value), "status"));
const typeCounts = computed(() => countBy(byStatus.value, "type"));

function countBy(list: AdminVitrina[], key: "status" | "type") {
   return list.reduce<Record<string, number>>((counts, item) => {
      const value = String(item[key] ?? "");
      counts[value] = (counts[value] ?? 0) + 1;
      return counts;
   }, {});
}

// Filtr o'zgarsa URL yangilanadi
watch([status, type], () =>
   router.replace({ query: { ...route.query, status: status.value || undefined, type: type.value || undefined } }),
);

const statusLabel = (value?: string) => statuses.find((option) => option.value === value)?.label ?? value ?? "";
const statusIcon = (value?: string) => statuses.find((option) => option.value === value)?.icon ?? Clock;
const typeLabel = (value?: string) => typeOptions.find((option) => option.value === value)?.label ?? value ?? "";
const typeIcon = (value?: string) => typeOptions.find((option) => option.value === value)?.icon ?? Store;

async function load() {
   loading.value = true;
   error.value = "";
   try {
      const { data } = await AdminRepo.vitrinas();
      items.value = data;
   } catch {
      error.value = "Ro'yxatni yuklab bo'lmadi.";
   } finally {
      loading.value = false;
   }
}

async function update(item: AdminVitrina, payload: { status?: Status; is_verified?: boolean; reject_reason?: string }) {
   busyId.value = item.id;
   error.value = "";
   try {
      const { data } = await AdminRepo.updateVitrina(item.id, payload);
      // Filtrga mos kelmasa ham ro'yxatda qoladi — oyna ochiq turadi, yangi holat ko'rinadi
      items.value = items.value.map((row) => (row.id === item.id ? { ...row, ...data } : row));
   } catch {
      error.value = "Saqlab bo'lmadi.";
   } finally {
      busyId.value = null;
   }
}

function openReject(item: AdminVitrina) {
   rejecting.value = item;
   rejectReason.value = "";
}

async function confirmReject() {
   if (!rejecting.value) return;
   const item = rejecting.value;
   rejecting.value = null;
   await update(item, { status: "rejected", reject_reason: rejectReason.value.trim() });
}

async function confirmDelete() {
   if (!deleting.value) return;
   const item = deleting.value;
   deleting.value = null;
   busyId.value = item.id;
   try {
      await AdminRepo.deleteVitrina(item.id);
      items.value = items.value.filter((row) => row.id !== item.id);
      openId.value = null;
   } catch {
      error.value = "O'chirib bo'lmadi.";
   } finally {
      busyId.value = null;
   }
}

onMounted(load);
</script>
