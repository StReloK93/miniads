<template>
   <!--
      Yangi vitrina — bosqichma-bosqich: turi → asosiy ma'lumot (logo, muqova, nomi...) → bog'lanish → ko'rinish.
      Maydonlar e'lon formasidagi komponentlar bilan (BaseFormFields); har bosqich o'z maydonlarini tekshiradi.
   -->
   <section class="grid h-full grid-rows-[auto_1fr_auto] -mx-4">
      <header class="px-4 pt-1 pb-3">
         <div class="flex gap-1.5" aria-hidden="true">
            <span v-for="(_, index) in STEPS" :key="index" class="step-bar" :class="{ 'is-done': index <= step }" />
         </div>
         <p class="mt-3 text-[11px] font-semibold uppercase tracking-wider text-(--z-muted-text)">
            {{ $t("vitrina.steps.counter", { n: step + 1, total: STEPS.length }) }}
         </p>
         <h3 class="mt-0.5 text-lg font-bold leading-tight">{{ $t(`vitrina.steps.${current.key}.title`) }}</h3>
         <p class="mt-0.5 text-xs leading-snug text-(--z-muted-text)">{{ $t(`vitrina.steps.${current.key}.hint`) }}</p>
      </header>

      <main class="relative">
         <!-- pt-1: tanlangan kartaning tashqi ramkasi (box-shadow) tepada qirqilmasin -->
         <div ref="scroller" class="absolute inset-0 overflow-y-auto no-scrollbar px-4 pt-1 pb-4">
            <VitrinaTypePicker v-if="current.key === 'type'" v-model="type" />
            <VitrinaLayoutPicker v-else-if="current.key === 'look'" v-model="layout" :type="type" />
            <template v-else>
               <!-- Asosiy ma'lumot: avval logo va muqova, keyin nomi, tavsifi, shahri -->
               <VitrinaImagesPicker
                  v-if="current.key === 'main'"
                  class="mb-3"
                  :logo="logo"
                  :cover="cover"
                  @pick="(field, file) => emit('pick', field, file)"
                  @remove="(field) => emit('remove', field)"
               />
               <BaseFormFields :input-configs="stepInputs" :scroll-parent="scroller" />
            </template>
         </div>
      </main>

      <footer class="flex flex-col gap-2.5 border-t border-(--z-border) px-4 pt-3 pb-1">
         <p v-if="error" class="text-center text-sm text-(--z-danger)" role="alert">{{ error }}</p>
         <div class="flex gap-2.5">
            <BaseButton v-if="step > 0" severity="glass" class="shrink-0" @click="step--">
               <template #icon><ChevronLeft class="size-4" /></template>
               {{ $t("common.back") }}
            </BaseButton>
            <BaseButton class="grow" :loading="saving" @click="next">
               {{ isLast ? $t("vitrina.create") : $t("vitrina.steps.next") }}
               <template v-if="!isLast" #icon><ChevronRight class="size-4 order-last" /></template>
            </BaseButton>
         </div>
      </footer>
   </section>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/zod";
import { z } from "zod";
import { ChevronLeft, ChevronRight } from "lucide-vue-next";
import BaseButton from "@shared/ui/BaseButton.vue";
import BaseFormFields from "@shared/ui/BaseFormFields.vue";
import { t } from "@shared/i18n";
import { reportRequestError } from "@shared/modules/clientErrors";
import type { InputConfig, VitrinaType } from "@shared/types";
import VitrinaTypePicker from "@/features/vitrina/VitrinaTypePicker.vue";
import VitrinaLayoutPicker from "@/features/vitrina/VitrinaLayoutPicker.vue";
import VitrinaImagesPicker from "@/features/vitrina/VitrinaImagesPicker.vue";

const props = defineProps<{
   /** Sahifadagi ma'lumot maydonlari (tahrirlash formasi bilan bir xil) */
   inputs: InputConfig[];
   logo: string | null;
   cover: string | null;
   submit: (values: Record<string, unknown>) => Promise<void>;
}>();
const emit = defineEmits<{ (e: "pick", field: "logo" | "cover", file: File): void; (e: "remove", field: "logo" | "cover"): void }>();

const type = defineModel<VitrinaType>("type", { required: true });
const layout = defineModel<"grid" | "list" | null>("layout", { required: true });

/** Bosqichlar va ulardagi maydonlar (nomi bo'yicha sahifadagi InputConfig'dan olinadi) */
const STEPS = [
   { key: "type", fields: [] },
   { key: "main", fields: ["name", "description", "district_id"] },
   { key: "contact", fields: ["phone", "telegram", "instagram", "address", "work_hours"] },
   { key: "look", fields: [] },
] as const;

const step = ref(0);
const current = computed(() => STEPS[step.value]);
const isLast = computed(() => step.value === STEPS.length - 1);
const stepInputs = computed(() => props.inputs.filter((input) => (current.value.fields as readonly string[]).includes(input.name)));

const scroller = ref<HTMLElement | null>(null);
const saving = ref(false);
const error = ref("");

// Bitta forma butun bosqichlar uchun: ko'rinmay qolgan maydon qiymati saqlanib qoladi
const { validateField, values } = useForm({
   validationSchema: toTypedSchema(z.object(Object.fromEntries(props.inputs.filter((i) => i.schema).map((i) => [i.name, i.schema!])))),
   initialValues: Object.fromEntries(props.inputs.map((input) => [input.name, input.value])),
   keepValuesOnUnmount: true,
});

watch(step, () => {
   error.value = "";
   scroller.value?.scrollTo({ top: 0 });
});

async function next() {
   const results = await Promise.all(current.value.fields.map((field) => validateField(field)));
   if (results.some((result) => !result.valid)) return;
   if (!isLast.value) {
      step.value++;
      return;
   }

   saving.value = true;
   error.value = "";
   try {
      await props.submit({ ...values });
   } catch (e: any) {
      reportRequestError(e, "Vitrina yaratilmadi");
      error.value = e?.response?.data?.message || t("common.saveError");
   } finally {
      saving.value = false;
   }
}
</script>

<style scoped>
.step-bar {
   height: 0.1875rem;
   flex: 1;
   border-radius: 999px;
   background: var(--z-border);
   transition: background-color var(--duration) var(--ease-soft);

   &.is-done {
      background: var(--z-primary);
   }
}
</style>
