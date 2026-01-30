<template>
   <div class="flex h-dvh bg-tertiary">
      <nav class="w-64 border-r border-(--color-border) p-4 flex flex-col gap-2">
         <div class="w-full flex justify-between mb-2 items-center">
            <a href="/">
               <BaseButton icon-only rounded severity="secondary">
                  <template #icon>
                     <HomeIcon class="size-5" />
                  </template>
               </BaseButton>
            </a>

            <ThemeSwitcher />
         </div>
         <RouterLink :to="{ name: 'admin-categories' }"> Kategoriyalar </RouterLink>
         <RouterLink :to="{ name: 'admin-parameters' }"> Parameterlar </RouterLink>
      </nav>
      <RouterView class="p-10 grow bg-secondary overflow-y-auto" />
   </div>
</template>

<script setup lang="ts">
import { HomeIcon } from "@heroicons/vue/24/outline";
import ThemeSwitcher from "@shared/components/ThemeSwitcher.vue";
</script>
