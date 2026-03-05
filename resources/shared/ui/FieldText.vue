<template>
   <Field :name="props.name" v-slot="{ field, handleChange }">
      <div class="field">
         <slot name="left"></slot>
         <input
            :type="props.type || 'text'"
            class="field_input"
            @input="handleChange"
            v-bind="{ ...field, ...$attrs }"
            :placeholder="placeholder"
            :disabled="disabled"
         />
         <slot name="right"></slot>
      </div>
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
