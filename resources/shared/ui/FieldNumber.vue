<template>
   <Field :name="props.name" v-slot="{ field, handleChange }">
      <input
         type="number"
         class="field"
         @input="handleChange"
         v-bind="{ ...field, ...$attrs }"
         :placeholder="placeholder"
         :disabled="disabled"
      />
   </Field>
</template>

<script setup lang="ts">
import { Field } from "vee-validate";

const props = defineProps<{
   name: string;
   placeholder?: string;
   type?: string;
   disabled?: boolean;
   invalid?: boolean;
}>();
</script>
