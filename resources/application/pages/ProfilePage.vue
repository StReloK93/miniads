<template>
   <NavigationPageDecorator :content-class="['bg-(--z-bg-secondary)']">
      <template #header>
         <aside class="flex flex-col items-center py-6">
            <div class="mb-2">
               <img v-if="user.photo_url" :src="user.photo_url" class="rounded-full w-18 h-18 object-fill" />
               <span v-else class="rounded-full w-18 h-18 bg-slate-100 inline-flex justify-center items-center">
                  <User class="size-5" />
               </span>
            </div>
            <!-- {{ userData.tgWebAppData.user.photo_url }} -->
            <h3 class="font-semibold">{{ user?.first_name }} {{ user?.last_name }}</h3>
            <div class="text-(--z-color-text-secondary)">
               {{ user?.username }}
            </div>
         </aside>
      </template>
      <template #content>
         <aside class="py-3 flex flex-col gap-1">
            <div class="bg-(--z-bg-primary) rounded-(--z-rounded) p-4 flex justify-between items-center">
               <span class="inline-flex gap-2 items-center">
                  <List class="size-4 inline text-(--z-color-primary)" />
                  <span> Mening e'lonlarim </span>
               </span>
               <span>
                  <ChevronRight class="size-4" />
               </span>
            </div>
            <RouterLink
               :to="{ name: 'favorites' }"
               class="bg-(--z-bg-primary) rounded-(--z-rounded) p-4 flex justify-between items-center"
            >
               <span class="inline-flex gap-2 items-center">
                  <Heart class="size-4 inline text-(--z-color-danger)" />
                  <span> Sevimlilar </span>
               </span>
               <span>
                  <ChevronRight class="size-4" />
               </span>
            </RouterLink>
            <div class="bg-(--z-bg-primary) rounded-(--z-rounded) p-4 flex justify-between items-center">
               <span class="inline-flex gap-2 items-center">
                  <Bell class="size-4 inline text-(--z-color-primary)" />
                  <span> Bildirishnomalar </span>
               </span>
               <span>
                  <ChevronRight class="size-4" />
               </span>
            </div>
         </aside>
      </template>
   </NavigationPageDecorator>
</template>

<script setup lang="ts">
import { computed, inject } from "vue";
import { useAuth } from "@shared/store/useAuth";

const userData: any = inject("userData");
const AuthStore = useAuth();
import NavigationPageDecorator from "@/components/NavigationPageDecorator.vue";
import { isTMA } from "@tma.js/bridge";
import { Bell, ChevronRight, Heart, List, User } from "lucide-vue-next";
import { RouterLink } from "vue-router";

const user = computed(() => {
   if (isTMA()) {
      console.log(userData?.tgWebAppData.user);

      return userData?.tgWebAppData.user;
   } else {
      return {
         photo_url: null,
         first_name: "Jonny",
         last_name: "Cage",
         username: "jonny_is_cage",
      };
   }
});
</script>
