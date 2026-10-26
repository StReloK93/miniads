<template>
   <div class="min-w-0 grow">
      <p class="inline-flex items-center gap-1 text-xs font-medium" :class="tone.text">
         <component :is="expired ? CircleOff : Timer" class="size-3.5" />
         {{ label }}
      </p>
      <div v-if="!expired" class="mt-1.5 h-1 w-full max-w-40 rounded-full bg-(--z-muted) overflow-hidden">
         <div
            class="h-full rounded-full transition-[width] duration-500"
            :class="tone.bar"
            :style="{ width: `${percent}%` }"
         />
      </div>
   </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { CircleOff, Timer } from "lucide-vue-next";

/**
 * E'lon muddati: "4 kun qoldi" va ingichka chiziq. 2 kun va undan kam qolsa — ogohlantiruvchi rang.
 * `current` — qolgan kunlar (kasr bo'lishi mumkin, muddati o'tgan bo'lsa manfiy), `max` — to'liq muddat.
 */
const props = defineProps<{
   current: number;
   max: number;
}>();

const WARNING_DAYS = 2;

const expired = computed(() => props.current <= 0);
const daysLeft = computed(() => Math.floor(props.current));

const label = computed(() => {
   if (expired.value) return "Muddati tugagan";
   if (daysLeft.value < 1) return "Bugun tugaydi";
   return `${daysLeft.value} kun qoldi`;
});

const percent = computed(() => {
   if (!props.max) return 0;
   return Math.max(4, Math.min(100, (props.current / props.max) * 100));
});

const tone = computed(() => {
   if (expired.value) return { text: "text-(--z-muted-text)", bar: "" };
   if (props.current <= WARNING_DAYS) return { text: "text-amber-600 dark:text-amber-400", bar: "bg-amber-500" };
   return { text: "text-(--z-muted-text)", bar: "bg-(--z-primary)" };
});
</script>
