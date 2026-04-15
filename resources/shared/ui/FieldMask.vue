<template>
   <Field :name="name" v-slot="{ value, setValue }">
      <input
         ref="inputRef"
         type="text"
         class="field"
         :value="formatValue(value)"
         :placeholder="placeholder"
         :inputmode="inputmode"
         :disabled="disabled"
         @input="onInput($event, setValue)"
         @keydown="onKeydown($event, value, setValue)"
         @paste="onPaste($event, setValue)"
      />
   </Field>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { Field } from "vee-validate";

type InputMode = "text" | "email" | "search" | "tel" | "url" | "none" | "numeric" | "decimal";

const props = defineProps<{
   name: string;
   mask: string;
   disabled?: boolean;
   placeholder?: string;
   inputmode?: InputMode;
}>();

const inputRef = ref<HTMLInputElement | null>(null);

const rules: Record<string, RegExp> = {
   "9": /\d/,
   "0": /\d/,
   A: /[a-zA-Z]/,
   "*": /[a-zA-Z0-9]/,
};

function isMaskToken(char: string) {
   return char in rules;
}

function getMaskSlots() {
   return [...props.mask].filter(isMaskToken);
}

function getMaskMaxLength() {
   return getMaskSlots().length;
}

function extractRaw(value = "") {
   return value.replace(/[^a-zA-Z0-9]/g, "");
}

function normalizeRawByMask(value = "") {
   const raw = extractRaw(value);
   const result: string[] = [];
   const maskSlots = getMaskSlots();

   let rawIndex = 0;
   let slotIndex = 0;

   while (rawIndex < raw.length && slotIndex < maskSlots.length) {
      const char = raw[rawIndex];
      const slot = maskSlots[slotIndex];
      const rule = rules[slot];

      if (rule.test(char)) {
         result.push(slot === "A" ? char.toUpperCase() : char);
         slotIndex++;
      }

      rawIndex++;
   }

   return result.join("").slice(0, getMaskMaxLength());
}

function formatValue(raw = "") {
   const normalized = normalizeRawByMask(raw);
   if (!normalized) return "";

   let result = "";
   let rawIndex = 0;

   for (const maskChar of props.mask) {
      if (isMaskToken(maskChar)) {
         const char = normalized[rawIndex];
         if (!char) break;

         result += maskChar === "A" ? char.toUpperCase() : char;
         rawIndex++;
      } else {
         if (rawIndex === 0) continue;
         if (rawIndex > normalized.length) break;
         result += maskChar;
      }
   }

   return result;
}

function updateInputValue(raw: string, setValue: (v: string) => void) {
   const normalized = normalizeRawByMask(raw);
   setValue(normalized);

   if (inputRef.value) {
      inputRef.value.value = formatValue(normalized);
   }
}

function onInput(e: Event, setValue: (v: string) => void) {
   const input = e.target as HTMLInputElement;
   updateInputValue(input.value, setValue);
}

function onKeydown(e: KeyboardEvent, currentValue: string | undefined, setValue: (v: string) => void) {
   if (e.key !== "Backspace") return;

   const input = e.target as HTMLInputElement;
   const start = input.selectionStart ?? 0;
   const end = input.selectionEnd ?? 0;

   if (start !== end) return;

   const formatted = formatValue(currentValue || "");
   if (!formatted || start === 0) return;

   e.preventDefault();

   const leftPart = formatted.slice(0, start);
   const rightPart = formatted.slice(end);

   const leftRaw = extractRaw(leftPart).slice(0, -1);
   const rightRaw = extractRaw(rightPart);

   updateInputValue(leftRaw + rightRaw, setValue);

   requestAnimationFrame(() => {
      if (!inputRef.value) return;

      const newFormatted = inputRef.value.value;
      let caret = 0;
      let seenRaw = 0;
      const targetRawCount = leftRaw.length;

      while (caret < newFormatted.length && seenRaw < targetRawCount) {
         if (/[a-zA-Z0-9]/.test(newFormatted[caret])) {
            seenRaw++;
         }
         caret++;
      }

      inputRef.value.setSelectionRange(caret, caret);
   });
}

function onPaste(e: ClipboardEvent, setValue: (v: string) => void) {
   e.preventDefault();

   const pasted = e.clipboardData?.getData("text") ?? "";
   const input = e.target as HTMLInputElement;

   const start = input.selectionStart ?? 0;
   const end = input.selectionEnd ?? 0;
   const current = input.value;

   const nextValue = current.slice(0, start) + pasted + current.slice(end);
   updateInputValue(nextValue, setValue);

   requestAnimationFrame(() => {
      if (!inputRef.value) return;

      const rawBefore = extractRaw(current.slice(0, start));
      const rawPasted = normalizeRawByMask(pasted);
      const targetRawCount = rawBefore.length + rawPasted.length;

      const formatted = inputRef.value.value;
      let caret = 0;
      let seenRaw = 0;

      while (caret < formatted.length && seenRaw < targetRawCount) {
         if (/[a-zA-Z0-9]/.test(formatted[caret])) {
            seenRaw++;
         }
         caret++;
      }

      inputRef.value.setSelectionRange(caret, caret);
   });
}
</script>
