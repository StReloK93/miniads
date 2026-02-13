<template>
   <main class="h-dvh">
      <section class="relative w-full h-full overflow-hidden">
         <router-view v-slot="{ Component, route }">
            <transition name="slide-in" mode="out-in">
               <component
                  :is="Component"
                  :key="route.fullPath"
                  class="p-4 pt-[calc(var(--safe-area-top)+var(--spacing)*4)] flex flex-col gap-4 overflow-y-auto overflow-x-hidden absolute inset-0 pb-[calc(var(--safe-area-bottom)+var(--spacing)*20)] no-scrollbar bg-white"
               />
            </transition>
         </router-view>
      </section>
      <div
         class="absolute bottom-0 left-0 right-0 pointer-events-none h-20 bg-linear-to-t from-white/70 via-white/70 to-transparent z-50"
      />
      <BaseBottomNavigator
         class="absolute bottom-[calc(var(--safe-area-bottom)+var(--spacing)*2)] left-2 right-2 z-100"
      />
   </main>
</template>

<script setup lang="ts">
import BaseBottomNavigator from "@/components/BaseBottomNavigator.vue";
</script>
