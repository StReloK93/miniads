<template>
   <router-view v-slot="{ Component, route }">
      <transition name="slide-in" mode="out-in">
         <component :is="Component" :key="route.fullPath" class="full-page px-3" />
      </transition>
   </router-view>
</template>
