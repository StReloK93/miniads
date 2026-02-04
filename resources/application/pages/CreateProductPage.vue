<template>
   <section class="pt-safe-top">
      <main class="p-(--space-md) border-b border-(--color-border)">
         <BackPreviusPage title="Yangi e'lon" />
      </main>
      <div>
         <AccordionGroup v-model="accordion">
            <AccordionItem
               v-for="category in categories"
               :key="category.id"
               :index="category.id"
               :title="category.name"
            >
               <button
                  v-for="child in category.children"
                  @click="selectCategory(child.id)"
                  :to="{ name: 'category-id', params: { id: child.id } }"
                  :key="child.id"
                  class="py-(--space-md) cursor-pointer flex items-center justify-between border-b border-(--color-border) last:border-0 w-full px-0!"
               >
                  {{ child.name }} <PlusCircleIcon class="size-5" />
               </button>
            </AccordionItem>
         </AccordionGroup>
      </div>
      <main class="h-100" v-if="pageData.selectedCategory">
         <BaseForm :submit="submitForm" @close="isVisible = false" :input-configs="fullInputs">
            <template #header>
               <div class="flex items-center gap-4 px-5 py-3 text-tertiary font-semibold border-b border-secondary">
                  <img :src="pageData.selectedCategory?.parent.image" class="w-6 dark:invert" />
                  <span>
                     {{ pageData.selectedCategory?.parent.name }}
                     <i class="pi pi-angle-right text-xs!"></i>
                     {{ pageData.selectedCategory?.name }}
                  </span>
               </div>
            </template>
         </BaseForm>
      </main>
   </section>
</template>

<script setup lang="ts">
import BaseForm from "@shared/ui/BaseForm.vue";
import { Inputs } from "@/modules/Inputs";
import { productInputs, ZodTypeMapping } from "@shared/entities/Product/ProductInputs";
import { ICategory, InputConfig } from "@shared/types";
import { computed, onMounted, reactive, ref } from "vue";
import CategoryRepo from "@shared/entities/Category/CategoryRepo";
import ProductRepo from "@shared/entities/Product/ProductRepo";
import { Component } from "vue";
import BackPreviusPage from "@/components/BackPreviusPage.vue";
import { useFetchDecorator } from "@shared/api/useFetch";
import { preloadImages } from "@/modules/Helpers";
import { PlusCircleIcon } from "@heroicons/vue/24/outline";
const { data: categories, execute: fetchCategories } = useFetchDecorator<ICategory[]>(CategoryRepo.parents);

const accordion = ref(null);

const pageData = reactive<{
   selectedCategory: ICategory | null;
   isImagesReady: boolean;
}>({
   selectedCategory: null,
   isImagesReady: false,
});
var fullInputs: InputConfig[] = [];
async function selectCategory(id: number) {
   const { data: category } = await CategoryRepo.show(id);
   const parameters = category.parameters;
   const customInputs = parameters.map((parameter, index) => {
      const latest = parameters.length - 1 === index;
      return {
         component: Inputs[parameter.component] as Component,
         name: `parameter_${parameter.id}`,
         class: latest ? [] : ["mb-3"],
         props: {
            placeholder: parameter.placeholder,
            options: parameter.options || [],
         },
         schema: ZodTypeMapping[parameter.type](parameter.pivot.is_required, parameter.placeholder),
      };
   });
   fullInputs = [...productInputs, ...customInputs];
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

onMounted(async () => {
   await fetchCategories();

   if (categories.value) {
      const imageUrls: string[] = categories.value.map((category) => category.image);
      await preloadImages(imageUrls);
      pageData.isImagesReady = true;
   }
});
const isVisible = computed({
   get: () => !!pageData.selectedCategory, // Agar null bo'lmasa true qaytaradi
   set: (value) => {
      if (!value) pageData.selectedCategory = null; // Modal yopilganda datani null qiladi
   },
});
</script>
