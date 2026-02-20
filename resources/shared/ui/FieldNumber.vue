<template>
   <Field :name="name" v-slot="{ value, setValue }">
      <input
         type="text"
         class="field"
         :value="format(value)"
         @input="onInput($event, value, setValue)"
         :placeholder="placeholder"
         :disabled="disabled"
      />
   </Field>
</template>

<script setup lang="ts">
import { Field } from "vee-validate";

const props = defineProps<{
   name: string;
   placeholder?: string;
   disabled?: boolean;
   min?: number; // default 0
   max?: number;
}>();

const minValue = props.min ?? 0;

function format(value?: number) {
   if (value === undefined || value === null) return "";
   return value.toLocaleString("ru-RU");
}

function parse(value: string) {
   return Number(value.replace(/\s/g, ""));
}

function onInput(e: Event, currentValue: number | undefined, setValue: (v: number) => void) {
   const input = e.target as HTMLInputElement;
   const raw = input.value;

   const parsed = parse(raw);

   // NaN yoki bo‘sh holat
   if (raw.trim() === "" || isNaN(parsed)) {
      setValue(minValue);
      input.value = format(minValue);
      return;
   }

   // min dan past bo‘lsa — tuzatamiz
   if (parsed < minValue) {
      setValue(minValue);
      input.value = format(minValue);
      return;
   }

   // ❗ max dan oshsa — hech narsa qilmaymiz (blok)
   if (props.max !== undefined && parsed > props.max) {
      input.value = format(currentValue ?? minValue);
      return;
   }

   // normal holat
   setValue(parsed);
   input.value = format(parsed);
}
</script>
