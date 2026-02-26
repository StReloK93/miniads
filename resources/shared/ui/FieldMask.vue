<template>
   <Field :name="name" v-slot="{ value, setValue }">
      <input
         type="text"
         class="field"
         :value="applyMask(value)"
         @input="onInput($event, setValue)"
         @keydown="onKeydown($event, value, setValue)"
         :placeholder="placeholder"
         :inputmode="inputmode"
         :disabled="disabled"
      />
   </Field>
</template>

<script setup lang="ts">
import { Field } from "vee-validate";

const props = defineProps<{
   name: string;
   mask: string;
   disabled?: boolean;
   placeholder?: string;
   inputmode?: "text" | "email" | "search" | "tel" | "url" | "none" | "numeric" | "decimal" | "search";
}>();

const rules: Record<string, RegExp> = {
   "9": /\d/,
   "0": /\d/,
   A: /[a-zA-Z]/,
   "*": /[a-zA-Z0-9]/,
};

// 🔹 masksiz (raw) qiymatni ajratib olish
function extractRaw(value = "") {
   return value.replace(/[^a-zA-Z0-9]/g, "");
}

// 🔹 raw qiymatga mask qo‘llash
function applyMask(raw = "") {
   const chars = raw.split("");
   let result = "";
   let i = 0;

   for (const m of props.mask) {
      if (rules[m]) {
         const c = chars[i];
         if (!c) break;

         if (rules[m].test(c)) {
            result += m === "A" ? c.toUpperCase() : c;
            i++;
         } else {
            i++;
            continue;
         }
      } else {
         result += m;
      }
   }
   return result;
}

// 🔹 oddiy input
function onInput(e: Event, setValue: (v: string) => void) {
   const input = e.target as HTMLInputElement;
   const raw = extractRaw(input.value);
   setValue(raw);
   input.value = applyMask(raw);
}

// 🔹 MUHIM QISM: Backspace ishlashi
function onKeydown(e: KeyboardEvent, currentValue: string | undefined, setValue: (v: string) => void) {
   if (e.key !== "Backspace") return;

   e.preventDefault();

   const raw = extractRaw(currentValue || "");
   const newRaw = raw.slice(0, -1); // oxirgi belgini o‘chiramiz

   setValue(newRaw);

   const input = e.target as HTMLInputElement;
   input.value = applyMask(newRaw);
}
</script>
