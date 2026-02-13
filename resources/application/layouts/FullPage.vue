<template>
   <main class="flex flex-col h-dvh w-dvw relative">
      <router-view v-slot="{ Component, route }">
         <transition name="slide-in" mode="out-in">
            <component
               :is="Component"
               :key="route.fullPath"
               class="pt-[calc(var(--safe-area-top)+var(--spacing)*4)] pb-[calc(var(--safe-area-bottom)+var(--spacing)*2)] h-full px-4"
            />
         </transition>
      </router-view>
   </main>
</template>

<script setup lang="ts"></script>
