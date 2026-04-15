<template>
   <section class="pt-[calc(var(--safe-area-top)+var(--spacing)*4)] bg-(--z-background) flex flex-col h-full">
      <main class="px-4" :class="props.headerClass">
         <slot name="header" :is-compact="isActive" :progress="displayProgress"></slot>
      </main>
      <main class="relative w-full grow">
         <aside
            ref="scrollEl"
            :class="[
               props.contentClass,
               isActive ? 'overflow-y-auto' : 'overflow-hidden',
               { 'overflow-y-auto': props.autoScroll },
            ]"
            class="absolute px-4 inset-0 no-scrollbar pt-4 pb-[calc(var(--safe-area-bottom)+var(--spacing)*20)] z-10"
         >
            <!-- isCompact ? 'overflow-y-auto' : 'overflow-hidden',  -->
            <slot name="content" :is-compact="isActive" :progress="displayProgress"></slot>
         </aside>
      </main>
      <div
         class="absolute inset-0 top-auto pointer-events-none h-12 bg-linear-to-t from-white/90 via-white/60 to-transparent z-50"
      />
   </section>
</template>
<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useTouchToggleProgress } from "@shared/composables/useTouchToggleProgress";

const props = withDefaults(
   defineProps<{
      headerClass?: string[];
      contentClass?: string[];
      autoScroll?: boolean;
   }>(),
   {
      headerClass: () => [],
      contentClass: () => [],
      autoScroll: true,
   },
);

const scrollEl = ref<HTMLElement | null>(null);

const { progress, isActive, displayProgress } = useTouchToggleProgress(scrollEl, {
   threshold: 120,
   autoScroll: props.autoScroll,
});

onMounted(() => {
   // setContainer(scrollEl.value);
});
</script>
