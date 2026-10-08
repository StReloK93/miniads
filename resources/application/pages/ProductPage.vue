<template>
   <section class="full-page flex flex-col">
      <aside v-if="product" class="grow relative -mt-[calc(var(--safe-area-top)+var(--spacing)*4)]">
         <article class="absolute inset-0 overflow-y-auto no-scrollbar">
            <main class="relative">
               <swiper
                  v-if="product?.images.length"
                  :modules="[Pagination]"
                  :pagination="product.images.length > 1"
                  class="aspect-video w-full"
               >
                  <swiper-slide v-for="(image, index) in product.images" :key="image.id">
                     <button
                        type="button"
                        class="h-full w-full cursor-zoom-in"
                        aria-label="To'liq rasmni ko'rish"
                        @click="openImagePreview(index)"
                     >
                        <ProductImageView
                           :src="`/storage/${image.src}`"
                           :crop-x="image.crop_x"
                           :crop-y="image.crop_y"
                           :crop-scale="image.crop_scale"
                           class="h-full w-full"
                           alt="E'lon rasmi"
                        />
                     </button>
                  </swiper-slide>
               </swiper>
               <div
                  v-else
                  :style="{
                     backgroundImage: product.back_color.gradient,
                  }"
                  class="aspect-video w-full flex justify-center items-center"
               >
                  <span class="text-white text-2xl font-semibold text-center px-5">
                     {{ product?.title }}
                  </span>
               </div>
               <!-- <img

                  class="h-64 w-full bg-(--z-border) rounded-tl-[10px] rounded-tr-[10px]"
                  :src="'/images/no-image.webp'"
                  alt="No Image"
               /> -->
            </main>
            <main class="pt-5 px-4">
               <!--  -->

               <div
                  v-if="product?.price"
                  class="inline-flex items-center gap-1 font-extrabold text-2xl mb-3"
                  :class="{ 'flex-row-reverse': product?.price_type.position === 'left' }"
               >
                  <span>
                     {{ formatPrice(product?.price) }}
                  </span>
                  <span>
                     {{ product?.price_type.type }}
                  </span>
               </div>

               <!--  -->

               <h1 class="mb-4 text-xl font-medium">{{ product?.title }}</h1>

               <!--  -->
               <div class="text-(--z-muted-text) text-xs flex gap-2 items-center mb-6">
                  <span class="capitalize">
                     {{ timeAgo(product?.created_at!) }}
                  </span>
                  <span class="inline-block w-1 h-1 rounded-full bg-(--z-muted-text)"> </span>
                  <span class="flex items-center gap-1"> {{ viewCount }} ko'rildi </span>
                  <span class="inline-block w-1 h-1 rounded-full bg-(--z-muted-text)"> </span>

                  <span class="flex items-center gap-1">
                     <MapPin class="size-3 inline" />
                     {{ product?.district?.name || "Navoiy V." }}
                  </span>
               </div>
               <!--  -->

               <!--  -->
               <div v-if="product?.description">
                  <h3 class="title text-sm">Izoh</h3>
                  <div class="py-1 leading-5 text-sm mb-6">{{ product?.description }}</div>
               </div>

               <h3 v-if="product?.parameter_values.length" class="title text-sm mb-2">Qo'shimcha ma'lumot</h3>
               <aside v-if="product?.parameter_values.length" class="text-sm divide-y divide-(--z-border)">
                  <div v-for="v in product.parameter_values" :key="v.id" class="flex justify-between py-2">
                     <span class="text-(--z-muted-text)">
                        {{ v.parameter.title }}
                     </span>
                     <main class="flex gap-1.5 font-medium">
                        <span>
                           {{ v.value }}
                        </span>
                        <span v-if="v.parameter.unit">
                           {{ v.parameter.unit }}
                        </span>
                     </main>
                  </div>
               </aside>

               <hr class="border-(--z-border)" />

               <div class="flex items-center gap-3 my-3">
                  <div
                     class="w-10 h-10 rounded-full bg-(--z-border) flex items-center justify-center text-sm font-bold text-(--z-muted-text)"
                  >
                     {{ product.user.name[0] }}
                  </div>
                  <main class="leading-4">
                     <h3 class="font-medium">{{ product.user.name }}</h3>
                     <span class="text-(--z-muted-text) text-xs">@{{ product.user.username }}</span>
                  </main>
               </div>
            </main>
         </article>
      </aside>
      <aside v-else class="grow">
         <main class="relative -mt-[calc(var(--safe-area-top)+var(--spacing)*4)]">
            <div class="skeleton aspect-video rounded-none!"></div>
         </main>
         <main class="py-5.5 px-4">
            <div class="skeleton h-5.5 mb-6 w-24"></div>
            <div class="skeleton h-4.5 w-full mb-5.5"></div>
            <div class="skeleton h-4 w-2/3 mb-7"></div>
            <div class="skeleton h-3 w-12 mb-3"></div>
            <div class="skeleton h-2 w-4/5 mb-3"></div>
            <div class="skeleton h-2 w-full mb-3"></div>
            <div class="skeleton h-2 w-2/3"></div>
         </main>
      </aside>
      <aside v-if="product" class="px-4 pt-4 border-t border-(--z-border) flex gap-4">
         <BaseButton
            v-if="product.user.username"
            severity="secondary"
            @click="openSellerChat(product)"
            iconOnly
            class="aspect-square"
         >
            <template #icon>
               <MessageCircle class="size-5 inline" />
            </template>
         </BaseButton>
         <BaseButton @click="callPhone(product?.phone!)" severity="primary" class="grow">
            <template #icon>
               <Phone class="size-4 inline" />
            </template>
            Qo'ng'iroq qilish
         </BaseButton>

         <BaseButton
            severity="secondary"
            @click="toggleFavorite"
            iconOnly
            class="aspect-square"
            :loading="isFavoriteButtonLoading"
         >
            <template #icon>
               <Heart class="size-5 inline" :class="product.is_favorite ? 'fill-red-500 text-red-500' : 'text-(--z-foreground)'" />
            </template>
         </BaseButton>
      </aside>
      <aside v-else class="px-4 pt-4 border-t border-(--z-border) flex gap-4">
         <div class="skeleton h-12 grow"></div>
         <div class="skeleton size-12"></div>
      </aside>

      <div
         v-if="previewIndex !== null && product?.images.length"
         class="fixed inset-0 z-500 flex items-center justify-center overflow-hidden bg-black/85 select-none"
         @click="onPreviewBackdropClick"
      >
         <swiper
            :key="previewIndex"
            :modules="[Pagination]"
            :initial-slide="previewIndex"
            :pagination="previewPaginationConfig"
            class="absolute inset-0 h-full w-full"
            @click="onPreviewBackdropClick"
         >
            <swiper-slide
               v-for="image in product.images"
               :key="`preview-${image.id}`"
               class="flex! h-full items-center justify-center cursor-pointer"
            >
               <img
                  :src="`/storage/${image.src}`"
                  class="max-h-full w-full object-contain pointer-events-auto cursor-default"
                  alt="E'lonning original rasmi"
                  @click.stop
               />
            </swiper-slide>
         </swiper>

         <!-- Mobile-first bottom bar: perfectly aligned items-center row with safe margins -->
         <div class="absolute inset-x-0 bottom-0 z-20 flex items-center justify-between px-6 pb-[calc(env(safe-area-inset-bottom,0px)+1.5rem)] pt-3 pointer-events-none">
            <!-- Left: Dot indicators wrapped in a sleek glass pill (never stuck to corner) -->
            <div
               v-show="product.images.length > 1"
               class="preview-pagination-dots pointer-events-auto flex items-center gap-1.5 bg-black/60 border border-white/20 backdrop-blur-md px-3.5 py-1.5 rounded-full shadow-2xl min-h-[36px]"
            ></div>
            <div v-if="product.images.length <= 1"></div>

            <!-- Right: Close button on the exact same line/height as the dots -->
            <BaseButton
               severity="secondary"
               rounded
               class="pointer-events-auto shadow-2xl bg-black/70 hover:bg-black/90 text-white border border-white/25 backdrop-blur-md px-4 py-2 flex items-center gap-1.5 active:scale-95 transition-transform text-sm h-[36px]"
               aria-label="Rasmni yopish"
               @click="closeImagePreview"
            >
               <template #icon>
                  <X class="size-4" />
               </template>
               Yopish
            </BaseButton>
         </div>
      </div>
   </section>
