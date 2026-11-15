<template>
   <section class="full-page flex flex-col">
      <aside v-if="vitrina" class="grow relative -mt-[calc(var(--safe-area-top)+var(--spacing)*4)]">
         <article class="absolute inset-0 overflow-y-auto no-scrollbar pb-6">
            <!-- Muqova: rasm yoki vitrina turi rangidagi fon -->
            <header class="cover" :style="{ '--tone': meta.tone }">
               <img v-if="cover" :src="cover" :alt="vitrina.name" class="absolute inset-0 size-full object-cover" />
               <component :is="meta.icon" v-else class="cover-icon" stroke-width="1.2" />
               <div class="cover-actions">
                  <button type="button" class="cover-button" :aria-label="$t('common.back')" @click="goBack(router)">
                     <ArrowLeft class="size-5" />
                  </button>
                  <button type="button" class="cover-button" :aria-label="$t('product.share')" @click="share">
                     <component :is="isCopied ? Check : Share2" class="size-4.5" />
                  </button>
               </div>
            </header>

            <main class="px-4">
               <div class="-mt-9 relative flex items-end gap-3">
                  <VitrinaAvatar :vitrina="vitrina" class="size-18 text-2xl border-4 border-(--z-background)" />
               </div>

               <h1 class="mt-2 text-xl font-bold leading-tight flex items-center gap-1.5">
                  <span>{{ vitrina.name }}</span>
                  <BadgeCheck v-if="vitrina.is_verified" class="size-5 shrink-0 text-(--z-primary)" />
               </h1>
               <p class="mt-1 text-xs text-(--z-muted-text) flex flex-wrap items-center gap-x-2 gap-y-1">
                  <span>{{ $t(`vitrina.types.${vitrina.type}`) }}</span>
                  <span class="dot" />
                  <span class="inline-flex items-center gap-1"><MapPin class="size-3" />{{ vitrina.district?.name || $t("district.region") }}</span>
                  <template v-if="vitrina.items_count">
                     <span class="dot" />
                     <span>{{ $t("vitrina.itemsCount", vitrina.items_count) }}</span>
                  </template>
               </p>

               <!-- Egasi uchun: holati va tahrirlash -->
               <div v-if="vitrina.is_owner" class="owner-bar mt-4" :class="`is-${vitrina.status}`">
                  <p class="grow text-xs leading-snug">
                     {{ $t(`vitrina.status.${vitrina.status}`) }}
                     <template v-if="vitrina.reject_reason">— {{ vitrina.reject_reason }}</template>
                  </p>
                  <BaseButton size="xs" severity="secondary" @click="router.push({ name: 'my-vitrina' })">
                     <template #icon><Pencil class="size-3.5" /></template>
                     {{ $t("vitrina.edit") }}
                  </BaseButton>
               </div>

               <p v-if="vitrina.description" class="mt-4 text-sm leading-relaxed whitespace-pre-line select-text">
                  {{ vitrina.description }}
               </p>

               <ul v-if="infoRows.length" class="mt-4 info card divide-y divide-(--z-border)">
                  <li v-for="row in infoRows" :key="row.key">
                     <component
                        :is="row.action ? 'button' : 'div'"
                        type="button"
                        class="info-row"
                        @click="row.action?.()"
                     >
                        <component :is="row.icon" class="size-4 shrink-0 text-(--z-muted-text)" />
                        <span class="grow text-sm">{{ row.text }}</span>
                        <ChevronRight v-if="row.action" class="size-4 text-(--z-muted-text)" />
                     </component>
                  </li>
               </ul>

               <!-- Bo'limlar: tepada yopishib turadigan chiplar -->
               <nav v-if="groups.length > 1" class="section-chips">
                  <button type="button" class="section-chip" :class="{ 'is-active': activeGroup === 'all' }" @click="activeGroup = 'all'">
                     {{ $t("common.all") }}
                  </button>
                  <button
                     v-for="group in groups"
                     :key="group.key"
                     type="button"
                     class="section-chip"
                     :class="{ 'is-active': activeGroup === group.key }"
                     @click="activeGroup = group.key"
                  >
                     {{ group.name }}
                     <span class="opacity-60 tabular-nums">{{ group.items.length }}</span>
                  </button>
               </nav>

               <section v-for="group in visibleGroups" :key="group.key" class="mt-5">
                  <h3 v-if="groups.length > 1 && activeGroup === 'all'" class="font-bold text-base mb-2.5">{{ group.name }}</h3>
                  <div v-if="group.layout === 'grid'" class="grid grid-cols-2 gap-2.5">
                     <VitrinaItemCard
                        v-for="item in group.items"
                        :key="item.id"
                        :item="item"
                        layout="grid"
                        :tone="meta.tone"
                        :icon="meta.icon"
                        @open="openedItem = $event"
                     />
                  </div>
                  <div v-else class="divide-y divide-(--z-border)">
                     <VitrinaItemCard
                        v-for="item in group.items"
                        :key="item.id"
                        :item="item"
                        layout="list"
                        :tone="meta.tone"
                        :icon="meta.icon"
                        @open="openedItem = $event"
                     />
                  </div>
               </section>

               <p v-if="!groups.length" class="py-12 text-center text-sm text-(--z-muted-text)">{{ $t("vitrina.emptyItems") }}</p>
            </main>
         </article>
      </aside>

      <aside v-else-if="isFirstLoading" class="grow">
         <div class="skeleton h-40 rounded-none! -mt-[calc(var(--safe-area-top)+var(--spacing)*4)]"></div>
         <div class="px-4">
            <div class="skeleton size-18 rounded-full! -mt-9 mb-3"></div>
            <div class="skeleton h-5 w-48 mb-2"></div>
            <div class="skeleton h-3 w-32 mb-6"></div>
            <div class="grid grid-cols-2 gap-2.5">
               <div v-for="n in 4" :key="n" class="skeleton aspect-square"></div>
            </div>
         </div>
      </aside>

      <aside v-else class="grow flex flex-col items-center justify-center text-center p-6">
         <Store class="size-10 text-(--z-muted-text) mb-3" />
         <h2 class="text-lg font-bold mb-1">{{ $t("vitrina.notFound") }}</h2>
         <BaseButton class="mt-4" severity="secondary" @click="router.replace({ name: 'vitrinas' })">{{ $t("vitrina.all") }}</BaseButton>
      </aside>

      <!-- Bog'lanish: doim pastda -->
      <aside v-if="vitrina && (vitrina.phone || vitrina.telegram)" class="px-4 pt-4 border-t border-(--z-border) flex gap-3">
         <BaseButton v-if="vitrina.phone" :severity="vitrina.telegram ? 'secondary' : 'primary'" class="grow" @click="contactPhone">
            <template #icon><Phone class="size-4" /></template>
            {{ $t("product.call") }}
         </BaseButton>
         <BaseButton v-if="vitrina.telegram" class="grow" @click="contactTelegram">
            <template #icon><MessageCircle class="size-4" /></template>
            {{ $t("product.writeTelegram") }}
         </BaseButton>
      </aside>

      <VitrinaItemDrawer
         :item="openedItem"
         :telegram="vitrina?.telegram"
         :phone="vitrina?.phone"
         @close="openedItem = null"
      />
   </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch, type Component } from "vue";
