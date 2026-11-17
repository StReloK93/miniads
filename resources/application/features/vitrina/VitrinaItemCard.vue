<template>
   <!--
      grid — Pinterest uslubi: rasm butun kartani egallaydi (4:5, egasi tanlagan crop bilan),
      pastida rasm ustida suzuvchi shisha panel — nomi va narxi. Rasm yo'q bo'lsa — naqshli fon, panel o'sha joyda.
      list — ixcham qator (menyu, narxnoma).
   -->
   <button
      v-if="layout === 'grid'"
      type="button"
      class="item-grid"
      :class="{ 'is-off': !item.is_available, 'is-empty tone-dots': !cover }"
      :style="{ '--tone': tone }"
      :aria-label="item.title"
      @click="emit('open', item)"
   >
      <ProductImageView
         v-if="cover"
         :src="storageUrl(cover.src)!"
         :crop-x="cover.crop_x"
         :crop-y="cover.crop_y"
         :crop-scale="cover.crop_scale"
         :alt="item.title"
         class="absolute! inset-0 size-full"
      />
      <component :is="icon" v-else class="item-icon" stroke-width="1.5" />

      <span v-if="photoCount > 1" class="item-badge right-2">
         <Images class="size-3" />
         {{ photoCount }}
      </span>
      <span v-if="!item.is_available" class="item-badge left-2">{{ $t("vitrina.unavailable") }}</span>

      <span class="item-panel z-glass-strong">
         <span class="item-name">{{ item.title }}</span>
         <span v-if="pricePill" class="item-price">
            <span class="whitespace-nowrap">{{ pricePill }}</span>
            <span v-if="item.unit" class="item-unit">/ {{ item.unit.toLowerCase() }}</span>
         </span>
      </span>
   </button>

   <!-- Ro'yxat — sodda: rasmcha faqat rasm bo'lsa, nomi va narxi bir qatorda -->
   <button
      v-else
      type="button"
      class="item-list text-left"
      :class="{ 'is-off': !item.is_available }"
      @click="emit('open', item)"
   >
      <ProductImageView
         v-if="cover"
         :src="storageUrl(cover.src)!"
         :crop-x="cover.crop_x"
         :crop-y="cover.crop_y"
         :crop-scale="cover.crop_scale"
         :alt="item.title"
         class="size-14 shrink-0 rounded-xl"
      />
      <span class="min-w-0 grow">
         <span class="item-title line-clamp-2">{{ item.title }}</span>
         <span v-if="item.description" class="block mt-0.5 text-xs text-(--z-muted-text) truncate">{{ item.description }}</span>
         <span v-if="!item.is_available" class="block mt-0.5 text-xs text-(--z-muted-text)">{{ $t("vitrina.unavailable") }}</span>
      </span>
      <span v-if="price" class="shrink-0 text-sm font-bold tabular-nums text-right max-w-[45%]">{{ price }}</span>
   </button>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { Component } from "vue";
import { Images } from "lucide-vue-next";
import ProductImageView from "@shared/ui/ProductImageView.vue";
import type { IVitrinaItem } from "@shared/types";
import { itemPrice, storageUrl } from "@/features/vitrina/vitrinaKit";

const props = defineProps<{ item: IVitrinaItem; layout: "grid" | "list"; tone: string; icon: Component }>();
const emit = defineEmits<{ (e: "open", item: IVitrinaItem): void }>();

const cover = computed(() => props.item.images?.[0] ?? null);
const photoCount = computed(() => props.item.images?.length ?? 0);
/** Kartadagi narx — birliksiz ("6 500 000 so'mdan"); birligi yonida kichik kulrang harfda */
const pricePill = computed(() => itemPrice(props.item, { withUnit: false }));
/** Ro'yxatda — birligi bilan bir qatorda ("45 000 so'm / porsiya") */
const price = computed(() => itemPrice(props.item));
</script>

<style scoped>
.item-grid {
   position: relative;
   display: block;
   aspect-ratio: 4 / 5;
   min-width: 0;
   overflow: hidden;
   border: 1px solid var(--z-glass-border);
   border-radius: var(--z-rounded);
   background: var(--z-muted);
   box-shadow: var(--z-glass-edge, none);
   text-align: left;
   cursor: pointer;
   transition: transform var(--duration) var(--ease-soft);

   &:active {
      transform: scale(0.97);
   }

   /* Rasm yo'q: juda yengil rang va nuqtali naqsh (muqova bilan bir xil) */
   &.is-empty {
      background: rgb(var(--tone) / 0.08);
      color: rgb(var(--tone) / 0.7);
   }
}

/* Rasmsiz kartada belgi panel ustidagi bo'sh joy o'rtasida */
.item-icon {
   position: absolute;
   top: 34%;
   left: 50%;
   width: 1.75rem;
   height: 1.75rem;
   transform: translate(-50%, -50%);
}

/* Rasm ustida suzuvchi shisha panel: rasm qanday bo'lmasin matn o'qiladi */
.item-panel {
   position: absolute;
   right: 0.375rem;
   bottom: 0.375rem;
   left: 0.375rem;
   display: flex;
   flex-direction: column;
   gap: 0.125rem;
   border-radius: calc(var(--z-rounded) - 0.375rem);
   padding: 0.4375rem 0.5625rem 0.5rem;
   box-shadow: 0 6px 18px -8px rgb(15 23 42 / 0.35);
}

.item-name {
   overflow: hidden;
   color: var(--z-foreground);
   font-size: 0.75rem;
   font-weight: 600;
   line-height: 1.25;
   text-overflow: ellipsis;
   white-space: nowrap;
}

/* Narx bo'linmaydi; sig'masa birligi keyingi qatorga tushadi */
.item-price {
   display: flex;
   flex-wrap: wrap;
   align-items: baseline;
   column-gap: 0.25rem;
   color: var(--z-foreground);
   font-size: 0.875rem;
   font-weight: 800;
   line-height: 1.2;
   letter-spacing: -0.01em;
   font-variant-numeric: tabular-nums;
}

.item-unit {
   white-space: nowrap;
   color: var(--z-muted-text);
   font-size: 0.6875rem;
   font-weight: 500;
   letter-spacing: 0;
}

/* Rasm soni (o'ngda) va "Sotuvda yo'q" (chapda) — tepada */
.item-badge {
   position: absolute;
   top: 0.5rem;
   display: inline-flex;
   height: 1.25rem;
   align-items: center;
   gap: 0.25rem;
   border-radius: 999px;
   background: rgb(0 0 0 / 0.45);
   padding: 0 0.4375rem;
   color: white;
   font-size: 0.625rem;
   font-weight: 600;
   backdrop-filter: blur(12px);
}

.item-list {
   display: flex;
   width: 100%;
   align-items: center;
   gap: 0.75rem;
   padding: 0.75rem 0;
   cursor: pointer;

   &:active {
      opacity: 0.7;
   }
}

.item-title {
   display: -webkit-box;
   -webkit-box-orient: vertical;
   overflow: hidden;
   font-size: 0.875rem;
   font-weight: 500;
   line-height: 1.3;
}

.is-off {
   opacity: 0.55;
}
</style>
