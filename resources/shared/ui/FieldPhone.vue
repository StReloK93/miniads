<template>
   <Field :name="name" v-slot="{ value, setValue }">
      <div class="flex flex-col" :onVnodeMounted="() => initFromValue(value, setValue)">
         <!-- Asosiy input konteyneri -->
         <div class="relative flex items-center">
            <!-- 1. Telefon raqami kiritish (Tel va Telegram yoki Faqat telefon) -->
            <input
               v-if="mode !== 'telegram'"
               ref="inputRef"
               type="text"
               :name="name"
               :id="name"
               class="field pr-20!"
               :value="formatValue(value)"
               :placeholder="placeholder"
               :inputmode="inputmode"
               :disabled="disabled"
               autocomplete="tel"
               @input="onInput($event, setValue)"
               @keydown="onKeydown($event, value, setValue)"
               @paste="onPaste($event, setValue)"
            />

            <!-- 2. Faqat Telegram rejimi: toza va chiroyli @username kartochkasi -->
            <div
               v-else
               class="field pr-20! flex items-center gap-2.5 bg-(--z-card) border border-(--z-border) select-none cursor-default"
            >
               <div class="size-6 rounded-md bg-[#229ED9]/15 flex items-center justify-center shrink-0">
                  <svg class="size-3.5 text-[#229ED9]" viewBox="0 0 24 24" fill="currentColor">
                     <path d="m20.665 3.717-17.73 6.837c-1.21.486-1.203 1.161-.222 1.462l4.552 1.42 10.532-6.645c.498-.303.953-.14.579.192l-8.533 7.701h-.002l-.313 4.678c.459 0 .661-.21.918-.459l2.204-2.143 4.585 3.387c.845.466 1.455.226 1.666-.783l3.009-14.179c.309-1.238-.472-1.8-1.445-1.469z"/>
                  </svg>
               </div>
               <span class="text-xs font-bold text-(--z-foreground) truncate">@{{ telegramUsername }}</span>
               <span class="text-[11px] text-(--z-muted-text) truncate hidden sm:inline">(raqamsiz)</span>
            </div>

            <!-- Chetki ixcham addon selektor (Pricedagi kabi) -->
            <div class="absolute inset-y-0 right-0 flex items-center pr-1.5">
               <!-- Username yo'q bo'lsa: nofaol ko'rinish -->
               <button
                  v-if="!telegramUsername"
                  type="button"
                  @click="onDisabledClick"
                  class="h-8 px-2.5 py-1 text-xs font-semibold rounded-lg bg-(--z-muted)/40 text-(--z-muted-text) hover:bg-(--z-muted)/60 flex items-center gap-1.5 cursor-pointer select-none transition-transform border border-transparent"
                  :class="{ 'animate-shake': isShaking }"
                  title="Sizda username yo'q"
               >
                  <Phone class="size-3.5 text-(--z-muted-text)" />
                  <span class="text-xs font-medium">Tel</span>
                  <ChevronDown class="size-3 text-(--z-muted-text) opacity-50" />
               </button>

               <!-- Username bo'lsa: zamonaviy ixcham Menu selektori -->
               <Menu v-else as="div" class="relative flex items-center">
                  <MenuButton
                     class="h-8 px-2.5 py-1 text-xs font-semibold rounded-lg bg-(--z-muted)/50 hover:bg-(--z-muted)/80 text-(--z-foreground) flex items-center gap-1.5 cursor-pointer select-none transition-colors border border-(--z-border)/50"
                  >
                     <template v-if="mode === 'both'">
                        <div class="flex items-center gap-1 shrink-0">
                           <Phone class="size-3 text-(--z-primary)" />
                           <span class="text-[10px] font-bold text-(--z-muted-text) leading-none">+</span>
                           <svg class="size-3 text-[#229ED9]" viewBox="0 0 24 24" fill="currentColor">
                              <path d="m20.665 3.717-17.73 6.837c-1.21.486-1.203 1.161-.222 1.462l4.552 1.42 10.532-6.645c.498-.303.953-.14.579.192l-8.533 7.701h-.002l-.313 4.678c.459 0 .661-.21.918-.459l2.204-2.143 4.585 3.387c.845.466 1.455.226 1.666-.783l3.009-14.179c.309-1.238-.472-1.8-1.445-1.469z"/>
                           </svg>
                        </div>
                     </template>

                     <template v-else-if="mode === 'phone'">
                        <Phone class="size-3.5 text-(--z-primary) shrink-0" />
                        <span class="text-xs font-medium text-(--z-foreground)">Tel</span>
                     </template>

                     <template v-else>
                        <svg class="size-3.5 text-[#229ED9] shrink-0" viewBox="0 0 24 24" fill="currentColor">
                           <path d="m20.665 3.717-17.73 6.837c-1.21.486-1.203 1.161-.222 1.462l4.552 1.42 10.532-6.645c.498-.303.953-.14.579.192l-8.533 7.701h-.002l-.313 4.678c.459 0 .661-.21.918-.459l2.204-2.143 4.585 3.387c.845.466 1.455.226 1.666-.783l3.009-14.179c.309-1.238-.472-1.8-1.445-1.469z"/>
                        </svg>
                        <span class="text-xs font-semibold text-[#229ED9]">TG</span>
                     </template>

                     <ChevronDown class="size-3 text-(--z-muted-text) shrink-0 ml-0.5" />
                  </MenuButton>

                  <transition
                     enter-active-class="transition duration-100 ease-out"
                     enter-from-class="transform scale-95 opacity-0"
                     enter-to-class="transform scale-100 opacity-100"
                     leave-active-class="transition duration-75 ease-in"
                     leave-from-class="transform scale-100 opacity-100"
                     leave-to-class="transform scale-95 opacity-0"
                  >
                     <MenuItems
                        class="absolute right-0 top-full mt-2 w-64 rounded-2xl border z-glass-strong p-1.5 text-(--z-foreground) focus:outline-none z-50 flex flex-col gap-1"
                     >
                        <!-- 1. Telefon va Telegram -->
                        <MenuItem v-slot="{ active }">
                           <button
                              type="button"
                              @click="setMode('both', value, setValue)"
                              class="w-full flex items-center justify-between p-2 rounded-xl text-xs font-medium cursor-pointer transition-colors"
                              :class="[
                                 mode === 'both' ? 'bg-(--z-muted) text-(--z-primary) font-semibold' : 'text-(--z-foreground)',
                                 active && mode !== 'both' ? 'bg-(--z-muted)/60' : '',
                              ]"
                           >
                              <div class="flex items-center gap-2.5">
                                 <div class="flex items-center -space-x-1 shrink-0">
                                    <div class="size-6 rounded-full bg-(--z-primary)/15 text-(--z-primary) flex items-center justify-center border border-(--z-card)">
                                       <Phone class="size-3" />
                                    </div>
                                    <div class="size-6 rounded-full bg-[#229ED9]/15 text-[#229ED9] flex items-center justify-center border border-(--z-card)">
                                       <svg class="size-3" viewBox="0 0 24 24" fill="currentColor">
                                          <path d="m20.665 3.717-17.73 6.837c-1.21.486-1.203 1.161-.222 1.462l4.552 1.42 10.532-6.645c.498-.303.953-.14.579.192l-8.533 7.701h-.002l-.313 4.678c.459 0 .661-.21.918-.459l2.204-2.143 4.585 3.387c.845.466 1.455.226 1.666-.783l3.009-14.179c.309-1.238-.472-1.8-1.445-1.469z"/>
                                       </svg>
                                    </div>
                                 </div>
                                 <div class="flex flex-col text-left">
                                    <span class="font-semibold text-xs leading-tight">Telefon va Telegram</span>
                                    <span class="text-[10px] text-(--z-muted-text)">Qo'ng'iroq va yozishmalar uchun</span>
                                 </div>
                              </div>
                              <Check v-if="mode === 'both'" class="size-3.5 text-emerald-500 shrink-0" />
                           </button>
                        </MenuItem>

                        <!-- 2. Faqat telefon -->
                        <MenuItem v-slot="{ active }">
                           <button
                              type="button"
                              @click="setMode('phone', value, setValue)"
                              class="w-full flex items-center justify-between p-2 rounded-xl text-xs font-medium cursor-pointer transition-colors"
                              :class="[
                                 mode === 'phone' ? 'bg-(--z-muted) text-(--z-primary) font-semibold' : 'text-(--z-foreground)',
                                 active && mode !== 'phone' ? 'bg-(--z-muted)/60' : '',
                              ]"
                           >
                              <div class="flex items-center gap-2.5">
                                 <div class="size-6 rounded-full bg-(--z-primary)/15 text-(--z-primary) flex items-center justify-center shrink-0">
                                    <Phone class="size-3" />
                                 </div>
                                 <div class="flex flex-col text-left">
                                    <span class="font-semibold text-xs leading-tight">Faqat telefon</span>
                                    <span class="text-[10px] text-(--z-muted-text)">Faqat qo'ng'iroq qabul qilish</span>
                                 </div>
                              </div>
                              <Check v-if="mode === 'phone'" class="size-3.5 text-emerald-500 shrink-0" />
                           </button>
                        </MenuItem>

                        <!-- 3. Faqat telegram -->
                        <MenuItem v-slot="{ active }">
                           <button
                              type="button"
                              @click="setMode('telegram', value, setValue)"
                              class="w-full flex items-center justify-between p-2 rounded-xl text-xs font-medium cursor-pointer transition-colors"
                              :class="[
                                 mode === 'telegram' ? 'bg-(--z-muted) text-(--z-primary) font-semibold' : 'text-(--z-foreground)',
                                 active && mode !== 'telegram' ? 'bg-(--z-muted)/60' : '',
                              ]"
                           >
                              <div class="flex items-center gap-2.5">
                                 <div class="size-6 rounded-full bg-[#229ED9]/15 text-[#229ED9] flex items-center justify-center shrink-0">
                                    <svg class="size-3" viewBox="0 0 24 24" fill="currentColor">
                                       <path d="m20.665 3.717-17.73 6.837c-1.21.486-1.203 1.161-.222 1.462l4.552 1.42 10.532-6.645c.498-.303.953-.14.579.192l-8.533 7.701h-.002l-.313 4.678c.459 0 .661-.21.918-.459l2.204-2.143 4.585 3.387c.845.466 1.455.226 1.666-.783l3.009-14.179c.309-1.238-.472-1.8-1.445-1.469z"/>
                                    </svg>
                                 </div>
                                 <div class="flex flex-col text-left">
                                    <span class="font-semibold text-xs leading-tight">Faqat Telegram</span>
                                    <span class="text-[10px] text-(--z-muted-text)">Raqam yashiriladi, faqat @{{ telegramUsername }}</span>
                                 </div>
                              </div>
                              <Check v-if="mode === 'telegram'" class="size-3.5 text-emerald-500 shrink-0" />
                           </button>
                        </MenuItem>
                     </MenuItems>
                  </transition>
               </Menu>
            </div>
         </div>

         <!-- Izoh matnlari -->
         <p v-if="mode === 'both' && telegramUsername" class="text-[11px] text-(--z-muted-text) px-1 mt-1">
            Qo'ng'iroq va Telegram (@{{ telegramUsername }}) orqali bog'lanish mumkin
         </p>
         <p v-else-if="mode === 'phone'" class="text-[11px] text-(--z-muted-text) px-1 mt-1">
            Xaridorlar faqat telefon orqali bog'lanadilar
         </p>
         <p v-else-if="mode === 'telegram' && telegramUsername" class="text-[11px] text-[#229ED9] px-1 mt-1">
            Telefon raqamingiz ko'rsatilmaydi, xaridorlar faqat Telegram orqali yozishlari mumkin
         </p>

         <!-- Agar username yo'q bo'lsa va bosmoqchi bo'lsa yumshoq ogohlantirish -->
         <transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 -translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-1"
         >
            <p v-if="showNoUsernameAlert" class="text-xs text-amber-500 font-medium px-1 flex items-center gap-1.5 mt-1">
               <AlertCircle class="size-3.5 shrink-0" /> Telegram profilingizda username o'rnatilmagan
            </p>
         </transition>
      </div>
   </Field>
