<template>
   <section class="full-page flex flex-col">
      <aside v-if="product" class="grow relative -mt-[calc(var(--safe-area-top)+var(--spacing)*4)]">
         <article class="absolute inset-0 overflow-y-auto no-scrollbar">
            <main class="relative">
               <swiper v-if="product?.images.length" :modules="[Pagination]" pagination class="h-64">
                  <swiper-slide v-for="image in product?.images" :key="image.id">
                     <img :src="`/storage/${image.src}`" class="h-full w-full object-cover" />
                  </swiper-slide>
               </swiper>
               <img
                  v-else
                  class="h-64 w-full bg-(--z-border) rounded-tl-[10px] rounded-tr-[10px]"
                  :src="'/images/no-image.webp'"
                  alt="No Image"
               />
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
                  <span class="flex items-center gap-1"> 1.2k ko'rildi </span>
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
               <aside v-if="product?.parameter_values.length" class="text-sm divide-y divide-gray-50">
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
            <div class="skeleton h-64 rounded-none!"></div>
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
               <Heart class="size-5 inline" :class="{ 'fill-(--z-primary)': product.is_favorite }" />
            </template>
         </BaseButton>
      </aside>
      <aside v-else class="px-4 pt-4 border-t border-(--z-border) flex gap-4">
         <div class="skeleton h-12 grow"></div>
         <div class="skeleton size-12"></div>
      </aside>
   </section>
</template>

<script setup lang="ts">
import { timeAgo } from "@/modules/Helpers";
import { isTMA } from "@tma.js/bridge";
import { formatPrice } from "@/modules/Helpers";
import ProductRepo from "@shared/entities/Product/ProductRepo";
import { Pagination } from "swiper/modules";
import { Swiper, SwiperSlide } from "swiper/vue";
import { useRoute } from "vue-router";
import { useFetchDecorator } from "@shared/composables/useFetch";
import { onMounted, ref } from "vue";
import { IProduct } from "@shared/types";
import { preloadImages } from "@/modules/Helpers";
import { Heart, MapPin, MessageCircle, Phone } from "lucide-vue-next";
import FavoriteRepo from "@shared/entities/Favotire/FavoriteRepo";
import { postEvent } from "@tma.js/bridge";

const route = useRoute();

const { data: product, execute: executeProduct } = useFetchDecorator<IProduct>(ProductRepo.show);

const isImagesReady = ref(false);

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
      const imageUrls = product.value.images.map((img) => `/storage/${img.src}`);
      await preloadImages(imageUrls);
   }

   setTimeout(() => {
      isImagesReady.value = true;
   }, 350);
});
</script>
