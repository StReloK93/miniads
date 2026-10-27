<template>
   <div class="inline-flex z-glass border rounded-full" role="group">
      <button
         v-for="option in props.options"
         :key="option.value"
         @click.stop="option.onClick?.()"
         type="button"
         :class="[
            'inline-flex items-center gap-2 justify-center text-xs font-medium first:rounded-l-full active:scale-95 last:rounded-r-full transition-colors',
            props.size === 'sm' ? 'w-8.5 h-8.5' : 'w-11 h-11',
            option.class || 'text-(--z-primary) focus:bg-(--z-primary)/10',
         ]"
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
   class?: string;
   onClick?: () => void;
}

const props = withDefaults(
   defineProps<{
      options?: ButtonOption[];
      size?: "sm" | "md";
   }>(),
   {
      options: (): ButtonOption[] => [],
      size: "md",
   },
);
</script>
