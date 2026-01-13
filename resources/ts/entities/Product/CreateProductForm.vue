<template>
   <section>
      <div class="pt-4">
         <main v-for="category in categories" :key="category.id">
            <nav class="py-1 text-secondary">
               {{ category.name }}
            </nav>
            <ul>
               <li
                  v-for="child in category.children"
                  :key="child.id"
                  @click="selectCategory(child.id)"
                  class="pl-4 py-1 text-primary-500 cursor-pointer hover:underline"
               >
                  {{ child.name }}
               </li>
            </ul>
         </main>
      </div>
      <Drawer
         v-model:visible="isVisible"
         position="bottom"
         style="height: 80%"
         class="headless-drawer"
         :show-close-icon="false"
      >
         <BaseForm :submit="submitForm" :input-configs="fullInputs" />
      </Drawer>
   </section>
</template>

<script setup lang="ts">
import { PrimeVueInputs } from "@/modules/PrimeVueInputs";
import { productInputs, globalProps, schemaProps } from "./ProductInputs";
import { ICategory, InputConfig } from "@/types";
import { computed, onMounted, reactive } from "vue";
import CategoryRepo from "../Category/CategoryRepo";
import BaseForm from "@/components/BaseForm.vue";
// import ProductRepo from "./ProductRepo";
import { Component } from "vue";
const { data: categories } = CategoryRepo.parents();

const pageData = reactive<{
   selectedCategory: ICategory | null;
}>({
   selectedCategory: null,
});
var fullInputs: InputConfig[] = [];
async function selectCategory(id: string) {
   const { data: category } = await CategoryRepo.show(id);

   const parameters = category.parameters;

   const parameterInputs = parameters.map((parameter) => {
      return {
         component: PrimeVueInputs[parameter.type] as Component,
         name: `parameters_${parameter.id}`,
         placeholder: parameter.placeholder,
         class: ["mb-4"],
         props: {
            ...globalProps,
            options: parameter.options || [],
         },
         schema: parameter.pivot.is_required ? schemaProps.required : schemaProps.optional,
      };
   });

   fullInputs = [...productInputs, ...parameterInputs];
   console.log(fullInputs);

   pageData.selectedCategory = category;
}

async function submitForm(values: any) {
   console.log("Form submitted with values:", values);
   // await ProductRepo.store(values);
   // Formani yuborish logikasi shu yerda bo'ladi
}

onMounted(() => {});
const isVisible = computed({
   get: () => !!pageData.selectedCategory, // Agar null bo'lmasa true qaytaradi
   set: (value) => {
      if (!value) pageData.selectedCategory = null; // Modal yopilganda datani null qiladi
   },
});
</script>
