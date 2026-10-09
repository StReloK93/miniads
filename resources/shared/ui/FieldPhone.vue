<template>
   <Field :name="name" v-slot="{ value, setValue }">
      <div class="flex flex-col gap-2">
         <!-- Telegram username mavjud bo'lganda tanlov tugmalari -->
         <div v-if="telegramUsername" class="grid grid-cols-2 gap-1.5 p-1 rounded-xl bg-(--z-card) border border-(--z-border)">
            <button
               type="button"
               class="flex items-center justify-center gap-1.5 py-1.5 px-3 rounded-lg text-xs font-semibold transition-all cursor-pointer select-none"
               :class="[
                  mode === 'phone'
                     ? 'bg-(--z-primary) text-white shadow-sm'
                     : 'text-(--z-muted-text) hover:text-(--z-foreground) hover:bg-(--z-muted)/40',
               ]"
               @click="setMode('phone', value, setValue)"
            >
               <Phone class="size-3.5" />
               <span>Telefon raqam</span>
            </button>

            <button
               type="button"
               class="flex items-center justify-center gap-1.5 py-1.5 px-3 rounded-lg text-xs font-semibold transition-all cursor-pointer select-none"
               :class="[
                  mode === 'telegram'
                     ? 'bg-(--z-primary) text-white shadow-sm'
                     : 'text-(--z-muted-text) hover:text-(--z-foreground) hover:bg-(--z-muted)/40',
               ]"
               @click="setMode('telegram', value, setValue)"
            >
               <MessageCircle class="size-3.5" />
               <span class="truncate">Faqat Telegram</span>
            </button>
         </div>

         <!-- 1. Telefon raqami kiritish rejimi -->
         <div v-if="mode === 'phone'" class="flex flex-col gap-1">
            <div class="relative flex items-center">
               <input
                  ref="inputRef"
                  type="text"
                  :name="name"
                  :id="name"
                  class="field"
                  :value="formatValue(value)"
                  :placeholder="placeholder"
                  :inputmode="inputmode"
                  :disabled="disabled"
                  autocomplete="tel"
                  @input="onInput($event, setValue)"
                  @keydown="onKeydown($event, value, setValue)"
                  @paste="onPaste($event, setValue)"
               />
            </div>
            <p v-if="telegramUsername" class="text-[11px] text-(--z-muted-text) px-1">
               Xaridorlar telefon va Telegram (@{{ telegramUsername }}) orqali bog'lana oladilar.
            </p>
         </div>

         <!-- 2. Faqat Telegram rejimi (raqam yashiriladi) -->
         <div
            v-else
            class="flex items-center justify-between p-3 rounded-xl border border-sky-500/30 bg-sky-500/10 text-sky-400 select-none"
         >
            <div class="flex items-start gap-3">
               <div class="size-9 shrink-0 rounded-full bg-sky-500/20 text-sky-300 flex items-center justify-center">
                  <MessageCircle class="size-4.5" />
               </div>
               <div class="flex flex-col gap-1">
                  <p class="text-xs font-bold text-(--z-foreground)">@{{ telegramUsername }}</p>
                  <p class="text-[11px] text-(--z-muted-text)">
                     Telefon raqamingiz ko'rsatilmaydi, xaridorlar faqat Telegram orqali yozishadi.
                  </p>
               </div>
            </div>

         </div>
      </div>
   </Field>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { Field } from "vee-validate";
import { Phone, MessageCircle } from "lucide-vue-next";
import { useAuth } from "@shared/store/useAuth";

type InputMode = "text" | "email" | "search" | "tel" | "url" | "none" | "numeric" | "decimal";

const props = withDefaults(
   defineProps<{
      name?: string;
      mask?: string;
      disabled?: boolean;
      placeholder?: string;
      inputmode?: InputMode;
      telegramUsername?: string | null;
   }>(),
   {
      name: "phone",
      mask: "99-999-99-99",
      placeholder: "93-123-45-67",
      inputmode: "tel",
      disabled: false,
   },
);

const authStore = useAuth();
const inputRef = ref<HTMLInputElement | null>(null);

const telegramUsername = computed(() => {
   const un = props.telegramUsername ?? authStore.user?.username;
   return un ? String(un).trim().replace(/^@/, "") : null;
});

// Agar telegram username bo'lsa va raqam bo'sh bo'lsa, 'telegram' rejimi ham tanlanishi mumkin
const mode = ref<"phone" | "telegram">("phone");
const previousPhone = ref<string>("");

function setMode(newMode: "phone" | "telegram", currentValue: string | undefined, setValue: (v: string) => void) {
   mode.value = newMode;
   if (newMode === "telegram") {
      if (currentValue) {
         previousPhone.value = currentValue;
      }
      setValue("");
   } else {
      const restored = previousPhone.value || "";
      setValue(restored);
      if (inputRef.value) {
         inputRef.value.value = formatValue(restored);
      }
   }
}

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
