<template>
   <main class="container mx-auto flex flex-col h-dvh bg-tertiary pt-safe-top pb-2">
      <section class="grow relative border-y border-(--color-border)">
         <RouterView class="absolute inset-0 overflow-y-auto overflow-x-hidden p-4 flex flex-col gap-4" />
      </section>
      <BaseBottomNavigator />
   </main>
</template>

<script setup lang="ts">
import BaseBottomNavigator from "@/components/BaseBottomNavigator.vue";
</script>
