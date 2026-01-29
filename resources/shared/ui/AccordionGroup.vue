<script setup lang="ts">
import { provide, computed } from "vue";

const props = defineProps<{
   modelValue?: number | null;
}>();

const emit = defineEmits<{
   (e: "update:modelValue", value: number | null): void;
}>();

const toggle = (index: number) => {
   emit("update:modelValue", props.modelValue === index ? null : index);
};

provide("accordion", {
   activeIndex: computed(() => props.modelValue),
   toggle,
});
</script>

<template>
   <div>
      <slot />
   </div>
</template>
