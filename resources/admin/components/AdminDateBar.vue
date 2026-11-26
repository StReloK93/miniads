<template>
   <div class="adm-datebar">
      <button
         v-if="modelValue.date"
         type="button"
         class="adm-datebar-arrow"
         aria-label="Oldingi kun"
         @click="step(-1)"
      >
         <ChevronLeft class="size-5" />
      </button>

      <!-- Sana: bosilsa kalendar ochiladi -->
      <button type="button" class="adm-datebar-current" :aria-expanded="open" @click="openCalendar">
         <span class="adm-datebar-icon"><CalendarDays class="size-4.5" aria-hidden="true" /></span>
         <span class="adm-datebar-text">
            <b>{{ primary }}</b>
            <small v-if="secondary">{{ secondary }}</small>
         </span>
         <ChevronDown class="size-4 shrink-0 opacity-50" aria-hidden="true" />
      </button>

      <button
         v-if="modelValue.date"
         type="button"
         class="adm-datebar-arrow"
         aria-label="Keyingi kun"
         :disabled="modelValue.date >= today"
         @click="step(1)"
      >
         <ChevronRight class="size-5" />
      </button>

   <AdminSheet :open="open" title="Kunni tanlang" :subtitle="secondary ? `${primary} · ${secondary}` : primary" @close="open = false">
      <div class="adm-cal">
         <!-- Tez tanlash -->
         <div class="adm-cal-quick">
            <button
               v-for="chip in chips"
               :key="chip.label"
               type="button"
               class="adm-chip"
               :class="{ 'is-active': chip.active }"
               @click="choose(chip.value)"
            >
               {{ chip.label }}
            </button>
         </div>

         <!-- Oy: ‹ Noyabr 2026 › -->
         <div class="adm-cal-head">
            <button type="button" class="adm-cal-nav" aria-label="Oldingi oy" @click="shiftMonth(-1)">
               <ChevronLeft class="size-5" />
            </button>
            <b>{{ monthTitle }}</b>
            <button type="button" class="adm-cal-nav" aria-label="Keyingi oy" :disabled="isCurrentMonth" @click="shiftMonth(1)">
               <ChevronRight class="size-5" />
            </button>
         </div>

         <div class="adm-cal-grid" role="grid" :aria-label="monthTitle">
            <span v-for="name in WEEKDAYS" :key="name" class="adm-cal-weekday" role="columnheader">{{ name }}</span>
            <template v-for="cell in cells" :key="cell.key">
               <span v-if="!cell.day" class="adm-cal-empty" />
               <button
                  v-else
                  type="button"
                  class="adm-cal-day"
                  :class="{ 'is-today': cell.key === today, 'is-selected': cell.key === modelValue.date, 'is-weekend': cell.weekend }"
                  :disabled="cell.key > today"
                  :aria-label="longLabel(cell.key)"
                  :aria-pressed="cell.key === modelValue.date"
                  @click="choose({ date: cell.key, days: null })"
               >
                  {{ cell.day }}
               </button>
            </template>
         </div>
      </div>
   </AdminSheet>
   </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { CalendarDays, ChevronDown, ChevronLeft, ChevronRight } from "lucide-vue-next";
import AdminSheet from "@admin/components/AdminSheet.vue";
import { fromKey, longLabel, relativeLabel, shiftKey, toKey, todayKey } from "@admin/modules/dates";

/** Tanlov: bitta kun (date) yoki oxirgi N kun (days) */
export type DateSelection = { date: string | null; days: number | null };

const props = defineProps<{
   modelValue: DateSelection;
   /** "7 kun" / "30 kun" tanlovi (bosh sahifada faqat bitta kun) */
   ranges?: boolean;
}>();
const emit = defineEmits<{ (e: "update:modelValue", value: DateSelection): void }>();

const MONTHS = ["Yanvar", "Fevral", "Mart", "Aprel", "May", "Iyun", "Iyul", "Avgust", "Sentabr", "Oktabr", "Noyabr", "Dekabr"];
const WEEKDAYS = ["Du", "Se", "Ch", "Pa", "Ju", "Sh", "Ya"];

const today = todayKey();
const yesterday = shiftKey(today, -1);
const open = ref(false);

// Ko'rsatilayotgan oy (kalendar ochilganda — tanlangan kunning oyi)
const view = ref({ year: 0, month: 0 });

const primary = computed(() => {
   const { date, days } = props.modelValue;
   if (!date) return `Oxirgi ${days} kun`;
   const relative = relativeLabel(date);
   return relative === "Bugun" || relative === "Kecha" ? relative : longLabel(date);
});
const secondary = computed(() => {
   const { date } = props.modelValue;
   if (!date) return "";
   const relative = relativeLabel(date);
   return relative === "Bugun" || relative === "Kecha" ? longLabel(date) : "";
});

const chips = computed(() => {
   const { date, days } = props.modelValue;
   const list: { label: string; value: DateSelection; active: boolean }[] = [
      { label: "Bugun", value: { date: today, days: null }, active: date === today },
      { label: "Kecha", value: { date: yesterday, days: null }, active: date === yesterday },
   ];
   if (props.ranges) {
      list.push(
         { label: "7 kun", value: { date: null, days: 7 }, active: !date && days === 7 },
         { label: "30 kun", value: { date: null, days: 30 }, active: !date && days === 30 },
      );
   }
   return list;
});

const monthTitle = computed(() => `${MONTHS[view.value.month]} ${view.value.year}`);
const isCurrentMonth = computed(() => {
   const now = new Date();
   return view.value.year === now.getFullYear() && view.value.month === now.getMonth();
});

/** Oy katakchalari: dushanbadan boshlanadi, oldingi bo'sh joylar bilan */
const cells = computed(() => {
   const { year, month } = view.value;
   const first = new Date(year, month, 1);
   const offset = (first.getDay() + 6) % 7;
   const total = new Date(year, month + 1, 0).getDate();
   const list: { key: string; day: number | null; weekend: boolean }[] = [];
   for (let i = 0; i < offset; i++) list.push({ key: `empty-${i}`, day: null, weekend: false });
   for (let day = 1; day <= total; day++) {
      const date = new Date(year, month, day);
      list.push({ key: toKey(date), day, weekend: date.getDay() === 0 || date.getDay() === 6 });
   }
   return list;
});

function openCalendar() {
   const base = fromKey(props.modelValue.date ?? today);
   view.value = { year: base.getFullYear(), month: base.getMonth() };
   open.value = true;
}

function shiftMonth(direction: number) {
   const date = new Date(view.value.year, view.value.month + direction, 1);
   view.value = { year: date.getFullYear(), month: date.getMonth() };
}

function choose(value: DateSelection) {
   emit("update:modelValue", value);
   open.value = false;
}

function step(direction: number) {
   if (!props.modelValue.date) return;
   const next = shiftKey(props.modelValue.date, direction);
   if (next <= today) emit("update:modelValue", { date: next, days: null });
}
</script>
