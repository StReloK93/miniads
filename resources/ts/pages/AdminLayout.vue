<template>
   <div class="flex h-dvh bg-tertiary">
      <nav class="w-64 border-r border-secondary p-4 flex flex-col gap-2">
         <div class="w-full flex justify-between mb-2 items-center">
            <RouterLink :to="{ name: 'home' }">
               <Button icon="pi pi-home" variant="text" />
            </RouterLink>

            <ThemeSwitcher />
         </div>
         <RouterLink :to="{ name: 'admin-categories' }"> Kategoriyalar </RouterLink>
         <RouterLink :to="{ name: 'admin-parameters' }"> Parameterlar </RouterLink>
      </nav>
      <RouterView class="p-10 grow bg-secondary overflow-y-auto" />
   </div>
</template>

<script setup lang="ts">
import ThemeSwitcher from "@/components/features/ThemeSwitcher.vue";
</script>
