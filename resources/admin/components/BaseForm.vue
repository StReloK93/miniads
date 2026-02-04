<template>
   <Form
      v-slot="$form"
      ref="instance"
      :initial-values
      :resolver
      @submit="onFormSubmit"
      class="flex flex-col w-full h-full"
   >
      <slot name="header"></slot>

      <div class="flex flex-col grow relative">
         <main class="absolute inset-0 overflow-y-auto py-3 px-5">
            <slot name="inputs"></slot>
            <template v-for="(input, index) in inputConfigs" :key="index">
               <main :class="input.class">
                  <FloatLabel v-if="input.placeholder" variant="on">
                     <component
                        :input="input"
                        :is="input.component"
                        :name="input.name"
                        :id="input.name"
                        v-bind="input.props"
                     />
                     <label :for="input.name">{{ input.placeholder }}</label>
                  </FloatLabel>

                  <component v-else :is="input.component" :input="input" :name="input.name" v-bind="input.props" />

                  <Message v-if="$form[input.name]?.invalid" severity="error" size="small" variant="simple">
                     {{ $form[input.name].error.message }}
                  </Message>
               </main>
            </template>
         </main>
      </div>

      <footer class="flex gap-3 py-4 border-t border-secondary px-5">
         <Button
            type="button"
            size="small"
            variant="text"
            @click="emit('close')"
            severity="secondary"
            label="Bekor qilish"
            :fluid="true"
         />
         <Button type="submit" size="small" label="Saqlash" severity="contrast" :fluid="true" :loading="buttonLoader" />
      </footer>
   </Form>
</template>

<script setup lang="ts">
import { Form } from "@primevue/forms";
import Button from "primevue/button";
import Message from "primevue/message";
import FloatLabel from "primevue/floatlabel";
import type { FormSubmitEvent } from "@primevue/forms";
import { reactive, ref } from "vue";
import { zodResolver } from "@primevue/forms/resolvers/zod";
import { z } from "zod";
import type { InputConfig } from "@shared/types";
const instance = ref();

const emit = defineEmits(["close"]);

const buttonLoader = ref(false);

const props = defineProps<{
   inputConfigs: InputConfig[];
   submit: (values: unknown) => Promise<void>;
   superRefine?: (values: unknown, ctx: z.RefinementCtx) => void;
}>();

const onFormSubmit = async (formEvent: FormSubmitEvent) => {
   if (formEvent.valid) {
      buttonLoader.value = true;
      await props.submit(formEvent.values).finally(() => {
         buttonLoader.value = false;
      });

      emit("close");
   }
};

const initialValues = reactive(
   props.inputConfigs.reduce(
      (acc, curr) => {
         acc[curr.name] = curr.value;
         return acc;
      },
      {} as Record<string, unknown>,
   ),
);

const resolver = zodResolver(
   z
      .object(
         props.inputConfigs.reduce(
            (acc, curr) => {
               if (curr.schema) acc[curr.name] = curr.schema;
               return acc;
            },
            {} as Record<string, z.ZodTypeAny>,
         ),
      )
      .superRefine((props.superRefine || function () {}) as any),
);

defineExpose({ instance });
</script>
