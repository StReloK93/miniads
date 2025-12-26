<template>
   <main class="container mx-auto flex flex-col h-dvh">
      <BaseNavigation />
      <section class="grow relative border-y border-surface-100">
         <RouterView class="absolute inset-0 overflow-y-auto overflow-x-hidden p-4" />
      </section>
      <BaseBottomNavigator />
   </main>
</template>

<script setup lang="ts">
import BaseBottomNavigator from "@/components/BaseBottomNavigator.vue";
import BaseNavigation from "@/components/BaseNavigation.vue";
</script>
