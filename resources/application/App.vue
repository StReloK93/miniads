<template>
   <section class="w-dvw h-dvh overflow-hidden no-scrollbar relative">
      <RouterView v-slot="{ Component, route }">
         <transition :name="animationName" mode="out-in">
            <component :is="Component" :key="route.meta.layoutKey" @vue:mounted="onComponentMounted" />
         </transition>
      </RouterView>
   </section>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";

const animationName = ref("");

function onComponentMounted() {
   animationName.value = "slide-bottom";
}
</script>
