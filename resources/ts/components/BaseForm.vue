<template>
   <Form
      v-slot="$form"
      ref="instance"
      :initial-values
      :resolver
      @submit="onFormSubmit"
      class="flex flex-col gap-4 w-full h-full pt-2"
   >
      <div class="flex flex-col">
         <main v-for="(input, index) in inputConfigs" :key="index" :class="[input.class]">
            <FloatLabel v-if="input.placeholder" variant="on">
               <component
                  :input="input"
                  :name="input.name"
                  :id="input.name"
                  v-bind="input.props"
                  :is="input.component"
               />
               <label :for="input.name">{{ input.placeholder }}</label>
            </FloatLabel>
            <component
               v-else
               :input="input"
               :name="input.name"
               v-bind="input.props"
               :is="input.component"
            />
            <Message
               v-if="$form[input.name]?.invalid"
               severity="error"
               size="small"
               variant="simple"
               >{{ $form[input.name].error.message }}
            </Message>
         </main>
      </div>
      <div class="grow" />
      <main class="flex gap-3 pb-4 -mb-4">
         <Button
            type="button"
            size="small"
            severity="secondary"
            @click="emit('close')"
            label="Bekor qilish"
            :fluid="true"
         />
         <Button type="submit" size="small" label="Saqlash" :fluid="true" :loading="buttonLoader" />
      </main>
   </Form>
</template>

<script setup lang="ts">
import type { FormSubmitEvent } from "@primevue/forms";
import { reactive, ref } from "vue";
import { zodResolver } from "@primevue/forms/resolvers/zod";
import { z } from "zod";
import type { InputConfig } from "@/types";
const instance = ref();

const emit = defineEmits(["close", "onSubmit"]);

const buttonLoader = ref(false);

const props = defineProps<{
   inputConfigs: InputConfig[];
   submit: (values: unknown) => Promise<void>;
}>();

const onFormSubmit = async (formEvent: FormSubmitEvent) => {
   if (formEvent.valid) {
      console.log(formEvent);

      buttonLoader.value = true;
      await props.submit(formEvent.values).finally(() => {
         buttonLoader.value = false;
      });

      emit("onSubmit");
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
   z.object(
      props.inputConfigs.reduce(
         (acc, curr) => {
            if (curr.schema) acc[curr.name] = curr.schema;
            return acc;
         },
         {} as Record<string, z.ZodTypeAny>,
      ),
   ),
);

defineExpose({ instance });
</script>