</template>

<script setup lang="ts">
import { computed, nextTick, ref, watch } from "vue";
import { Field } from "vee-validate";
import { Menu, MenuButton, MenuItems, MenuItem } from "@headlessui/vue";
import { Phone, ChevronDown, Check, AlertCircle } from "lucide-vue-next";
import { useAuth } from "@shared/store/useAuth";

type InputMode = "text" | "email" | "search" | "tel" | "url" | "none" | "numeric" | "decimal";
type ContactMode = "both" | "phone" | "telegram";

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

const mode = ref<ContactMode>(telegramUsername.value ? "both" : "phone");
const previousPhone = ref<string>("");
const showNoUsernameAlert = ref(false);
const isShaking = ref(false);
let alertTimeout: ReturnType<typeof setTimeout> | null = null;
const initialized = ref(false);

function initFromValue(val: string | undefined | null, setValue: (v: string) => void) {
   if (initialized.value) return;

   if (!telegramUsername.value) {
      mode.value = "phone";
      initialized.value = true;
      return;
   }

   if (val === "telegram" || (val === null && props.disabled)) {
      mode.value = "telegram";
      setValue("telegram");
   } else if (val && val !== "telegram") {
      mode.value = "both";
      previousPhone.value = val;
   } else {
      mode.value = "both";
   }

   initialized.value = true;
}

