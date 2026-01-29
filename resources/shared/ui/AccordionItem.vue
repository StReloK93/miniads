<template>
   <div
      class="ui-accordion overflow-hidden border-b last:border-b-0 border-(--color-border)"
      :data-size="size"
      :data-open="isOpen"
   >
      <!-- HEADER -->
      <button
         type="button"
         @click="accordion.toggle(index)"
         class="flex w-full items-center justify-between text-left transition hover:bg-(--color-surface-muted)"
      >
         <span class="text-sm font-medium text-(--color-text-primary)">
            {{ title }}
         </span>

         <ChevronDownIcon
            class="h-5 w-5 text-(--color-text-secondary) transition-transform duration-(--duration-fast)"
            :class="isOpen && 'rotate-180'"
         />
      </button>

      <!-- CONTENT -->
      <div
         class="grid transition-[grid-template-rows] duration-(--duration-normal) ease-(--ease-soft)"
         :class="isOpen ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'"
      >
         <div class="overflow-hidden">
            <div class="ui-accordion__content text-sm">
               <slot />
            </div>
         </div>
      </div>
   </div>
</template>

<script setup lang="ts">
import { inject, computed } from "vue";
import { ChevronDownIcon } from "@heroicons/vue/24/outline";

type Size = "sm" | "md" | "lg";

const props = withDefaults(
   defineProps<{
      index: number;
      title: string;
      size?: Size;
   }>(),
   {
      size: "md",
   },
);

const accordion = inject<any>("accordion");

const isOpen = computed(() => accordion?.activeIndex.value === props.index);
</script>
