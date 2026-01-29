<template>
   <nav class="flex justify-between px-3 pt-2 pb-safe-bottom bg-surface-50 dark:bg-surface-900">
      <RouterLink :to="{ name: 'home' }">
         <BaseButton severity="secondary" class="flex-col gap-0! text-xs bg-transparent! active:bg-slate-50! min-w-20">
            <template #icon>
               <HomeIcon class="size-5" />
            </template>
            Bosh sahifa
         </BaseButton>
      </RouterLink>
      <RouterLink :to="{ name: 'favorites' }">
         <BaseButton severity="secondary" class="flex-col gap-0! text-xs bg-transparent! active:bg-slate-50! min-w-20">
            <template #icon>
               <HeartIcon class="size-5" />
            </template>
            Sevimlilar
         </BaseButton>
      </RouterLink>
      <RouterLink :to="{ name: 'create-product' }">
         <BaseButton severity="secondary" class="flex-col gap-0! text-xs bg-transparent! active:bg-slate-50! min-w-20">
            <template #icon>
               <PlusCircleIcon class="size-5" />
            </template>
            E'lon berish
         </BaseButton>
      </RouterLink>
      <BaseButton severity="secondary" class="flex-col gap-0! text-xs bg-transparent! active:bg-slate-50! min-w-20">
         <template #icon>
            <UserIcon class="size-5" />
         </template>
         Profil
      </BaseButton>
      <!-- <BottomDrawer v-model="openUserSettings">
         <main class="p-4">
            <ThemeSwitcher />
         </main>
      </BottomDrawer> -->
   </nav>
</template>
<script lang="ts" setup>
import { HeartIcon, HomeIcon, PlusCircleIcon, UserIcon } from "@heroicons/vue/24/outline";
// import BottomDrawer from "./ui/BottomDrawer.vue";
// import { ref } from "vue";
// const visible = ref(false);
// const openUserSettings = ref(false);
</script>
