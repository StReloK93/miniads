<template>
   <router-view v-slot="{ Component, route }">
      <transition name="slide-in" mode="out-in">
         <component
            :is="Component"
            :key="route.fullPath"
            :class="{ 'pb-0!': hasFocusedInput }"
            class="full-page px-4 transition-all"
         />
      </transition>
   </router-view>
</template>
<script setup lang="ts">
import { useFocusedInput } from "@shared/composables/useFocusInput";
const { hasFocusedInput } = useFocusedInput();
</script>
