<template>
   <nav class="admin-tabs" role="tablist">
      <button
         v-for="tab in tabs"
         :key="tab.value"
         type="button"
         role="tab"
         class="admin-tabs-item"
         :class="{ 'is-active': tab.value === modelValue, 'is-danger': tab.danger && tab.count }"
         :aria-selected="tab.value === modelValue"
         @click="select(tab.value)"
      >
         {{ tab.label }}
         <span v-if="tab.count">{{ tab.count }}</span>
      </button>
   </nav>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";

/**
 * Sahifa ichidagi bo'limlar (telefonda uzun sahifa o'rniga). Tanlangan tab URL'da (?tab=) saqlanadi:
 * sahifa yangilansa yoki orqaga qaytilsa ham o'sha bo'lim ochiladi.
 */
const props = defineProps<{
   modelValue: string;
   tabs: { value: string; label: string; count?: number; danger?: boolean }[];
}>();
const emit = defineEmits<{ (e: "update:modelValue", value: string): void }>();

const route = useRoute();
const router = useRouter();

function select(value: string) {
   if (value === props.modelValue) return;
   emit("update:modelValue", value);
   router.replace({ query: { ...route.query, tab: value === props.tabs[0]?.value ? undefined : value } });
   window.scrollTo({ top: 0 });
}

onMounted(() => {
   const fromUrl = route.query.tab;
   if (typeof fromUrl === "string" && props.tabs.some((tab) => tab.value === fromUrl)) {
      emit("update:modelValue", fromUrl);
   }
});
</script>
