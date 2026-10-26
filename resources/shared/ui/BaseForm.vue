<template>
   <Form
      ref="formRef"
      @submit="onSubmit"
      @input="onValuesUpdate"
      @change="onValuesUpdate"
      :validation-schema="validationSchema"
      :initialValues="initialValues"
      class="grid-rows-[1fr_auto] h-full grid -mx-4"
   >
      <main class="relative">
         <div ref="parentScroll" class="overflow-y-auto inset-0 absolute no-scrollbar px-4 py-3">
            <slot name="inputs" />
            <template v-for="input in inputConfigs" :key="input.name">
               <Teleport defer v-if="input.teleport_child_class" :to="`.${input.teleport_child_class}`">
                  <main :class="input.class" class="child">
                     <component :is="input.component" :name="input.name" v-bind="input.props" />
                  </main>
               </Teleport>
               <div v-else :class="input.class">
                  <div v-if="input.props?.title" class="flex items-center gap-1.5 mb-1.5 px-0.5 select-none">
                     <p class="text-xs font-semibold text-(--z-foreground) tracking-wide">
                        {{ input.props?.title }}
                     </p>
                     <span
                        v-if="input.optional || input.required === false"
                        class="text-[11px] font-normal text-(--z-muted-text)/80"
                     >
                        (ixtiyoriy)
                     </span>
                  </div>
                  <main class="relative" :class="input.teleport_parent_class">
                     <component
                        :is="input.component"
                        :name="input.name"
                        v-bind="input.props"
                        @focusin="centerElement($event.target, parentScroll!)"
                     />
                  </main>

                  <ErrorMessage :name="input.name" v-slot="{ message }">
                     <p class="text-sm text-(--z-danger) mt-1">
                        {{ message }}
                     </p>
                  </ErrorMessage>
               </div>
            </template>
         </div>
      </main>
      <footer
         :class="[hasFocusedInput ? 'max-h-0 py-0' : 'py-4  max-h-40 border-t']"
         class="flex flex-col gap-3 px-4 pb-0 border-(--z-border) overflow-hidden transition-all"
      >
         <BaseButton type="submit" class="w-full" :loading="buttonLoader">
            <template #icon>
               <CheckCircle class="w-5 h-5 mr-2" />
            </template>
            {{ submitLabel }}
         </BaseButton>
         <!-- <p class="text-xs px-4 leading-4.5 text-center text-(--z-muted-text)">
            Elon joylashtirish orqali siz foydalanish shartlariga rozilik bildirasiz.
         </p> -->
         <BaseButton
            :class="[hasFocusedInput ? 'bottom-4' : '-bottom-22']"
            type="button"
            class="absolute right-4 transition-all duration-300 delay-150"
            rounded
            icon-only
            :loading="buttonLoader"
         >
            <template #icon>
               <ChevronRight class="w-5 h-5" />
            </template>
         </BaseButton>
      </footer>
   </Form>
</template>

<script setup lang="ts">
import { useFocusedInput } from "@shared/composables/useFocusInput";
import { centerElement } from "@shared/modules/helpers";
import { ref, computed } from "vue";
import { Form, ErrorMessage } from "vee-validate";
import { toTypedSchema } from "@vee-validate/zod";
import type { InputConfig } from "@shared/types";
import { z } from "zod";
import { CheckCircle, ChevronRight } from "lucide-vue-next";

const { hasFocusedInput } = useFocusedInput();

const emit = defineEmits(["submit", "values-change"]);

const formRef = ref<any>(null);
const parentScroll = ref<HTMLElement>();

function onValuesUpdate() {
   if (formRef.value?.values) {
      emit("values-change", formRef.value.values);
   }
}

const props = defineProps<{
   inputConfigs: InputConfig[];
   submit: (values: unknown) => Promise<void>;
   submitLabel?: string;
   superRefine?: (values: Record<string, unknown>, ctx: z.RefinementCtx) => void;
}>();

const submitLabel = computed(() => props.submitLabel ?? "E'lonni joylash");
const buttonLoader = ref(false);
const initialValues = computed(() => Object.fromEntries(props.inputConfigs.map((i) => [i.name, i.value])));

const schema = computed(() => {
   const shape: Record<string, z.ZodTypeAny> = {};

   for (const input of props.inputConfigs) {
      if (input.schema) {
         shape[input.name] = input.schema;
      }
   }

   return z.object(shape).superRefine(props.superRefine ?? (() => {}));
});

const validationSchema = toTypedSchema(schema.value);

const onSubmit: any = async (values) => {
   buttonLoader.value = true;

   await props.submit(values).finally(() => {
      setTimeout(() => {
         buttonLoader.value = false;
      }, 500);
   });
   emit("submit", values);
};
</script>
