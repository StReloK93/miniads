<template>
   <div class="relative inline-flex items-center justify-center" :style="{ width: `${size}px`, height: `${size}px` }">
      <svg class="-rotate-90" :width="size" :height="size" :viewBox="`0 0 ${size} ${size}`">
         <circle
            :cx="size / 2"
            :cy="size / 2"
            :r="radius"
            fill="none"
            stroke="currentColor"
            class="text-zinc-200 dark:text-zinc-800 stroke-white"
            :stroke-width="strokeWidth"
         />

         <circle
            :cx="size / 2"
            :cy="size / 2"
            :r="radius"
            fill="none"
            :stroke-width="strokeWidth"
            stroke-linecap="round"
            :stroke-dasharray="circumference"
            :stroke-dashoffset="dashOffset"
            class="transition-all duration-500 ease-out stroke-(--z-primary)"
         />
      </svg>

      <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
         <span class="mt-1 text-xs font-semibold leading-none text-(--z-primary)">
            {{ label }}
         </span>
      </div>
   </div>
</template>
<script setup lang="ts">
import { computed } from "vue";

const props = withDefaults(
   defineProps<{
      current: number; // qolgan kun, masalan 5.5
      max: number; // umumiy kun, masalan 7
      size?: number;
      strokeWidth?: number;
   }>(),
   {
      size: 50,
      strokeWidth: 2,
   },
);

const radius = computed(() => (props.size - props.strokeWidth) / 2);
const circumference = computed(() => 2 * Math.PI * radius.value);

const percent = computed(() => {
   if (!props.max || props.max <= 0) return 0;
   const value = (props.current / props.max) * 100;
   return Math.max(0, Math.min(100, value));
});

const dashOffset = computed(() => {
   return circumference.value - (percent.value / 100) * circumference.value;
});

const label = computed(() => {
   if (props.current <= 0) return props.current;
   return `${props.current} K`;
});

const statusClass = computed(() => {
   if (percent.value <= 15) return "text-red-500";
   if (percent.value <= 40) return "text-amber-500";
   return "text-emerald-500";
});
</script>
