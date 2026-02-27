<template>
   <NavigationPageDecorator>
      <template #header>
         <h3 class="font-bold text-xl">Profil</h3>
         <aside class="flex flex-col items-center pb-6 py-4">
            <div class="mb-2">
               <img
                  v-if="user.photo_url && isImagesReady"
                  :src="user.photo_url"
                  class="rounded-full w-18 h-18 object-fill"
               />
               <span v-else-if="user.photo_url && !isImagesReady" class="rounded-full! w-18 h-18 skeleton inline-block">
               </span>
               <span v-else class="rounded-full w-18 h-18 bg-slate-100 inline-flex justify-center items-center">
                  <User class="size-5" />
               </span>
            </div>
            <!-- {{ userData.tgWebAppData.user.photo_url }} -->
            <h3 class="font-semibold">{{ user?.first_name }} {{ user?.last_name }}</h3>
            <div class="text-(--z-muted-text) mb-6">@{{ user?.username }}</div>

            <main class="flex w-full divide-x divide-(--z-border)">
               <div class="text-center w-1/3">
                  <span class="font-semibold">45</span>
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
               <BaseButton size="sm" rounded class="w-full"> E'lonlar </BaseButton>
               <BaseButton size="sm" rounded class="w-full" severity="secondary"> Do'kon </BaseButton>
            </div>
         </aside>
      </template>
      <template #content>
         <BaseTabs :items="['Faol', `O'chiq`, 'Tekshiruvda']" @change="onTabChange" class="w-full" />
      </template>
   </NavigationPageDecorator>
</template>

<script setup lang="ts">
import { preloadImages } from "@/modules/Helpers";
import { computed, inject, onMounted, ref } from "vue";
import NavigationPageDecorator from "@/components/NavigationPageDecorator.vue";
import { isTMA } from "@tma.js/bridge";
import { User } from "lucide-vue-next";
const isImagesReady = ref(false);
const userData: any = inject("userData");

function onTabChange({ index, value }) {
   console.log(index, value); // 0, 1, 2
}

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
