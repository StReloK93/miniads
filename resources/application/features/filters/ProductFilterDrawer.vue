<template>
   <BaseDrawer :open="open" :title="$t('common.filters')" @close="emit('close')">
      <!-- Qoralama: "Ko'rsatish" bosilgunicha ro'yxat qayta yuklanmaydi -->
      <div class="flex flex-col gap-6">
         <section>
            <h4 class="filter-title">{{ $t("filters.sort") }}</h4>
            <div class="flex flex-wrap gap-2">
               <button
                  v-for="option in SORT_OPTIONS"
                  :key="option.value"
                  type="button"
                  class="filter-chip"
                  :class="{ 'is-active': (draft.sort ?? 'new') === option.value }"
                  @click="draft.sort = option.value"
               >
                  {{ $t(option.label) }}
               </button>
            </div>
         </section>

         <section v-if="withCategory">
            <h4 class="filter-title">{{ $t("categories.category") }}</h4>
            <FieldSelect
               :model-value="draft.root_category_id ?? 0"
               :options="[{ id: 0, name: $t('categories.all') }, ...(categoryStore.parentCategories ?? [])]"
               @update:model-value="(id) => (draft.root_category_id = id || null)"
               value="name"
               :placeholder="$t('categories.all')"
            />
         </section>

         <!-- Server ro'yxati "Barcha shaharlar" (id 0) bilan keladi: 0 — filtr yo'q (null) -->
         <section>
            <h4 class="filter-title">{{ $t("district.city") }}</h4>
            <FieldSelect
               :model-value="draft.city_id ?? 0"
               :options="cityStore.cities ?? []"
               @update:model-value="(id) => (draft.city_id = id || null)"
               value="name"
               :placeholder="$t('district.all')"
            />
         </section>

         <section v-if="withPrice">
            <h4 class="filter-title">{{ $t("common.price") }}</h4>
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
               :unit="displayUnit(parameter)"
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
                  {{ labelOf(parameter, option) }}
               </button>
            </div>

            <FieldSelect
               v-else
               :model-value="draft.params![parameter.id] ?? null"
               :options="[{ id: null, name: $t('filters.any') }, ...optionsOf(parameter).map((o) => ({ id: o, name: labelOf(parameter, o) }))]"
               value="name"
               :placeholder="$t('filters.any')"
               @update:model-value="(value) => (draft.params![parameter.id] = value)"
            />
         </section>

         <section>
            <BaseSwitch v-model="draft.with_image">
               <span class="text-sm font-medium">{{ $t("filters.withImage") }}</span>
            </BaseSwitch>
         </section>
      </div>

      <template #footer>
         <div class="grid grid-cols-2 gap-3">
            <BaseButton severity="secondary" class="w-full" @click="reset">{{ $t("common.clear") }}</BaseButton>
            <!-- Filtr o'zgarishi bilan: nechta e'lon qolayotgani darhol tugmada ko'rinadi -->
            <BaseButton class="w-full" :class="{ 'opacity-70': isCounting }" @click="apply">
               {{ buttonLabel }}
            </BaseButton>
         </div>
      </template>
   </BaseDrawer>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import ProductRepo from "@shared/entities/Product/ProductRepo";
import BaseDrawer from "@shared/ui/BaseDrawer.vue";
import BaseButton from "@shared/ui/BaseButton.vue";
import FieldSelect from "@shared/ui/FieldSelect.vue";
import BaseSwitch from "@shared/ui/BaseSwitch.vue";
import FilterRange from "@/features/filters/FilterRange.vue";
import {
   SORT_OPTIONS,
   emptyFilters,
   toQueryParams,
   type ListFilters,
   type RangeValue,
} from "@/features/filters/listFilters";
import { displayUnit, isYearParameter } from "@shared/modules/productSpecs";
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
      /**
       * Qaysi ro'yxat filtrlanyapti: { category_id } yoki { search }. Berilsa — filtr o'zgarganda
       * nechta e'lon qolishi so'raladi va tugmada ko'rsatiladi. null — son ko'rsatilmaydi.
       */
      countScope?: Record<string, unknown> | null;
   }>(),
   { parameters: () => [], withCategory: false, withPrice: true, countScope: null },
);

const emit = defineEmits<{
   (e: "update:modelValue", value: ListFilters): void;
   (e: "close"): void;
}>();

const cityStore = useCity();
const categoryStore = useCategory();
const { t } = useI18n();

const draft = ref<ListFilters>(emptyFilters());

// Har ochilganda joriy filtrlardan boshlaymiz; yopib qo'yilsa o'zgarishlar saqlanmaydi
watch(
   () => props.open,
   (open) => {
      if (open) draft.value = { ...emptyFilters(), ...toRawDeep(props.modelValue) };
   },
   { immediate: true },
);

/* ===== Jonli son: "12 ta e'lonni ko'rsatish" ===== */
const COUNT_DEBOUNCE_MS = 300;
const liveCount = ref<number | null>(null);
const isCounting = ref(false);
let countTimer: ReturnType<typeof setTimeout> | undefined;
let lastCountRequest = 0;

async function refreshCount() {
   if (!props.countScope) return;
   const requestId = ++lastCountRequest;
   isCounting.value = true;
   try {
      const { data } = await ProductRepo.count({ ...props.countScope, ...toQueryParams(draft.value) });
      // Kech kelgan eski javob yangisini bosib ketmasin
      if (requestId === lastCountRequest) liveCount.value = data.count;
   } catch (error) {
      console.error("Filter count failed.", error);
      if (requestId === lastCountRequest) liveCount.value = null;
   } finally {
      if (requestId === lastCountRequest) isCounting.value = false;
   }
}

watch(
   [draft, () => props.open, () => props.countScope],
   () => {
      clearTimeout(countTimer);
      if (!props.open || !props.countScope) return;
      isCounting.value = true;
      countTimer = setTimeout(refreshCount, COUNT_DEBOUNCE_MS);
   },
   { deep: true },
);

onBeforeUnmount(() => clearTimeout(countTimer));

const buttonLabel = computed(() => {
   if (!props.countScope || liveCount.value === null) return t("common.show");
   return liveCount.value === 0 ? t("filters.noneLeft") : t("filters.showCount", liveCount.value);
});

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

// Variant nomi foydalanuvchi tilida; filtrga esa asl (o'zbekcha) qiymat yuboriladi
function labelOf(parameter: IParameter, option: string): string {
   const index = optionsOf(parameter).indexOf(option);
   return parameter.option_labels?.[index] ?? option;
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
