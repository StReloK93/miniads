<template>
   <Form
      @submit="onSubmit"
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
                  <p v-if="input.props?.title" class="mb-1 text-sm text-(--z-muted-text) tracking-wide">
                     {{ input.props?.title }}
                  </p>
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
            E'lonni joylash
         </BaseButton>
         <p class="text-xs px-4 leading-4.5 text-center text-(--z-muted-text)">
            Elon joylashtirish orqali siz foydalanish shartlariga rozilik bildirasiz.
         </p>
      </footer>
   </Form>
</template>

<script setup lang="ts">
import { useFocusedInput } from "@shared/composables/useFocusInput";
const { hasFocusedInput } = useFocusedInput();

import { centerElement } from "@/modules/Helpers";
import { ref, computed } from "vue";
import { Form, ErrorMessage } from "vee-validate";
import { toTypedSchema } from "@vee-validate/zod";
import type { InputConfig } from "@shared/types";
import { z } from "zod";
import { CheckCircle } from "lucide-vue-next";

const emit = defineEmits(["submit"]);

const parentScroll = ref<HTMLElement>();

const props = defineProps<{
   inputConfigs: InputConfig[];
   submit: (values: unknown) => Promise<void>;
   superRefine?: (values: Record<string, unknown>, ctx: z.RefinementCtx) => void;
}>();

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
