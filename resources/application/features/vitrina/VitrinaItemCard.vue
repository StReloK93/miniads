<template>
   <!-- grid — rasmli karta (do'kon, portfolio, uylar); list — ixcham qator (menyu, narxnoma) -->
   <button
      v-if="layout === 'grid'"
      type="button"
      class="item-grid text-left"
      :class="{ 'is-off': !item.is_available }"
      @click="emit('open', item)"
   >
      <span class="item-photo" :style="{ '--tone': tone }">
         <img v-if="cover" :src="cover" :alt="item.title" class="size-full object-cover" loading="lazy" />
         <component :is="icon" v-else class="size-8 opacity-70" stroke-width="1.5" />
         <span v-if="photoCount > 1" class="item-count">{{ photoCount }}</span>
      </span>
      <span class="block px-2.5 pt-2 pb-2.5">
         <span class="item-title line-clamp-2">{{ item.title }}</span>
         <span v-if="price" class="block mt-1 text-sm font-bold tabular-nums">{{ price }}</span>
         <span v-if="!item.is_available" class="block mt-0.5 text-xs text-(--z-muted-text)">{{ $t("vitrina.unavailable") }}</span>
      </span>
   </button>

   <button
      v-else
      type="button"
      class="item-list text-left"
      :class="{ 'is-off': !item.is_available }"
      @click="emit('open', item)"
   >
      <img v-if="cover" :src="cover" :alt="item.title" class="size-14 shrink-0 rounded-xl object-cover" loading="lazy" />
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
import type { IVitrinaItem } from "@shared/types";
import { itemPrice, storageUrl } from "@/features/vitrina/vitrinaKit";
import type { Component } from "vue";

const props = defineProps<{ item: IVitrinaItem; layout: "grid" | "list"; tone: string; icon: Component }>();
const emit = defineEmits<{ (e: "open", item: IVitrinaItem): void }>();

const cover = computed(() => storageUrl(props.item.images?.[0]));
const photoCount = computed(() => props.item.images?.length ?? 0);
const price = computed(() => itemPrice(props.item));
</script>

<style scoped>
.item-grid {
   display: block;
   overflow: hidden;
   border: 1px solid var(--z-glass-border);
   border-radius: var(--z-rounded);
   background: var(--z-card);
   box-shadow: var(--z-glass-edge, none);
   cursor: pointer;
   transition: transform var(--duration) var(--ease-soft);

   &:active {
      transform: scale(0.97);
   }
}

.item-photo {
   position: relative;
   display: grid;
   aspect-ratio: 1;
   place-items: center;
   background: rgb(var(--tone) / 0.1);
   color: rgb(var(--tone));
}

.item-count {
   position: absolute;
   right: 0.375rem;
   bottom: 0.375rem;
   min-width: 1.25rem;
   border-radius: 999px;
   background: rgb(0 0 0 / 0.5);
   padding: 0 0.375rem;
   color: white;
   font-size: 0.6875rem;
   font-weight: 600;
   line-height: 1.25rem;
   text-align: center;
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
