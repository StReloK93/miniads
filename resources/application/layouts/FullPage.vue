<template>
   <main class="flex flex-col h-dvh w-dvw relative">
      <router-view v-slot="{ Component, route }">
         <transition name="slide-in" mode="out-in">
            <component :is="Component" :key="route.fullPath" class="full-page h-full" />
         </transition>
      </router-view>
   </main>
</template>
