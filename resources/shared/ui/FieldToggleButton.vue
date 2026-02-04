<template>
   <Field :name="props.name" v-slot="{ field, handleChange }">
      <input
         type="checkbox"
         class="input-text"
         :id="'checkbox' + props.name"
         @vue:mounted="inputMouted(field)"
         @input="handleChange"
         @change="(event) => onNativeFileChange(event, field)"
         :class="[size && `input-${size}`]"
         v-bind="field"
         :placeholder="placeholder"
         :disabled="disabled"
         hidden
      />
      <label
         :for="'checkbox' + props.name"
         class="cursor-pointer flex items-center gap-2 bg-(--color-surface-muted) p-1 rounded-md select-none"
      >
         <div :class="{ 'bg-(--color-surface)': checked }" class="py-1.5 w-full text-center rounded-sm transition-all">
            <span v-if="checked" class="font-semibold">{{ attrs.onLabel }}</span>
            <span v-else>{{ attrs.offLabel }}</span>
         </div>
      </label>
   </Field>
</template>

<script setup lang="ts">
import { Field } from "vee-validate";
import { ref, useAttrs } from "vue";

const attrs = useAttrs();
const checked = ref(false);
function onNativeFileChange(event: Event, $field: any) {
   const target = event.target as HTMLInputElement;

   checked.value = target.checked;
   $field.onInput(target.checked);
}

function inputMouted($field: any) {
   checked.value = $field.value;
}

const props = defineProps<{
   name: string;
   placeholder?: string;
   type?: string;
   disabled?: boolean;
   invalid?: boolean;
   size?: "sm" | "md" | "lg";
}>();
</script>
<style scoped></style>
