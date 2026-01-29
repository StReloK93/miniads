<template>
   <Form
      @submit="onSubmit"
      :validation-schema="validationSchema"
      :initialValues="initialValues"
      class="flex flex-col w-full h-full"
   >
      <slot name="header" />

      <div class="flex flex-col grow relative">
         <main class="absolute inset-0 overflow-y-auto py-3">
            <slot name="inputs" />
            <template v-for="input in inputConfigs" :key="input.name">
               <div :class="input.class">
                  <component :is="input.component" :name="input.name" v-bind="input.props" />

                  <ErrorMessage :name="input.name" v-slot="{ message }">
                     <p class="text-sm text-red-500 mt-1">
                        {{ message }}
                     </p>
                  </ErrorMessage>
               </div>
            </template>
         </main>
      </div>
      <footer class="flex gap-3 py-4 border-t border-(--color-border)">
         <BaseButton severity="secondary" class="w-full" @click="emit('close')"> Bekor qilish </BaseButton>
         <BaseButton type="submit" class="w-full" :loading="buttonLoader"> Saqlash </BaseButton>
      </footer>
   </Form>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { Form, ErrorMessage } from "vee-validate";
import { toTypedSchema } from "@vee-validate/zod";
import { z } from "zod";
import type { InputConfig } from "@shared/types";

const emit = defineEmits(["close"]);

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
   emit("close");
};
</script>
