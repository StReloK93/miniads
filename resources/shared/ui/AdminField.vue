<template>
   <div class="admin-field">
      <label v-if="label" class="admin-field-label" :for="id">{{ label }}</label>

      <template v-if="kind === 'toggle'">
         <button
            :id="id"
            type="button"
            class="admin-toggle"
            :class="{ 'is-active': Boolean(modelValue) }"
            role="switch"
            :aria-checked="Boolean(modelValue)"
            @click="emit('update:modelValue', !modelValue)"
         >
            <span class="admin-toggle-track"><span /></span>
            <span>{{ modelValue ? onLabel : offLabel }}</span>
         </button>
      </template>

      <template v-else-if="kind === 'file'">
         <div v-if="typeof modelValue === 'string' && modelValue" class="admin-file-current">
            <img :src="modelValue" alt="Amaldagi fayl" />
            <span>Joriy rasm</span>
         </div>
         <input
            :id="id"
            class="admin-native-input admin-file-input"
            type="file"
            accept=".svg,image/svg+xml"
            @change="onFileChange"
         />
      </template>

      <template v-else-if="kind === 'select'">
         <select
            :id="id"
            class="admin-native-input"
            :value="modelValue ?? ''"
            @change="emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
         >
            <option value="" disabled>Tanlang</option>
            <option v-for="option in options" :key="String(option)" :value="option">{{ option }}</option>
         </select>
      </template>

      <template v-else-if="kind === 'tags'">
         <input
            :id="id"
            class="admin-native-input"
            type="text"
            :value="Array.isArray(modelValue) ? modelValue.join(', ') : ''"
            :placeholder="placeholder || 'Variantlarni vergul bilan ajrating'"
            @input="onTagsInput"
         />
         <p class="admin-field-hint">Variantlarni vergul bilan ajrating.</p>
      </template>

      <input
         v-else
         :id="id"
         class="admin-native-input"
         :type="kind"
         :value="modelValue ?? ''"
         :placeholder="placeholder"
         :min="min"
         :max="max"
         @input="onValueInput"
      />

      <p v-if="error" class="admin-field-error">{{ error }}</p>
   </div>
</template>

<script setup lang="ts">
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
      offLabel: "O‘chirilgan",
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

<style scoped>
.admin-field {
   display: grid;
   gap: 7px;
}

.admin-field-label,
.admin-field-hint {
   color: var(--z-muted-text);
   font-size: 12px;
}

.admin-native-input {
   width: 100%;
   min-height: 42px;
   border: 1px solid var(--z-border);
   border-radius: 10px;
   background: var(--z-field-background);
   padding: 9px 12px;
   color: var(--z-foreground);
   font: inherit;
   font-size: 13px;
   outline: none;
}

.admin-native-input:focus {
   border-color: var(--z-primary);
   box-shadow: 0 0 0 3px color-mix(in srgb, var(--z-primary) 10%, transparent);
}

.admin-field-error {
   margin: 0;
   color: var(--z-danger);
   font-size: 11px;
}

.admin-field-hint {
   margin: -2px 0 0;
   font-size: 10px;
}

.admin-file-current {
   display: flex;
   align-items: center;
   gap: 10px;
   color: var(--z-muted-text);
   font-size: 11px;
}

.admin-file-current img {
   width: 38px;
   height: 38px;
   object-fit: contain;
}

.admin-toggle {
   display: inline-flex;
   min-height: 38px;
   align-items: center;
   gap: 9px;
   border: 0;
   background: transparent;
   padding: 0;
   color: var(--z-foreground);
   cursor: pointer;
}

.admin-toggle-track {
   display: flex;
   width: 36px;
   height: 21px;
   align-items: center;
   border-radius: 30px;
   background: #cbd5e1;
   padding: 3px;
   transition: background 0.15s ease;
}

.admin-toggle-track span {
   width: 15px;
   height: 15px;
   border-radius: 50%;
   background: white;
   transition: transform 0.15s ease;
}

.admin-toggle.is-active .admin-toggle-track {
   background: #1c967c;
}

.admin-toggle.is-active .admin-toggle-track span {
   transform: translateX(15px);
}
</style>