</template>

<script setup lang="ts">
import { formatPrice, timeAgo } from "@shared/modules/formatters";
import { isTMA } from "@tma.js/bridge";
import ProductRepo from "@shared/entities/Product/ProductRepo";
import ProductImageView from "@shared/ui/ProductImageView.vue";
import BaseButton from "@shared/ui/BaseButton.vue";
import { Pagination } from "swiper/modules";
import { Swiper, SwiperSlide } from "swiper/vue";
import { useRoute } from "vue-router";
import { useFetchDecorator } from "@shared/composables/useFetch";
import { computed, onMounted, ref } from "vue";
import { IProduct } from "@shared/types";
import { preloadImages } from "@/modules/Helpers";
import { Heart, MapPin, MessageCircle, Phone, X } from "lucide-vue-next";
import FavoriteRepo from "@shared/entities/Favorite/FavoriteRepo";
import { postEvent } from "@tma.js/bridge";

const route = useRoute();

const { data: product, execute: executeProduct } = useFetchDecorator<IProduct>(ProductRepo.show);

const isImagesReady = ref(false);
const previewIndex = ref<number | null>(null);

const previewPaginationConfig = computed(() => {
   if (!product.value?.images || product.value.images.length <= 1) return false;
   return {
      el: ".preview-pagination-dots",
      clickable: true,
   };
});

