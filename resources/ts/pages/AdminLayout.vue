<template>
   <div class="flex h-dvh bg-background">
      <nav class="w-64 border-r border-secondary p-4 flex flex-col gap-2">
         <div class="w-full flex justify-end mb-2">
            <ThemeSwitcher />
         </div>
         <RouterLink :to="{ name: 'home' }"> Home page </RouterLink>
         <RouterLink :to="{ name: 'admin-categories' }"> Kategoriyalar </RouterLink>
         <RouterLink :to="{ name: 'admin-parameters' }"> Parameterlar </RouterLink>
      </nav>
      <main class="p-4 grow bg-secondary">
         <RouterView />
      </main>
   </div>
</template>

<script setup lang="ts">
import ThemeSwitcher from "@/components/features/ThemeSwitcher.vue";
</script>
