<template>
   <main class="container mx-auto h-dvh pt-[calc(var(--safe-area-top)+var(--spacing)*4)] relative bg-white">
      <section class="relative w-full h-full">
         <router-view v-slot="{ Component, route }: any">
            <transition :name="route.meta.transition">
               <component
                  :is="Component"
                  :key="route.fullPath"
                  class="p-4 flex flex-col gap-4 overflow-y-auto overflow-x-hidden absolute inset-0 pb-[calc(var(--safe-area-bottom)+var(--spacing)*20)] no-scrollbar"
               />
            </transition>
         </router-view>
      </section>
      <div
         class="pointer-events-none absolute bottom-0 left-0 right-0 h-20 bg-linear-to-t from-white/70 via-white/70 to-transparent z-50"
      />
      <BaseBottomNavigator
         class="absolute bottom-[calc(var(--safe-area-bottom)+var(--spacing)*2)] left-2 right-2 z-100"
      />
   </main>
</template>

<script setup lang="ts">
import BaseBottomNavigator from "@/components/BaseBottomNavigator.vue";
</script>
