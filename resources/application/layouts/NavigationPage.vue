<template>
   <main class="h-full">
      <router-view v-slot="{ Component, route }">
         <transition name="slide-in" mode="out-in">
            <component :is="Component" :key="route.name" />
         </transition>
      </router-view>
      <BottomNavigator
         class="absolute bottom-[calc(var(--safe-area-bottom)+var(--spacing)*2)] left-2 right-2 z-100"
      />
   </main>
</template>

<script setup lang="ts">
import BottomNavigator from "@/layouts/BottomNavigator.vue";
</script>