function openImagePreview(index: number) {
   previewIndex.value = index;
}

function closeImagePreview() {
   previewIndex.value = null;
}

function onPreviewBackdropClick(event: MouseEvent) {
   const target = event.target as HTMLElement | null;
   if (!target) return;
   // Don't close if user clicked the image itself
   if (target.tagName === "IMG") return;
   // Don't close if user clicked swiper pagination dots or close button
   if (target.closest(".swiper-pagination") || target.closest("button")) return;
   closeImagePreview();
}

function callPhone(phone: string) {
   if (isTMA()) {
      window.open(`tel:${phone}`);
   }
}

function openSellerChat(product: IProduct) {
   const username = product.user.username?.trim().replace(/^@/, "");

   if (!username) return;

   const text = `Assalomu alaykum, "${product.title}" e'loni bo'yicha yozyabman.`;

   const pathFull = `${username}?text=${encodeURIComponent(text)}`;

   postEvent("web_app_open_tg_link", {
      path_full: pathFull,
   });
}

const viewCount = computed(() => {
   const count = product.value?.views_count ?? 0;

   if (count >= 1_000_000) {
      const value = Math.floor(count / 100_000) / 10; // 1.2M
      return `${Number.isInteger(value) ? value.toFixed(0) : value}M`;
   }

   if (count >= 1_000) {
      const value = Math.floor(count / 100) / 10; // 1.5K
      return `${Number.isInteger(value) ? value.toFixed(0) : value}K`;
   }

   return count;
});

const isFavoriteButtonLoading = ref(false);

async function toggleFavorite() {
   if (!product.value) return;
   isFavoriteButtonLoading.value = true;

   if (product.value.is_favorite) {
      await FavoriteRepo.delete(product.value.id).finally(() => {
         isFavoriteButtonLoading.value = false;
      });
   } else {
      await FavoriteRepo.store(product.value.id).finally(() => {
         isFavoriteButtonLoading.value = false;
      });
   }
   product.value.is_favorite = !product.value.is_favorite;
}

onMounted(async () => {
   await executeProduct(route.params.id);
   if (product.value?.images?.length) {
      const imageUrls = product.value.images.flatMap((img) => [
         `/storage/${img.src}`,
      ]);
      await preloadImages(imageUrls);
   }

   setTimeout(() => {
      isImagesReady.value = true;
   }, 350);
});
</script>

<style scoped>
:deep(.preview-pagination-dots) {
   display: inline-flex !important;
   align-items: center !important;
   gap: 6px !important;
}

:deep(.preview-pagination-dots .swiper-pagination-bullet) {
   background: rgba(255, 255, 255, 0.45) !important;
   margin: 0 !important;
   width: 6px !important;
   height: 6px !important;
   border-radius: 9999px !important;
   cursor: pointer !important;
   transition: all 0.25s ease !important;
   display: inline-block !important;
   opacity: 1 !important;
}

:deep(.preview-pagination-dots .swiper-pagination-bullet-active) {
   background: #ffffff !important;
   width: 18px !important;
   border-radius: 9999px !important;
}
</style>
