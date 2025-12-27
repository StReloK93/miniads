<template>
   <div class="card flex flex-col items-center gap-5">
      <Form
         v-slot="$form"
         :initialValues
         :resolver="resolver"
         @submit="onFormSubmit"
         class="flex flex-col gap-4 w-full sm:w-56"
      >
         <div class="flex flex-col gap-1">
            <template v-for="(input, index) in inputConfig" :key="index">
               <component :name="input.props.name" v-bind="input.props" :is="input.component" />
               <Message v-if="$form[input.props.name]?.invalid" severity="error" size="small" variant="simple">{{
                  $form[input.props.name].error.message
               }}</Message>
            </template>
         </div>
         <Button type="submit" severity="secondary" label="Submit" />
      </Form>
   </div>
</template>

<script setup>
import { Form } from "@primevue/forms";
import { ref } from "vue";
import { zodResolver } from "@primevue/forms/resolvers/zod";
import { z } from "zod";
import { PrimeVueInputs } from "@/configs/PrimeVueInputs";

const inputConfig = [
   {
      component: PrimeVueInputs["InputText"],
      props: { name: "username", placeholder: "Username", fluid: true },
   },
   { component: PrimeVueInputs["InputNumber"], props: { name: "age", placeholder: "Age", fluid: true } },
   {
      component: PrimeVueInputs["Select"],
      props: { name: "tags", placeholder: "Tags", fluid: true, options: ["lolo", "lala", "pepe"] },
   },
   {
      component: PrimeVueInputs["SelectButton"],
      props: { name: "who", placeholder: "Tags", fluid: true, options: ["lolo", "lala", "pepe"] },
   },
];

const initialValues = ref({
   username: "",
   age: null,
   tags: [],
});

const resolver = ref(
   zodResolver(
      z.object({
         username: z.string().min(1, { message: "Username is required via Zod." }),
         age: z.number().min(1, { message: "Age is required via Zod." }),
         tags: z.string().min(1, { message: "At least one tag is required via Zod." }),
      }),
   ),
);

const onFormSubmit = ({ valid, values }) => {
   if (valid) {
      console.log(values);
   }
};
</script>
