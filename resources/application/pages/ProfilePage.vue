<template>
   <NavigationPageDecorator>
      <template #header="{ isCompact }">
         <h3 class="font-bold text-xl">Profil</h3>
         <aside
            class="flex flex-col items-center overflow-hidden transition-all duration-300"
            :class="[isCompact ? 'max-h-0 py-0 opacity-0' : 'max-h-200 py-4']"
         >
            <div class="mb-2 flex">
               <img
                  v-if="user.photo_url && isImagesReady"
                  :src="user.photo_url"
                  class="rounded-full w-18 h-18 object-fill"
               />
               <span v-else-if="user.photo_url && !isImagesReady" class="rounded-full! w-18 h-18 skeleton inline-block">
               </span>
               <span v-else class="rounded-full w-18 h-18 bg-slate-100 inline-flex justify-center items-center">
                  <Camera class="size-5" stroke-width="2" stroke="currentColor" />
               </span>
            </div>
            <!-- {{ userData.tgWebAppData.user.photo_url }} -->
            <h3 class="font-semibold">{{ user?.first_name }} {{ user?.last_name }}</h3>
            <div class="text-(--z-muted-text) mb-6">@{{ user?.username }}</div>

            <main class="flex w-full divide-x divide-(--z-border)">
               <div class="text-center w-1/3">
                  <span class="font-semibold">{{ products?.length }}</span>
                  <h3 class="text-(--z-muted-text) text-sm">E'lon</h3>
               </div>
               <div class="text-center w-1/3">
                  <span class="font-semibold">12</span>
                  <h3 class="text-(--z-muted-text) text-sm">Kuzatuvchi</h3>
               </div>
               <div class="text-center w-1/3">
                  <span class="font-semibold">4.5</span>
                  <h3 class="text-(--z-muted-text) text-sm">Baho</h3>
               </div>
            </main>

            <div class="flex gap-2 w-full mt-8">
               <h3 class="text-center text-2xl font-bold w-full">E'lonlar</h3>
               <!-- <BaseButton size="sm" rounded class="w-full" variant="text"> E'lonlar </BaseButton> -->
               <!-- <BaseButton size="sm" rounded class="w-full" severity="secondary"> Do'kon </BaseButton> -->
            </div>
         </aside>
         <BaseTabs :items="['Faol', `O'chiq`]" @change="onTabChange" class="w-full" />
      </template>
      <template #content>
         <div class="flex flex-col gap-4">
            <BaseProductCard v-for="product in products" :product="product" />
         </div>
      </template>
   </NavigationPageDecorator>
</template>

<script setup lang="ts">
import BaseProductCard from "@/components/BaseProductCard.vue";
import ProductRepo from "@shared/entities/Product/ProductRepo";
import { preloadImages } from "@/modules/Helpers";
import { computed, inject, onMounted, ref } from "vue";
import NavigationPageDecorator from "@/components/NavigationPageDecorator.vue";
import { isTMA } from "@tma.js/bridge";
import { Camera } from "lucide-vue-next";
import { useFetchDecorator } from "@shared/composables/useFetch";
import { IProduct } from "@shared/types";
const isImagesReady = ref(false);
const userData: any = inject("userData");

function onTabChange({ index, value }) {
   console.log(index, value); // 0, 1, 2
}

const { data: products, execute: fetchProducts } = useFetchDecorator<IProduct[]>(ProductRepo.myAds);

const user = computed(() => {
   if (isTMA()) {
      return userData?.tgWebAppData.user;
   } else {
      return {
         photo_url: null,
         first_name: "Bumin",
         last_name: "Xoqon",
         username: "Ruzzifer",
      };
   }
});

onMounted(async () => {
   await fetchProducts();

   const images = <string[]>[];
   if (user.value.photo_url) {
      images.push(user.value.photo_url);
   }
   await preloadImages(images);

   setTimeout(() => {
      isImagesReady.value = true;
   }, 1000);
});
</script>
