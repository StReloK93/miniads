<template>
   <router-view v-slot="{ Component, route }">
      <transition name="slide-in" mode="out-in">
         <component :is="Component" :key="route.fullPath" class="full-page" />
      </transition>
   </router-view>
</template>