watch(
   () => telegramUsername.value,
   (un) => {
      if (!un) {
         mode.value = "phone";
      }
   },
);

function onDisabledClick(e: Event) {
   e.preventDefault();
   e.stopPropagation();
   showNoUsernameAlert.value = true;
   isShaking.value = true;
   setTimeout(() => {
      isShaking.value = false;
   }, 450);

   if (alertTimeout) clearTimeout(alertTimeout);
   alertTimeout = setTimeout(() => {
      showNoUsernameAlert.value = false;
   }, 3500);
}

function setMode(newMode: ContactMode, currentValue: string | undefined, setValue: (v: string) => void) {
   mode.value = newMode;
   if (newMode === "telegram") {
      if (currentValue && currentValue !== "telegram") {
         previousPhone.value = currentValue;
      }
      setValue("telegram");
   } else {
      const restored = previousPhone.value || (currentValue !== "telegram" ? currentValue : "") || "";
      setValue(restored);
      if (inputRef.value) {
         inputRef.value.value = formatValue(restored);
         nextTick(() => {
            inputRef.value?.focus();
         });
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
   if (raw === "telegram") return "";
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

<style scoped>
@keyframes shake {
   0%, 100% {
      transform: translateX(0);
   }
   20%, 60% {
      transform: translateX(-4px);
   }
   40%, 80% {
      transform: translateX(4px);
   }
}
.animate-shake {
   animation: shake 0.4s ease-in-out;
}
</style>
