<template>
   <Form
      @submit="onSubmit"
      :validation-schema="validationSchema"
      :initialValues="initialValues"
      class="grid-rows-[auto_1fr_auto] h-full grid"
   >
      <main>
         <slot name="header" />
      </main>
      <main class="relative">
         <div class="overflow-y-auto inset-0 absolute no-scrollbar p-4">
            <slot name="inputs" />
            <template v-for="input in inputConfigs" :key="input.name">
               <div :class="input.class">
                  <label>
                     <p class="mb-1 text-xs text-(--color-text-secondary) tracking-wide">
                        {{ input.props?.title }}
                     </p>
                  </label>
                  <component :is="input.component" :name="input.name" v-bind="input.props" />

                  <ErrorMessage :name="input.name" v-slot="{ message }">
                     <p class="text-sm text-red-500 mt-1">
                        {{ message }}
                     </p>
                  </ErrorMessage>
               </div>
            </template>
         </div>
      </main>
      <footer class="flex flex-col gap-3 p-4 border-t border-(--color-border)">
         <BaseButton type="submit" class="w-full rounded-2xl!" :loading="buttonLoader">
            <template #icon>
               <CheckCircle class="w-5 h-5 mr-2" />
            </template>
            E'lonni joylash
         </BaseButton>
         <p class="text-xs px-4 leading-4.5 text-center text-(--color-text-secondary)">
            Elon joylashtirish orqali siz foydalanish shartlari va rozilik bildirasiz.
         </p>
      </footer>
   </Form>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { Form, ErrorMessage } from "vee-validate";
import { toTypedSchema } from "@vee-validate/zod";
import type { InputConfig } from "@shared/types";
import { z } from "zod";
import { CheckCircle } from "lucide-vue-next";

const emit = defineEmits(["close", "submit"]);

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
   emit("close");
};
</script>
