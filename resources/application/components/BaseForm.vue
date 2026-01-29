<template>
   <Form @submit="onSubmit" class="flex flex-col w-full h-full">
      <slot name="header" />

      <div class="flex flex-col grow relative">
         <main class="absolute inset-0 overflow-y-auto py-3 px-5">
            <slot name="inputs" />
            <Field name="field" v-slot="{ field }">
               <!-- <component :is="input.component" v-bind="{ ...field }" /> -->
               <InputText v-bind="field" v-model="field.value" />
            </Field>
            <template v-for="input in inputConfigs" :key="input.name">
               <div :class="input.class">
                  <Field :name="input.name" v-slot="{ field }">
                     <component :is="input.component" v-bind="{ ...field, ...input.props }" />
                  </Field>

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
import { Form, Field, ErrorMessage, useForm } from "vee-validate";
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

/* -----------------------------
   Initial values
--------------------------------*/
const initialValues = computed(() => Object.fromEntries(props.inputConfigs.map((i) => [i.name, i.value])));

/* -----------------------------
   Zod schema (dynamic)
--------------------------------*/
const schema = computed(() => {
   const shape: Record<string, z.ZodTypeAny> = {};

   for (const input of props.inputConfigs) {
      if (input.schema) {
         shape[input.name] = input.schema;
      }
   }

   return z.object(shape).superRefine(props.superRefine ?? (() => {}));
});

const { handleSubmit } = useForm({
   initialValues: initialValues.value,
   validationSchema: toTypedSchema(schema.value),
});

const onSubmit: any = handleSubmit(async (values) => {
   buttonLoader.value = true;
   console.log(values);

   await props.submit(values).finally(() => {
      setTimeout(() => {
         buttonLoader.value = false;
      }, 500);
   });
   emit("close");
});
</script>
