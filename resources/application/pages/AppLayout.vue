<template>
   <main class="container mx-auto flex flex-col h-dvh bg-tertiary pt-safe-top pb-2">
      <BaseNavigation />
      <section class="grow relative border-y border-(--color-border)">
         <RouterView class="absolute inset-0 overflow-y-auto overflow-x-hidden px-4 py-3" />
      </section>
      <BaseBottomNavigator />
   </main>
</template>

<script setup lang="ts">
import BaseBottomNavigator from "@/components/BaseBottomNavigator.vue";
import BaseNavigation from "@/components/BaseNavigation.vue";
</script>
