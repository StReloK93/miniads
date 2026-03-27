<template>
   <section class="pt-[calc(var(--safe-area-top)+var(--spacing)*4)] bg-(--z-background) flex flex-col h-full">
      <main class="px-4" :class="props.headerClass">
         <slot name="header" :is-compact="isCompact"></slot>
      </main>
      <main class="relative w-full grow">
         <aside
            :class="props.contentClass"
            @touchstart="handleTouchStart"
            @touchmove="handleTouchMove"
            @wheel="handleWheel"
            class="absolute px-4 inset-0 overflow-y-auto no-scrollbar pt-4 pb-[calc(var(--safe-area-bottom)+var(--spacing)*20)] z-10"
         >
            <slot name="content" :is-compact="isCompact"></slot>
         </aside>
      </main>
      <div
         class="absolute inset-0 top-auto pointer-events-none h-20 bg-linear-to-t from-white/90 via-white/60 to-transparent z-50"
      />
   </section>
</template>
<script setup lang="ts">
import { useGestureHeader } from "@shared/composables/useGestureHeader";
const { isCompact, handleTouchStart, handleTouchMove, handleWheel } = useGestureHeader();
const props = defineProps<{
   headerClass?: string[];
   contentClass?: string[];
}>();
</script>
