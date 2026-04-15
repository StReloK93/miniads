<template>
   <Field :name="name" v-slot="{ value, handleChange }">
      <div class="relative flex w-full gap-1">
         <button
            type="button"
            v-for="item in items"
            :key="item.id"
            @click="handleChange(item.id)"
            class="w-10 h-10 border-2 rounded-full p-0.5 transition-colors duration-200"
            :class="[value === item.id ? item.border : 'border-(--z-border)']"
         >
            <span
               class="inline-block w-full h-full rounded-full"
               :style="{
                  backgroundImage: item.gradient,
               }"
            />
         </button>
      </div>
   </Field>
</template>

<script setup lang="ts">
import { Field } from "vee-validate";

type Item = { id: number; gradient: string; border: string };

defineProps<{
   name: string;
   items: Item[];
}>();
</script>
