<template>
   <!-- Qidiruvda: e'lonlar bilan birga vitrinalardagi mos mahsulotlar ("divan" → do'konlardagi divanlar) -->
   <section v-if="items.length" class="mb-4" @mousedown.prevent>
      <h4 class="mb-2 flex items-center gap-1.5 text-xs font-semibold text-(--z-muted-text)">
         <Store class="size-3.5" />
         {{ $t("vitrina.search.title") }}
      </h4>
      <div class="-mx-4 px-4 flex gap-2.5 overflow-x-auto no-scrollbar snap-x">
         <button
            v-for="item in items"
            :key="item.id"
            type="button"
            class="item snap-start"
            @click="open(item)"
         >
            <span class="relative block aspect-square w-full overflow-hidden rounded-xl bg-(--z-muted)">
               <ProductImageView
                  v-if="item.images?.length"
                  :src="storageUrl(item.images[0].src)!"
                  :crop-x="item.images[0].crop_x"
                  :crop-y="item.images[0].crop_y"
                  :crop-scale="item.images[0].crop_scale"
                  :alt="item.title"
                  class="size-full"
               />
               <span v-else class="size-full grid place-items-center text-(--z-muted-text)">
                  <component :is="typeIcon(item)" class="size-6" />
               </span>
            </span>
            <span class="mt-1.5 block text-xs font-semibold leading-snug line-clamp-2 text-left">{{ item.title }}</span>
            <span v-if="itemPrice(item)" class="block text-xs font-bold tabular-nums text-left">{{ itemPrice(item) }}</span>
            <span class="mt-0.5 flex items-center gap-1 text-[11px] text-(--z-muted-text)">
               <span class="truncate">{{ item.vitrina?.name }}</span>
               <VitrinaVerified v-if="item.vitrina?.is_verified" class="size-3.5" />
            </span>
         </button>
      </div>
   </section>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import { useRouter } from "vue-router";
import { Store } from "lucide-vue-next";
import VitrinaVerified from "@/features/vitrina/VitrinaVerified.vue";
import ProductImageView from "@shared/ui/ProductImageView.vue";
import VitrinaRepo from "@shared/entities/Vitrina/VitrinaRepo";
import type { IVitrinaItem } from "@shared/types";
import { VITRINA_TYPES, itemPrice, storageUrl } from "@/features/vitrina/vitrinaKit";

const props = defineProps<{
   query: string;
   /** Qidiruv filtridagi shahar: shu shahar va butun viloyat vitrinalari */
   cityId?: number | null;
}>();

const router = useRouter();
const items = ref<IVitrinaItem[]>([]);
let requestId = 0;

watch(
   () => [props.query.trim(), props.cityId] as const,
   async ([query, cityId]) => {
      const current = ++requestId;
      if (query.length < 2) {
         items.value = [];
         return;
      }
      try {
         const { data } = await VitrinaRepo.searchItems(query, cityId);
         // Eski so'rov javobi kech kelsa — yangisini bosib ketmasin
         if (current === requestId) items.value = data;
      } catch {
         if (current === requestId) items.value = [];
      }
   },
   { immediate: true },
);

function typeIcon(item: IVitrinaItem) {
   return VITRINA_TYPES[item.vitrina?.type ?? "shop"]?.icon ?? Store;
}

function open(item: IVitrinaItem) {
   if (!item.vitrina) return;
   router.push({ name: "vitrina", params: { slug: item.vitrina.slug }, query: { item: String(item.id) } });
}
</script>

<style scoped>
.item {
   display: flex;
   flex-direction: column;
   flex: 0 0 8.5rem;
   width: 8.5rem;
   padding: 0.375rem;
   border: 1px solid var(--z-border);
   border-radius: var(--z-rounded);
   background: var(--z-card);
   cursor: pointer;

   &:active {
      transform: scale(0.98);
   }
}
</style>
