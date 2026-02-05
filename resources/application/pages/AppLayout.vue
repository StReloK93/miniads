<template>
   <main class="container mx-auto flex flex-col h-dvh bg-tertiary pt-safe-top pb-2">
      <!-- <main class="bg-[#069569] h-60 absolute top-0 left-0 right-0"></main> -->
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
