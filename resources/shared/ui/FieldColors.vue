<template>
   <Field :name="name" v-slot="{ value, handleChange }">
      <div class="relative flex w-full gap-1 flex-wrap justify-between">
         <button
            type="button"
            v-for="item in items"
            :key="item.id"
            @click="handleChange(item.id)"
            class="size-11 border-2 border-(--z-border) rounded-full p-0.5 transition-all duration-200 cursor-pointer active:scale-95"
            :style="{ borderColor: value === item.id ? item.border : '' }"
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
