<template>
   <main class="container mx-auto flex flex-col h-dvh bg-tertiary pt-safe-top pb-2">
      <section class="grow relative border-y border-slate-100">
         <router-view v-slot="{ Component, route }">
            <transition name="slide-in">
               <component
                  :is="Component"
                  :key="route.fullPath"
                  class="absolute inset-0 overflow-y-auto overflow-x-hidden p-4 flex flex-col gap-4 bg-white"
               />
            </transition>
         </router-view>
      </section>
      <BaseBottomNavigator />
   </main>
</template>

<script setup lang="ts">
import BaseBottomNavigator from "@/components/BaseBottomNavigator.vue";
</script>
