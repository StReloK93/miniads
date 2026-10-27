<template>
   <!-- "dan — gacha" oralig'i: narx, yil, yurgan masofa, maydon... -->
   <div class="grid grid-cols-2 gap-2">
      <label v-for="side in sides" :key="side.key" class="field gap-1.5">
         <span class="shrink-0 text-xs text-(--z-muted-text)">{{ side.label }}</span>
         <input
            type="text"
            inputmode="numeric"
            class="w-full min-w-0 bg-transparent outline-none text-(--z-foreground) placeholder:text-(--z-muted-text)"
            :placeholder="side.placeholder"
            :value="format(modelValue?.[side.key])"
            @input="onInput(side.key, $event)"
         />
         <span v-if="unit" class="shrink-0 text-xs text-(--z-muted-text)">{{ unit }}</span>
      </label>
   </div>
</template>

<script setup lang="ts">
import type { RangeValue } from "@/features/filters/listFilters";

const props = withDefaults(
   defineProps<{
      modelValue?: RangeValue | null;
      unit?: string | null;
      /** Yil uchun false: "2022", "2 022" emas */
      grouping?: boolean;
      placeholderFrom?: string;
      placeholderTo?: string;
   }>(),
   { grouping: true, placeholderFrom: "", placeholderTo: "" },
);

const emit = defineEmits<{ (e: "update:modelValue", value: RangeValue): void }>();

const sides = [
   { key: "from" as const, label: "dan", placeholder: props.placeholderFrom },
   { key: "to" as const, label: "gacha", placeholder: props.placeholderTo },
];

function format(value?: number | null) {
   if (value === null || value === undefined) return "";
   return props.grouping ? value.toLocaleString("ru-RU") : String(value);
}

function onInput(key: "from" | "to", event: Event) {
   const input = event.target as HTMLInputElement;
   const digits = input.value.replace(/\D/g, "");
   const value = digits ? Number(digits) : null;
   input.value = format(value);
   emit("update:modelValue", { ...(props.modelValue ?? {}), [key]: value });
}
</script>