import { useRoute, useRouter } from "vue-router";
import { isTMA, postEvent } from "@tma.js/bridge";
import {
   ArrowLeft,
   AtSign,
   BadgeCheck,
   Check,
   ChevronRight,
   Clock,
   MapPin,
   MessageCircle,
   Navigation,
   Pencil,
   Phone,
   Share2,
   Store,
} from "lucide-vue-next";
import { t } from "@shared/i18n";
import BaseButton from "@shared/ui/BaseButton.vue";
import VitrinaRepo from "@shared/entities/Vitrina/VitrinaRepo";
import { useFetchDecorator } from "@shared/composables/useFetch";
import type { IVitrina, IVitrinaItem } from "@shared/types";
import { goBack } from "@/modules/navigation";
import { track } from "@/modules/activity";
import VitrinaAvatar from "@/features/vitrina/VitrinaAvatar.vue";
import VitrinaItemCard from "@/features/vitrina/VitrinaItemCard.vue";
import VitrinaItemDrawer from "@/features/vitrina/VitrinaItemDrawer.vue";
import { VITRINA_TYPES, callPhone, openTelegram, openUrl, storageUrl } from "@/features/vitrina/vitrinaKit";

const route = useRoute();
const router = useRouter();
const { data: vitrina, execute, isFirstLoading } = useFetchDecorator<IVitrina>(VitrinaRepo.show);

