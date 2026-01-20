<template>
   <section>
      <main class="px-5 py-2 border-b border-secondary">
         <BackPreviusPage :model="true" title="Elon joylash" @close="$emit('close')" />
      </main>
      <div>
         <h3 class="py-3 px-5 text-tertiary font-semibold mb-3 text-center flex justify-center items-center gap-2">
            Kategoriyani tanlang
         </h3>
         <template v-if="pageData.isImagesReady === false">
            <Skeleton height="40px" class="mb-2" v-for="n in 6" :key="n"></Skeleton>
         </template>
         <Accordion value="0" v-else>
            <AccordionPanel v-for="category in categories" :key="category.id" :value="category.id">
               <AccordionHeader class="py-3!">
                  <div class="flex items-center gap-4">
                     <img :src="category.image" class="w-6 dark:invert" />
                     {{ category.name }}
                  </div>
               </AccordionHeader>
               <AccordionContent>
                  <nav
                     v-for="child in category.children"
                     :key="child.id"
                     @click="selectCategory(child.id)"
                     class="first:mt-2 py-1 text-tertiary font-semibold cursor-pointer flex items-center justify-between border-b border-secondary last:border-0"
                  >
                     {{ child.name }} <i class="pi pi-angle-right text-xs!"></i>
                  </nav>
               </AccordionContent>
            </AccordionPanel>
         </Accordion>
      </div>
      <BottomDrawer v-model="isVisible">
         <main class="h-full">
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
      </BottomDrawer>
   </section>
</template>

<script setup lang="ts">
import BottomDrawer from "@/components/ui/BottomDrawer.vue";
import { PrimeVueInputs } from "@/modules/PrimeVueInputs";
import { productInputs, globalProps, ZodTypeMapping } from "./ProductInputs";
import { ICategory, InputConfig } from "@/types";
import { computed, onMounted, reactive } from "vue";
import CategoryRepo from "../Category/CategoryRepo";
import BaseForm from "@/components/BaseForm.vue";
import ProductRepo from "./ProductRepo";
import { Component } from "vue";
import BackPreviusPage from "@/components/BackPreviusPage.vue";
import { useFetchDecorator } from "@/modules/useFetch";
import { preloadImages } from "@/modules/Helpers";
const { data: categories, execute: fetchCategories } = useFetchDecorator<ICategory[]>(CategoryRepo.parents);

const pageData = reactive<{
   selectedCategory: ICategory | null;
   isImagesReady: boolean;
}>({
   selectedCategory: null,
   isImagesReady: false,
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
         class: latest ? [] : ["mb-3"],
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
