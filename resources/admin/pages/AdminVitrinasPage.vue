<template>
   <section class="admin-page">
      <header class="admin-page-heading">
         <div>
            <h1>Vitrinalar</h1>
            <p class="admin-muted">Biznes sahifalari: tasdiqlanmaguncha ilovada ko'rinmaydi. "Tasdiqlangan" belgisi — ishonchli bizneslar uchun.</p>
         </div>
      </header>

      <div class="admin-filter-chips mb-3">
         <button
            v-for="option in filters"
            :key="option.value"
            type="button"
            class="admin-filter-chip"
            :class="{ 'is-active': status === option.value }"
            @click="status = option.value"
         >
            {{ option.label }}
         </button>
      </div>

      <p v-if="error" class="admin-alert admin-alert-error">{{ error }}</p>

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

      <!-- Butunlay o'chirish: mahsulotlar, logo, muqova va rasmlar diskdan ham -->
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

      <section class="admin-chart-card py-1!">
         <div v-if="loading" class="admin-loading">Yuklanmoqda…</div>
         <div v-else class="admin-list">
            <div v-for="item in items" :key="item.id" class="admin-list-row">
               <span class="admin-list-main">
                  <span class="admin-list-title">
                     <a :href="`/v/${item.slug}`" target="_blank" class="admin-text-link">{{ item.name }}</a>
                     <span class="admin-badge ml-1" :class="statusBadge(item.status)">{{ statusLabel(item.status) }}</span>
                     <span v-if="item.is_verified" class="admin-badge admin-badge-info ml-1">✓ Tasdiqlangan</span>
                  </span>
                  <span class="admin-list-meta">
                     {{ typeLabels[item.type] ?? item.type }} · {{ item.district?.name ?? "Butun viloyat" }} · {{ item.items_count }} ta mahsulot ·
                     {{ item.user?.name }}<template v-if="item.user?.username"> (@{{ item.user.username }})</template>
                     <template v-if="item.reject_reason"> · Sabab: {{ item.reject_reason }}</template>
                  </span>
                  <span class="admin-list-meta" title="Ko'rishlar — jami; qo'ng'iroq va Telegram bosishlari — so'nggi 30 kun">
                     <Eye class="inline size-3.5 -mt-0.5" /> {{ item.views_count ?? 0 }} ko'rish ·
                     <Phone class="inline size-3.5 -mt-0.5" /> {{ item.phone_clicks ?? 0 }} qo'ng'iroq ·
                     <Send class="inline size-3.5 -mt-0.5" /> {{ item.telegram_clicks ?? 0 }} Telegram
                     <span class="opacity-70">(30 kun)</span>
                  </span>
               </span>
               <BaseButton v-if="item.status !== 'approved'" size="xs" severity="success" :loading="busyId === item.id" @click="update(item, { status: 'approved' })">
                  Tasdiqlash
               </BaseButton>
               <BaseButton v-if="item.status !== 'rejected'" size="xs" severity="danger" variant="text" @click="openReject(item)">
                  Rad etish
               </BaseButton>
               <BaseButton
                  v-if="item.status === 'approved'"
                  size="xs"
                  severity="secondary"
                  :title="item.is_verified ? 'Belgini olib tashlash' : 'Tasdiqlangan belgisini berish'"
                  @click="update(item, { is_verified: !item.is_verified })"
               >
                  {{ item.is_verified ? "Belgini olish" : "✓ Belgi" }}
               </BaseButton>
               <BaseButton size="xs" severity="danger" variant="text" :loading="busyId === item.id" @click="deleting = item">
                  O'chirish
               </BaseButton>
            </div>
            <p v-if="!items.length" class="admin-muted py-3">Bu holatda vitrina yo'q.</p>
         </div>
      </section>
   </section>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from "vue";
import AdminRepo from "@admin/entities/AdminRepo";
import BaseButton from "@shared/ui/BaseButton.vue";
import BaseModal from "@shared/ui/BaseModal.vue";
import { Eye, Phone, Send, Trash2 } from "lucide-vue-next";
import type { IVitrina } from "@shared/types";

type AdminVitrina = IVitrina & {
   user?: { id: number; name: string; username: string | null };
   phone_clicks?: number;
   telegram_clicks?: number;
};
type Status = "pending" | "approved" | "rejected";

const filters: { value: Status | ""; label: string }[] = [
   { value: "pending", label: "Kutilmoqda" },
   { value: "approved", label: "Tasdiqlangan" },
   { value: "rejected", label: "Rad etilgan" },
   { value: "", label: "Hammasi" },
];
const typeLabels: Record<string, string> = {
   shop: "Do'kon",
   menu: "Menyu",
   portfolio: "Portfolio",
   pricelist: "Xizmatlar",
   realty: "Ko'chmas mulk",
};

const status = ref<Status | "">("pending");
const items = ref<AdminVitrina[]>([]);
const loading = ref(true);
const error = ref("");
const busyId = ref<number | null>(null);
const rejecting = ref<AdminVitrina | null>(null);
const rejectReason = ref("");
const deleting = ref<AdminVitrina | null>(null);

function statusLabel(value?: string) {
   return filters.find((item) => item.value === value)?.label ?? value;
}

function statusBadge(value?: string) {
   return value === "approved" ? "admin-badge-success" : value === "rejected" ? "admin-badge-danger" : "admin-badge-warning";
}

async function load() {
   loading.value = true;
   error.value = "";
   try {
      const { data } = await AdminRepo.vitrinas(status.value || undefined);
      items.value = data;
   } catch {
      error.value = "Ro'yxatni yuklab bo'lmadi.";
   } finally {
      loading.value = false;
   }
}

async function update(item: AdminVitrina, payload: { status?: Status; is_verified?: boolean; reject_reason?: string }) {
   busyId.value = item.id;
   try {
      const { data } = await AdminRepo.updateVitrina(item.id, payload);
      // Filtrga endi mos kelmasa — ro'yxatdan chiqadi
      items.value = status.value && data.status !== status.value
         ? items.value.filter((row) => row.id !== item.id)
         : items.value.map((row) => (row.id === item.id ? data : row));
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
   } catch {
      error.value = "O'chirib bo'lmadi.";
   } finally {
      busyId.value = null;
   }
}

watch(status, load);
onMounted(load);
</script>
