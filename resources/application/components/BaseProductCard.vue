<template>
   <main @click="$router.push({ name: 'product-id', params: { id: product.id } })" class="cursor-pointer">
      <section class="bg-(--z-card) p-1.5 rounded-(--z-rounded) select-none border border-(--z-border) transition-shadow hover:shadow-sm">
         <div class="mb-2.5 relative rounded-[10px] overflow-hidden">
            <div v-if="inactive" class="absolute inset-0 bg-black/50 z-10 pointer-events-none"></div>

            <ProductImageView
               v-if="props.product.images && props.product.images.length > 0"
               :src="`/storage/${product.images[0].src}`"
               :crop-x="product.images[0].crop_x"
               :crop-y="product.images[0].crop_y"
               :crop-scale="product.images[0].crop_scale"
               class="w-full aspect-video"
               alt="Rasm"
            />
            <div
               v-else
               :style="{
                  backgroundImage: product.back_color?.gradient,
               }"
               class="w-full object-cover aspect-video flex items-center"
            >
               <h3 class="font-bold text-2xl text-white px-4 pt-5 text-center w-full">
                  {{ product.title }}
               </h3>
            </div>

            <!-- Narx belgisi -->
            <div
               v-if="product.price"
               class="absolute top-2 left-2 text-sm inline-flex items-center gap-1 px-2 py-0.5 z-bg-gradient backdrop-blur-sm border rounded-full border-(--z-border) z-10"
               :class="{ 'flex-row-reverse': product.price_type?.position === 'left' }"
            >
               <span class="font-semibold">
                  {{ formatPrice(product?.price) }}
               </span>
               <span v-if="product.price_type?.type">
                  {{ product.price_type.type }}
               </span>
            </div>

            <!-- Yuqori o'ng burchak (Slot yoki Sevimlilar tugmasi) -->
            <slot name="top-right">
               <BaseButton
                  v-if="showFavorite"
                  @click.stop="toggleFavorite"
                  class="absolute top-2 right-2 border border-(--z-border) z-10"
                  iconOnly
                  rounded
                  :loading="isFavoriteButtonLoading"
                  severity="glass"
               >
                  <template #icon>
                     <Heart class="size-4" :class="product.is_favorite ? 'fill-white text-white' : 'text-white'" />
                  </template>
               </BaseButton>
            </slot>

            <!-- Pastki o'ng burchak amallari -->
            <slot name="actions" />
         </div>

         <main class="px-1.5">
            <h3 v-if="props.product.images && props.product.images.length > 0" class="font-medium line-clamp-1">
               {{ product.title }}
            </h3>
            <aside class="text-xs my-1">
               <div class="text-(--z-muted-text) inline-flex items-center gap-1 flex-wrap">
                  <span class="font-semibold">
                     {{ product.district?.name || "Barcha shaharlar" }}
                  </span>
                  <template v-if="showCategory && product.category?.name">
                     <span class="inline-flex w-1 h-1 rounded-full bg-(--z-primary)"></span>
                     <span>
                        {{ product.category.name }}
                     </span>
                  </template>

                  <span class="inline-flex w-1 h-1 rounded-full bg-(--z-primary)"></span>
                  <span>
                     {{ timeAgo(timeValue) }}
                  </span>
               </div>
            </aside>
         </main>
      </section>
   </main>
</template>

<script setup lang="ts">
import { Heart } from "lucide-vue-next";
import { formatPrice, timeAgo } from "@shared/modules/formatters";
import { IProduct } from "@shared/types";
import { computed, ref } from "vue";
import FavoriteRepo from "@shared/entities/Favorite/FavoriteRepo";
import ProductImageView from "@shared/ui/ProductImageView.vue";
import BaseButton from "@shared/ui/BaseButton.vue";

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
   }
);

const timeValue = computed(() => {
   if (props.timeField === "published_at") {
      return props.product.published_at || props.product.created_at;
   }
   return props.product.created_at || props.product.published_at;
});

const isFavoriteButtonLoading = ref(false);

async function toggleFavorite() {
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
