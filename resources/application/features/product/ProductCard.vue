<template>
   <article
      class="flex flex-col p-3 bg-(--z-card) border border-(--z-border) rounded-(--z-rounded) cursor-pointer select-none transition active:scale-[0.99] hover:shadow-sm"
      @click="$router.push({ name: 'product-id', params: { id: product.id } })"
   >
      <!-- Faol bo'lmagan e'lon ma'lumoti xiralashadi; footer'dagi tugmalar esa aniq qoladi -->
      <div :class="{ 'opacity-50': inactive }">
         <div class="flex gap-3">
            <!-- Matn: e'lonning asosiy qismi -->
            <div class="min-w-0 grow">
               <!-- Rasmsiz e'lon (ish, xizmat, ta'lim...) nima haqida ekanini kategoriya belgisi aytadi -->
               <p
                  v-if="!mainImage && product.category?.name"
                  class="mb-1.5 inline-flex items-center gap-1.5 text-xs font-medium text-(--z-muted-text)"
               >
                  <span class="size-5 rounded-full grid place-items-center" :class="categoryVisual.bgClass">
                     <component :is="categoryVisual.icon" class="size-3" stroke-width="2" />
                  </span>
                  {{ product.category.name }}
               </p>

               <h3 class="text-sm font-medium leading-snug line-clamp-2">{{ product.title }}</h3>

               <p
                  v-if="product.price"
                  class="mt-1 text-base font-bold tabular-nums inline-flex gap-1"
                  :class="{ 'flex-row-reverse justify-end': product.price_type?.position === 'left' }"
               >
                  <span>{{ formatPrice(product.price) }}</span>
                  <span v-if="product.price_type?.type">{{ product.price_type.type }}</span>
               </p>
            </div>

            <!-- Rasm bo'lsa — o'ngda kichik -->
            <div v-if="mainImage" class="relative size-16 shrink-0 rounded-lg overflow-hidden">
               <ProductImageView
                  :src="`/storage/${mainImage.src}`"
                  :crop-x="mainImage.crop_x"
                  :crop-y="mainImage.crop_y"
                  :crop-scale="mainImage.crop_scale"
                  class="size-full"
                  alt="E'lon rasmi"
               />
               <span
                  v-if="imageCount > 1"
                  class="absolute bottom-0.5 right-0.5 h-4 px-1 inline-flex items-center gap-0.5 rounded-full bg-black/55 text-white text-[9px] font-semibold tabular-nums backdrop-blur-sm"
               >
                  <Images class="size-2.5" />
                  {{ imageCount }}
               </span>
            </div>
         </div>

         <!-- Pastki qator kartaning to'liq kengligida: yurakcha har doim bir joyda turadi -->
         <div class="mt-2 flex items-center justify-between gap-2">
            <!-- Vaqt kategoriyadan oldin: joy yetmasa kategoriya qirqiladi, vaqt emas -->
            <p class="min-w-0 text-xs text-(--z-muted-text) truncate">
               {{ product.district?.name || "Barcha shaharlar" }} · {{ timeAgo(timeValue) }}
               <template v-if="showCategory && mainImage && product.category?.name">
                  · {{ product.category.name }}
               </template>
            </p>

            <button
               v-if="showFavorite"
               type="button"
               class="size-8 -my-1.5 -mr-1.5 shrink-0 grid place-items-center rounded-full cursor-pointer transition active:scale-90 hover:bg-(--z-muted) disabled:opacity-50"
               :aria-label="product.is_favorite ? 'Sevimlilardan olib tashlash' : 'Sevimlilarga qo\'shish'"
               :disabled="isFavoriteButtonLoading"
               @click.stop="toggleFavorite"
            >
               <Heart
                  class="size-4.5 transition-colors"
                  :class="product.is_favorite ? 'fill-(--z-primary) text-(--z-primary)' : 'text-(--z-muted-text)'"
               />
            </button>
         </div>
      </div>

      <!-- Kartaning to'liq kengligidagi qo'shimcha qator (profilda: qolgan kunlar va amallar) -->
      <slot name="footer" />
   </article>
</template>

<script setup lang="ts">
import { Heart, Images } from "lucide-vue-next";
import { formatPrice, timeAgo } from "@shared/modules/formatters";
import { getCategoryVisual } from "@shared/modules/categoryIcons";
import { IProduct } from "@shared/types";
import { computed, ref } from "vue";
import FavoriteRepo from "@shared/entities/Favorite/FavoriteRepo";
import { requireAuth } from "@/features/auth/requireAuth";
import ProductImageView from "@shared/ui/ProductImageView.vue";

/**
 * Ro'yxatlardagi e'lon kartasi: matn birinchi o'rinda, rasm bo'lsa o'ngda kichik.
 * Rasmsiz e'lonlar (ish, xizmat, ta'lim) bo'sh joysiz, kategoriya belgisi bilan ko'rsatiladi.
 * Slot: `footer` — karta pastiga qo'shimcha qator (profilda qolgan kunlar va amallar).
 */
const props = withDefaults(
   defineProps<{
      product: IProduct;
      showFavorite?: boolean;
      showCategory?: boolean;
      inactive?: boolean;
      timeField?: "published_at" | "created_at";
   }>(),
   {
      showFavorite: true,
      showCategory: true,
      inactive: false,
      timeField: "created_at",
   },
);

const mainImage = computed(() => props.product.images?.[0] ?? null);
const imageCount = computed(() => props.product.images?.length ?? 0);

const timeValue = computed(() => {
   if (props.timeField === "published_at") {
      return props.product.published_at || props.product.created_at;
   }
   return props.product.created_at || props.product.published_at;
});

const categoryVisual = computed(() => getCategoryVisual(props.product.category?.name));

const isFavoriteButtonLoading = ref(false);

async function toggleFavorite() {
   if (!requireAuth()) return;
   isFavoriteButtonLoading.value = true;
   try {
      if (props.product.is_favorite) {
         await FavoriteRepo.delete(props.product.id);
      } else {
         await FavoriteRepo.store(props.product.id);
      }
      props.product.is_favorite = !props.product.is_favorite;
   } finally {
      isFavoriteButtonLoading.value = false;
   }
}
</script>
