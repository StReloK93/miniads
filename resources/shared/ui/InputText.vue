<template>
   <div class="input-wrapper" :data-invalid="invalid || undefined">
      <input
         class="input-text"
         :class="[size && `input-${size}`]"
         :value="modelValue"
         :type="type"
         :placeholder="placeholder"
         :disabled="disabled"
         @input="$emit('update:modelValue', $event.target?.value)"
      />
   </div>
</template>

<script setup lang="ts">
defineProps<{
   modelValue?: string | number;
   placeholder?: string;
   type?: string;
   disabled?: boolean;
   invalid?: boolean;
   size?: "sm" | "md" | "lg";
}>();

defineEmits(["update:modelValue"]);
</script>