const meta = computed(() => VITRINA_TYPES[vitrina.value?.type ?? "shop"] ?? VITRINA_TYPES.shop);
const cover = computed(() => storageUrl(vitrina.value?.cover));
const openedItem = ref<IVitrinaItem | null>(null);

/* ----- Bo'limlar bo'yicha guruhlash; bo'limsiz mahsulotlar — "Boshqa" ----- */
const activeGroup = ref<string>("all");

const groups = computed(() => {
   const items = vitrina.value?.items ?? [];
   const result = (vitrina.value?.sections ?? [])
      .map((section) => ({
         key: String(section.id),
         name: section.name,
         items: items.filter((item) => item.vitrina_section_id === section.id),
      }))
      .filter((group) => group.items.length);

   const sectionIds = new Set((vitrina.value?.sections ?? []).map((section) => section.id));
   const rest = items.filter((item) => !item.vitrina_section_id || !sectionIds.has(item.vitrina_section_id));
   if (rest.length) result.push({ key: "other", name: t("vitrina.otherSection"), items: rest });

   // Rasmli kartalar faqat rasmi bor bo'limda: ustaning "Narxlar" bo'limi rasmsiz — ixcham ro'yxat bo'lib chiqadi
   return result.map((group) => ({
      ...group,
      layout: meta.value.layout === "grid" && group.items.some((item) => item.images?.length) ? ("grid" as const) : ("list" as const),
   }));
});

const visibleGroups = computed(() =>
   activeGroup.value === "all" ? groups.value : groups.value.filter((group) => group.key === activeGroup.value),
);

/* ----- Ma'lumot qatorlari: manzil, ish vaqti, Instagram ----- */
type InfoRow = { key: string; icon: Component; text: string; action?: () => void };

const infoRows = computed<InfoRow[]>(() => {
   const v = vitrina.value;
   if (!v) return [];
   const rows: InfoRow[] = [];
   if (v.address) {
      rows.push({
         key: "address",
         icon: Navigation,
         text: v.address,
         action: () => openUrl(`https://maps.google.com/?q=${encodeURIComponent(v.address!)}`),
      });
   }
   if (v.work_hours) rows.push({ key: "hours", icon: Clock, text: v.work_hours });
   if (v.instagram) {
      rows.push({
         key: "instagram",
         icon: AtSign,
         text: `instagram.com/${v.instagram}`,
         action: () => openUrl(`https://instagram.com/${v.instagram}`),
      });
   }
   return rows;
});

function contactPhone() {
   if (!vitrina.value?.phone) return;
   track("contact_phone", `v${vitrina.value.id}`);
   callPhone(vitrina.value.phone);
}

function contactTelegram() {
   if (!vitrina.value?.telegram) return;
   track("contact_telegram", `v${vitrina.value.id}`);
   openTelegram(vitrina.value.telegram);
}

/* ----- Ulashish: mini app havolasi (startapp=v_<slug>) ----- */
const TELEGRAM_APP_URL = import.meta.env.VITE_TELEGRAM_APP_URL || "https://t.me/NavoiyElonBot";
const isCopied = ref(false);

