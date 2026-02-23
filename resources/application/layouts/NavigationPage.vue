<template>
   <main class="h-full">
      <router-view v-slot="{ Component, route }">
         <transition name="slide-in" mode="out-in">
            <component :is="Component" :key="route.fullPath" />
         </transition>
      </router-view>
      <BaseBottomNavigator
         class="absolute bottom-[calc(var(--safe-area-bottom)+var(--spacing)*2)] left-2 right-2 z-100"
      />
   </main>
</template>

<script setup lang="ts">
import BaseBottomNavigator from "@/components/BaseBottomNavigator.vue";
</script>
