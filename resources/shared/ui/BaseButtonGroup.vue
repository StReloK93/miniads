<template>
   <div class="inline-flex z-bg-gradient backdrop-blur-sm border rounded-full border-(--z-border)" role="group">
      <button
         v-for="option in props.options"
         :key="option.value"
         @click.stop="option.onClick?.()"
         type="button"
         class="inline-flex items-center gap-2 w-11 h-11 justify-center text-xs font-medium text-(--z-primary) first:rounded-l-full active:scale-95 last:rounded-r-full focus:bg-(--z-primary)/10"
      >
         <component v-if="option.icon" :is="option.icon" class="size-4 shrink-0" />

         <span v-if="option.label">
            {{ option.label }}
         </span>
      </button>
   </div>
</template>

<script setup lang="ts">
import type { Component } from "vue";

export interface ButtonOption {
   label?: string;
   value: string | number;
   icon?: Component;
   onClick?: () => void;
}

const props = withDefaults(
   defineProps<{
      options?: ButtonOption[];
   }>(),
   {
      options: (): ButtonOption[] => [],
   },
);
</script>
