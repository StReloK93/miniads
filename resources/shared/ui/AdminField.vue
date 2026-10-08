<template>
   <div class="grid gap-1.75">
      <label v-if="label" class="text-xs font-medium text-(--z-muted-text)" :for="id">{{ label }}</label>

      <template v-if="kind === 'toggle'">
         <button
            :id="id"
            type="button"
            class="inline-flex min-h-9.5 items-center gap-2.5 bg-transparent p-0 text-sm text-(--z-foreground) cursor-pointer select-none"
            role="switch"
            :aria-checked="Boolean(modelValue)"
            @click="emit('update:modelValue', !modelValue)"
         >
            <span
               class="flex h-5.5 w-9.5 items-center rounded-full p-0.5 transition-colors duration-150"
               :class="modelValue ? 'bg-emerald-600' : 'bg-slate-300 dark:bg-slate-700'"
            >
               <span
                  class="size-4 rounded-full bg-white shadow-xs transition-transform duration-150"
                  :class="modelValue ? 'translate-x-4' : 'translate-x-0'"
               />
            </span>
            <span class="text-xs font-medium">{{ modelValue ? onLabel : offLabel }}</span>
         </button>
      </template>

      <template v-else-if="kind === 'file'">
         <div v-if="typeof modelValue === 'string' && modelValue" class="flex items-center gap-2.5 text-xs text-(--z-muted-text)">
            <img :src="modelValue" alt="Amaldagi fayl" class="size-9.5 object-contain rounded-md bg-(--z-muted) p-1" />
            <span>Joriy rasm</span>
         </div>
         <input
            :id="id"
            class="w-full min-h-10.5 rounded-xl border border-(--z-border) bg-(--z-field-background) px-3 py-2 text-xs text-(--z-foreground) placeholder:text-(--z-muted-text)/60 outline-none transition focus:border-(--z-primary) focus:ring-2 focus:ring-(--z-primary)/20 file:mr-3 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:bg-(--z-muted) file:text-xs file:font-semibold file:text-(--z-foreground) cursor-pointer"
            type="file"
            accept=".svg,image/svg+xml"
            @change="onFileChange"
         />
      </template>

      <template v-else-if="kind === 'select'">
         <FieldSelect
            :model-value="modelValue"
            :options="options"
            placeholder="Tanlang"
            @update:model-value="emit('update:modelValue', $event)"
         />
      </template>

      <template v-else-if="kind === 'tags'">
         <input
            :id="id"
            class="w-full min-h-10.5 rounded-xl border border-(--z-border) bg-(--z-field-background) px-3 py-2 text-xs text-(--z-foreground) placeholder:text-(--z-muted-text)/60 outline-none transition focus:border-(--z-primary) focus:ring-2 focus:ring-(--z-primary)/20"
            type="text"
            :value="Array.isArray(modelValue) ? modelValue.join(', ') : ''"
            :placeholder="placeholder || 'Variantlarni vergul bilan ajrating'"
            @input="onTagsInput"
         />
         <p class="-mt-0.5 text-[10px] text-(--z-muted-text)">Variantlarni vergul bilan ajrating.</p>
      </template>

      <input
         v-else
         :id="id"
         class="w-full min-h-10.5 rounded-xl border border-(--z-border) bg-(--z-field-background) px-3 py-2 text-xs text-(--z-foreground) placeholder:text-(--z-muted-text)/60 outline-none transition focus:border-(--z-primary) focus:ring-2 focus:ring-(--z-primary)/20"
         :type="kind"
         :value="modelValue ?? ''"
         :placeholder="placeholder"
         :min="min"
         :max="max"
         @input="onValueInput"
      />

      <p v-if="error" class="m-0 text-xs text-(--z-danger)">{{ error }}</p>
   </div>
</template>

<script setup lang="ts">
import FieldSelect from "@shared/ui/FieldSelect.vue";

withDefaults(
   defineProps<{
      id?: string;
      label?: string;
      kind?: "text" | "number" | "select" | "toggle" | "tags" | "file";
      modelValue?: unknown;
      placeholder?: string;
      options?: (string | number)[];
      min?: number;
      max?: number;
      onLabel?: string;
      offLabel?: string;
      error?: string;
   }>(),
   {
      kind: "text",
      options: () => [],
      onLabel: "Yoqilgan",
      offLabel: "O'chirilgan",
   },
);
const emit = defineEmits<{ (event: "update:modelValue", value: unknown): void }>();

function onValueInput(event: Event) {
   const input = event.target as HTMLInputElement;
   emit("update:modelValue", input.type === "number" ? (input.value === "" ? "" : Number(input.value)) : input.value);
}

function onFileChange(event: Event) {
   const file = (event.target as HTMLInputElement).files?.[0];
   if (file) emit("update:modelValue", file);
}

function onTagsInput(event: Event) {
   emit(
      "update:modelValue",
      (event.target as HTMLInputElement).value
         .split(",")
         .map((item) => item.trim())
         .filter(Boolean),
   );
}
</script>
