<template>
   <section class="pt-safe-top">
      <main class="-mx-5 px-5 py-2 border-b border-secondary">
         <BackPreviusPage :model="true" title="Bosh sahifaga qaytish" @close="$emit('close')" />
      </main>
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
         <main class="h-full -mx-5">
            <BaseForm :submit="submitForm" @close="isVisible = false" :input-configs="fullInputs" />
         </main>
      </Drawer>
   </section>
</template>

<script setup lang="ts">
import { PrimeVueInputs } from "@/modules/PrimeVueInputs";
import { productInputs, globalProps, ZodTypeMapping } from "./ProductInputs";
import { ICategory, InputConfig } from "@/types";
import { computed, onMounted, reactive } from "vue";
import CategoryRepo from "../Category/CategoryRepo";
import BaseForm from "@/components/BaseForm.vue";
import ProductRepo from "./ProductRepo";
import { Component } from "vue";
import BackPreviusPage from "@/components/BackPreviusPage.vue";
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

   const parameterInputs = parameters.map((parameter, index) => {
      const latest = parameters.length - 1 === index;
      return {
         component: PrimeVueInputs[parameter.component] as Component,
         name: `parameter_${parameter.id}`,
         placeholder: parameter.placeholder,
         class: latest ? [] : ["mb-4"],
         props: {
            ...globalProps,
            options: parameter.options || [],
         },
         schema: ZodTypeMapping[parameter.type](parameter.pivot.is_required, parameter.placeholder),
      };
   });

   fullInputs = [...productInputs, ...parameterInputs];
   pageData.selectedCategory = category;
}

async function submitForm(values: any) {
   const payload: any = {
      title: values.title,
      description: values.description,
      category_id: pageData.selectedCategory?.id,
      parameters: [],
      images: values.images,
   };

   Object.keys(values).forEach((key) => {
      if (key.startsWith("parameter_")) {
         const paramId = key.replace("parameter_", "");
         payload.parameters.push({
            id: paramId,
            value: values[key],
         });
      }
   });

   await ProductRepo.store(payload);
}

onMounted(() => {});
const isVisible = computed({
   get: () => !!pageData.selectedCategory, // Agar null bo'lmasa true qaytaradi
   set: (value) => {
      if (!value) pageData.selectedCategory = null; // Modal yopilganda datani null qiladi
   },
});
</script>