async function share() {
   if (!vitrina.value) return;
   const appUrl = `${TELEGRAM_APP_URL}?startapp=v_${vitrina.value.slug}`;
   const text = `${vitrina.value.name} — ${t(`vitrina.types.${vitrina.value.type}`)}`;

   if (isTMA()) {
      postEvent("web_app_open_tg_link", {
         path_full: `/share/url?url=${encodeURIComponent(appUrl)}&text=${encodeURIComponent(text)}`,
      });
      return;
   }
   try {
      if (navigator.share) {
         await navigator.share({ title: vitrina.value.name, text, url: appUrl });
         return;
      }
      await navigator.clipboard.writeText(`${text}\n${appUrl}`);
      isCopied.value = true;
      setTimeout(() => (isCopied.value = false), 2000);
   } catch {
      // Ulashish oynasi yopildi — xato emas
   }
}

function load() {
   activeGroup.value = "all";
   return execute(String(route.params.slug));
}

watch(() => route.params.slug, (slug) => slug && load());
onMounted(load);
</script>

<style scoped>
.cover {
   position: relative;
   display: grid;
   height: calc(10rem + var(--safe-area-top));
   place-items: center;
   overflow: hidden;
   background:
      radial-gradient(circle at 20% 20%, rgb(var(--tone) / 0.35), transparent 60%),
      linear-gradient(135deg, rgb(var(--tone) / 0.25), rgb(var(--tone) / 0.08));
   color: rgb(var(--tone));
}

.cover-icon {
   width: 4.5rem;
   height: 4.5rem;
   opacity: 0.35;
}

.cover-actions {
   position: absolute;
   inset: calc(var(--safe-area-top) + 0.75rem) 0.75rem auto;
   display: flex;
   justify-content: space-between;
}

.cover-button {
   display: grid;
   width: 2.25rem;
   height: 2.25rem;
   place-items: center;
   border: 1px solid rgb(255 255 255 / 0.15);
   border-radius: 999px;
   background: rgb(0 0 0 / 0.35);
   color: white;
   backdrop-filter: blur(12px);
   cursor: pointer;
   transition: transform var(--duration) var(--ease-soft);

   &:active {
      transform: scale(0.92);
   }
}

.dot {
   width: 0.25rem;
   height: 0.25rem;
   border-radius: 999px;
   background: var(--z-muted-text);
}

.owner-bar {
   display: flex;
   align-items: center;
   gap: 0.75rem;
   border: 1px solid var(--z-border);
   border-radius: var(--z-rounded);
   background: var(--z-card);
   padding: 0.625rem 0.75rem;

   &.is-pending {
      border-color: rgb(245 158 11 / 0.5);
   }

   &.is-rejected {
      border-color: color-mix(in srgb, var(--z-danger) 60%, transparent);
   }
}

.card {
   overflow: hidden;
   border: 1px solid var(--z-glass-border);
   border-radius: var(--z-rounded);
   background: var(--z-card);
   box-shadow: var(--z-glass-edge, none);
}

.info-row {
   display: flex;
   width: 100%;
   align-items: center;
   gap: 0.75rem;
   padding: 0.75rem 1rem;
   text-align: left;
}

button.info-row:active {
   background: var(--z-muted);
}

.section-chips {
   position: sticky;
   top: 0;
   z-index: 5;
   display: flex;
   gap: 0.5rem;
   margin: 1.25rem -1rem 0;
   padding: 0.625rem 1rem;
   overflow-x: auto;
   background: var(--z-background);
   scrollbar-width: none;

   &::-webkit-scrollbar {
      display: none;
   }
}

.section-chip {
   display: inline-flex;
   height: 2.125rem;
   flex-shrink: 0;
   align-items: center;
   gap: 0.375rem;
   border: 1px solid var(--z-border);
   border-radius: 999px;
   background: var(--z-card);
   padding: 0 0.875rem;
   font-size: 0.8125rem;
   font-weight: 500;
   white-space: nowrap;
   cursor: pointer;

   &.is-active {
      border-color: var(--z-primary);
      background: var(--z-primary);
      color: var(--z-primary-foreground);
   }
}
</style>
