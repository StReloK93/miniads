<template>
   <!-- Butun qator bosiladi; o'ngda kichik o'chirgich -->
   <button
      type="button"
      role="switch"
      :aria-checked="model"
      :disabled="disabled"
      class="w-full flex items-center justify-between gap-3 cursor-pointer text-left disabled:opacity-60 disabled:cursor-not-allowed"
      @click="model = !model"
   >
      <span class="min-w-0"><slot /></span>
      <span
         class="relative h-6 w-11 shrink-0 rounded-full transition-colors"
         :class="model ? 'bg-(--z-primary)' : 'bg-(--z-muted-text)/30'"
      >
         <span
            class="absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow transition-transform"
            :class="{ 'translate-x-5': model }"
         />
      </span>
   </button>
</template>

<script setup lang="ts">
defineProps<{ disabled?: boolean }>();
const model = defineModel<boolean>({ default: false });
</script>
