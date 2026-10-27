<template>
   <Field :name="name" v-slot="{ value, setValue }">
      <input
         type="text"
         inputmode="numeric"
         :name="name"
         :id="name"
         class="field"
         v-bind="{ ...$attrs }"
         :value="format(value)"
         @input="onInput($event, value, setValue)"
         :placeholder="placeholder"
         :disabled="disabled"
      />
   </Field>
</template>

<script setup lang="ts">
import { Field } from "vee-validate";

const props = withDefaults(
   defineProps<{
      name: string;
      placeholder?: string;
      disabled?: boolean;
      min?: number; // default 0
      max?: number;
      /** Mingliklarni ajratish: narx "1 000 000"; yil uchun false — "2022" */
      grouping?: boolean;
   }>(),
   { grouping: true },
);

const minValue = props.min ?? 0;

function format(value?: number | null) {
   if (value === undefined || value === null || (value as unknown) === "") return "";
   return props.grouping ? Number(value).toLocaleString("ru-RU") : String(value);
}

function parse(value: string) {
   return Number(value.replace(/\s/g, ""));
}

function onInput(e: Event, currentValue: number | undefined, setValue: (v: number | undefined) => void) {
   const input = e.target as HTMLInputElement;
   const raw = input.value;

   // Bo'sh maydon bo'sh qoladi (ixtiyoriy parametr "0" bo'lib saqlanmasin)
   if (raw.trim() === "") {
      setValue(undefined);
      input.value = "";
      return;
   }

   const parsed = parse(raw);

   // Raqam bo'lmagan belgi — oldingi qiymat qoladi
   if (isNaN(parsed)) {
      input.value = format(currentValue);
      return;
   }

   // min dan past bo'lsa — tuzatamiz
   if (parsed < minValue) {
      setValue(minValue);
      input.value = format(minValue);
      return;
   }

   // ❗ max dan oshsa — hech narsa qilmaymiz (blok)
   if (props.max !== undefined && parsed > props.max) {
      input.value = format(currentValue);
      return;
   }

   // normal holat
   setValue(parsed);
   input.value = format(parsed);
}
</script>
