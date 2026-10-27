<template>
   <BaseDrawer :open="open" title="Filtrlar" @close="emit('close')">
      <!-- Qoralama: "Ko'rsatish" bosilgunicha ro'yxat qayta yuklanmaydi -->
      <div class="flex flex-col gap-6">
         <section>
            <h4 class="filter-title">Saralash</h4>
            <div class="flex flex-wrap gap-2">
               <button
                  v-for="option in SORT_OPTIONS"
                  :key="option.value"
                  type="button"
                  class="filter-chip"
                  :class="{ 'is-active': (draft.sort ?? 'new') === option.value }"
                  @click="draft.sort = option.value"
               >
                  {{ option.label }}
               </button>
            </div>
         </section>

         <section v-if="withCategory">
            <h4 class="filter-title">Bo'lim</h4>
            <FieldSelect
               :model-value="draft.root_category_id ?? 0"
               :options="[{ id: 0, name: 'Barcha bo\'limlar' }, ...(categoryStore.parentCategories ?? [])]"
               @update:model-value="(id) => (draft.root_category_id = id || null)"
               value="name"
               placeholder="Barcha bo'limlar"
            />
         </section>

         <!-- Server ro'yxati "Barcha shaharlar" (id 0) bilan keladi: 0 — filtr yo'q (null) -->
         <section>
            <h4 class="filter-title">Shahar</h4>
            <FieldSelect
               :model-value="draft.city_id ?? 0"
               :options="cityStore.cities ?? []"
               @update:model-value="(id) => (draft.city_id = id || null)"
               value="name"
               placeholder="Barcha shaharlar"
            />
         </section>

         <section v-if="withPrice">
            <h4 class="filter-title">Narx</h4>
            <FilterRange
               :model-value="{ from: draft.price_from, to: draft.price_to }"
               placeholder-from="0"
               placeholder-to="∞"
               @update:model-value="(range) => ((draft.price_from = range.from), (draft.price_to = range.to))"
            />
         </section>

         <!-- Kategoriya parametrlari: tanlov — chiplar (ko'p bo'lsa ro'yxat), son — oraliq -->
         <section v-for="parameter in parameters" :key="parameter.id">
            <h4 class="filter-title">{{ parameter.title }}</h4>

            <FilterRange
               v-if="parameter.type === 'number'"
               :model-value="rangeOf(parameter.id)"
               :unit="isYearParameter(parameter) ? null : cleanUnit(parameter.unit)"
               :grouping="!isYearParameter(parameter)"
               @update:model-value="(range) => (draft.params![parameter.id] = range)"
            />

            <div v-else-if="optionsOf(parameter).length <= CHIP_LIMIT" class="flex flex-wrap gap-2">
               <button
                  v-for="option in optionsOf(parameter)"
                  :key="option"
                  type="button"
                  class="filter-chip"
                  :class="{ 'is-active': draft.params![parameter.id] === option }"
                  @click="toggleOption(parameter.id, option)"
               >
                  {{ option }}
               </button>
            </div>

            <FieldSelect
               v-else
               :model-value="draft.params![parameter.id] ?? null"
               :options="[{ id: null, name: 'Farqi yo\'q' }, ...optionsOf(parameter).map((o) => ({ id: o, name: o }))]"
               value="name"
               placeholder="Farqi yo'q"
               @update:model-value="(value) => (draft.params![parameter.id] = value)"
            />
         </section>

         <section>
            <BaseSwitch v-model="draft.with_image">
               <span class="text-sm font-medium">Faqat rasmli e'lonlar</span>
            </BaseSwitch>
         </section>
      </div>

      <template #footer>
         <div class="grid grid-cols-2 gap-3">
            <BaseButton severity="secondary" class="w-full" @click="reset">Tozalash</BaseButton>
            <BaseButton class="w-full" @click="apply">Ko'rsatish</BaseButton>
         </div>
      </template>
   </BaseDrawer>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import BaseDrawer from "@shared/ui/BaseDrawer.vue";
import BaseButton from "@shared/ui/BaseButton.vue";
import FieldSelect from "@shared/ui/FieldSelect.vue";
import BaseSwitch from "@shared/ui/BaseSwitch.vue";
import FilterRange from "@/features/filters/FilterRange.vue";
import { SORT_OPTIONS, emptyFilters, type ListFilters, type RangeValue } from "@/features/filters/listFilters";
import { isYearParameter } from "@shared/modules/productSpecs";
import { useCity } from "@shared/entities/District/useCity";
import { useCategory } from "@shared/entities/Category/useCategory";
import type { IParameter } from "@shared/types";

// Bundan ko'p variant bo'lsa chiplar ekranni to'ldirib yuboradi — ro'yxat qulayroq
const CHIP_LIMIT = 8;

const props = withDefaults(
   defineProps<{
      open: boolean;
      modelValue: ListFilters;
      parameters?: IParameter[];
      /** Qidiruvda: bo'lim tanlash (kategoriya sahifasida kerak emas) */
      withCategory?: boolean;
      withPrice?: boolean;
   }>(),
   { parameters: () => [], withCategory: false, withPrice: true },
);

const emit = defineEmits<{
   (e: "update:modelValue", value: ListFilters): void;
   (e: "close"): void;
}>();

const cityStore = useCity();
const categoryStore = useCategory();

const draft = ref<ListFilters>(emptyFilters());

// Har ochilganda joriy filtrlardan boshlaymiz; yopib qo'yilsa o'zgarishlar saqlanmaydi
watch(
   () => props.open,
   (open) => {
      if (open) draft.value = { ...emptyFilters(), ...toRawDeep(props.modelValue) };
   },
   { immediate: true },
);

function toRawDeep<T>(value: T): T {
   return JSON.parse(JSON.stringify(value ?? {}));
}

function optionsOf(parameter: IParameter): string[] {
   return ((parameter.options ?? []) as unknown[]).map((option) =>
      typeof option === "object" && option !== null ? String((option as { value: string }).value) : String(option),
   );
}

function rangeOf(parameterId: number): RangeValue {
   const value = draft.value.params?.[parameterId];
   return typeof value === "object" && value !== null ? value : {};
}

// "-qavat" → "qavat": oraliqda "dan 2 gacha 5 qavat" deb o'qiladi
function cleanUnit(unit: string | null) {
   return unit?.replace(/^-/, "") || null;
}

function toggleOption(parameterId: number, option: string) {
   draft.value.params![parameterId] = draft.value.params![parameterId] === option ? null : option;
}

function reset() {
   draft.value = emptyFilters();
}

function apply() {
   emit("update:modelValue", draft.value);
   emit("close");
}
</script>

<style scoped>
.filter-title {
   margin-bottom: 0.625rem;
   color: var(--z-muted-text);
   font-size: 0.75rem;
   font-weight: 600;
   letter-spacing: 0.02em;
}

.filter-chip {
   height: 2.25rem;
   padding: 0 0.875rem;
   border: 1px solid var(--z-border);
   border-radius: 999px;
   background: var(--z-card);
   color: var(--z-foreground);
   font-size: 0.8125rem;
   font-weight: 500;
   cursor: pointer;
   transition:
      background-color var(--duration) var(--ease-soft),
      color var(--duration) var(--ease-soft),
      transform var(--duration) var(--ease-soft);

   &:active {
      transform: scale(0.96);
   }

   &.is-active {
      border-color: var(--z-primary);
      background: var(--z-primary);
      color: var(--z-primary-foreground);
   }
}

</style>
