<template>
   <!--
      Vaqt oralig'i ("09:00–19:00"): ikkita soat tanlagich, telefonda tizim soat oynasi ochiladi — qo'lda yozilmaydi.
      Qiymat bitta satr bo'lib saqlanadi; ikkalasi bo'sh bo'lsa — "".
   -->
   <Field :name="name" v-slot="{ value, setValue }">
      <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2">
         <label class="field gap-2 cursor-pointer">
            <span class="shrink-0 text-xs text-(--z-muted-text)">{{ fromLabel }}</span>
            <input
               type="time"
               :id="name"
               class="time-input"
               :value="parse(value).from"
               :disabled="disabled"
               @input="onInput(value, setValue, 'from', $event)"
            />
         </label>
         <span class="text-(--z-muted-text)">–</span>
         <label class="field gap-2 cursor-pointer">
            <span class="shrink-0 text-xs text-(--z-muted-text)">{{ toLabel }}</span>
            <input
               type="time"
               class="time-input"
               :value="parse(value).to"
               :disabled="disabled"
               @input="onInput(value, setValue, 'to', $event)"
            />
         </label>
      </div>
   </Field>
</template>

<script setup lang="ts">
import { Field } from "vee-validate";

defineProps<{
   name: string;
   /** "Ochiladi" / "Yopiladi" kabi kichik yorliqlar */
   fromLabel?: string;
   toLabel?: string;
   disabled?: boolean;
}>();

const SEPARATOR = "–";

/** "9:00-19:00", "09:00 – 19:00" → { from: "09:00", to: "19:00" }; boshqa matn — bo'sh */
function parse(value: unknown): { from: string; to: string } {
   const match = /(\d{1,2}):(\d{2})\s*[–—-]\s*(\d{1,2}):(\d{2})/.exec(String(value ?? ""));
   if (!match) return { from: "", to: "" };
   const pad = (hours: string) => hours.padStart(2, "0");
   return { from: `${pad(match[1])}:${match[2]}`, to: `${pad(match[3])}:${match[4]}` };
}

function onInput(current: unknown, setValue: (value: string) => void, side: "from" | "to", event: Event) {
   const range = { ...parse(current), [side]: (event.target as HTMLInputElement).value };
   // Bitta tomoni tanlangan bo'lsa ham saqlanadi: ikkinchisi to'ldirilguncha shu holat turadi
   setValue(range.from || range.to ? `${range.from || "00:00"}${SEPARATOR}${range.to || "00:00"}` : "");
}
</script>

<style scoped>
.time-input {
   width: 100%;
   min-width: 0;
   background: transparent;
   outline: none;
   color: var(--z-foreground);
   font-variant-numeric: tabular-nums;

   /* Chrome'dagi soat belgisi butun maydonni egallamasin */
   &::-webkit-calendar-picker-indicator {
      opacity: 0.5;
      cursor: pointer;
   }
}
</style>
