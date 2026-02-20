<template>
   <section class="h-full">
      <section v-if="selectedCategory" class="grid grid-rows-[auto_1fr] gap-4 h-full -mx-4 relative">
         <BackPreviusPage title="Ortga qaytish" class="px-4" model @close="backToCategoryPage" />
         <BaseForm
            v-if="selectedCategory"
            :submit="submitForm"
            @close="backToCategoryPage"
            @submit="backToCategoryPage"
            :input-configs="fullInputs"
         />
      </section>
      <main v-else class="grid grid-rows-[auto_1fr] gap-4 w-full h-full">
         <aside class="flex justify-between items-center">
            <div class="skeleton w-10 h-10 rounded-full!"></div>
            <div class="skeleton w-32 h-5 rounded-full!"></div>
            <div class="w-10"></div>
         </aside>
         <aside class="pt-5 flex flex-col justify-between">
            <main class="relative grow">
               <article class="absolute inset-0 overflow-y-auto no-scrollbar">
                  <div class="skeleton w-16 h-3 mb-2"></div>
                  <div class="skeleton aspect-video mb-4"></div>

                  <div class="skeleton w-16 h-3 mb-1"></div>
                  <div class="skeleton h-12 mb-4.5"></div>

                  <div class="skeleton w-16 h-3 mb-1"></div>
                  <div class="skeleton h-12 mb-4.5"></div>

                  <div class="skeleton w-16 h-3 mb-1"></div>
                  <div class="skeleton h-17 mb-4"></div>

                  <div class="skeleton w-16 h-3 mb-1"></div>
                  <div class="skeleton h-12 mb-4"></div>
               </article>
            </main>
            <main class="border-t border-(--z-color-border) h-28 p-4 pb-0 flex flex-col items-center -mx-4">
               <div class="skeleton h-12 rounded-2xl! mb-3 w-full"></div>
               <p class="skeleton h-3 w-4/5 mb-1"></p>
               <p class="skeleton h-3 w-2/5"></p>
            </main>
         </aside>
      </main>
   </section>
</template>

<script setup lang="ts">
import BaseForm from "@shared/ui/BaseForm.vue";
import ProductRepo from "@shared/entities/Product/ProductRepo";
import BackPreviusPage from "@/components/BackPreviusPage.vue";
import { useRoute, useRouter } from "vue-router";
import { ICategory, InputConfig } from "@shared/types";
import { Component, onMounted, ref, shallowRef } from "vue";
import { Inputs } from "@/modules/Inputs";
import { productInputs, ZodTypeMapping } from "@shared/entities/Product/ProductInputs";
import CategoryRepo from "@shared/entities/Category/CategoryRepo";

const route = useRoute();
const router = useRouter();

var fullInputs: InputConfig[] = [];
const selectedCategory = ref<ICategory | null>(null);
const inputConfigs = shallowRef(productInputs);

async function selectCategory(category: ICategory) {
   await Promise.all(
      inputConfigs.value.map(async (input) => {
         if (input.generateProps) await input.generateProps();
         return input;
      }),
   );

   const parameters = category.parameters;

   const phoneInput = inputConfigs.value.at(-1);
   if (parameters.length) {
      phoneInput!.class = ["mb-3"];
   } else {
      phoneInput!.class = [];
   }
   const customInputs = parameters.map((parameter, index) => {
      const latest = parameters.length - 1 === index;

      return {
         component: Inputs[parameter.component] as Component,
         name: `parameter_${parameter.id}`,
         class: latest ? [] : ["mb-3"],

         props: {
            title: parameter.title,
            placeholder: parameter.placeholder,
            options: parameter.options || [],
         },
         schema: ZodTypeMapping[parameter.type](parameter.pivot.is_required),
      };
   });

   fullInputs = [...inputConfigs.value, ...customInputs];
   setTimeout(() => {
      selectedCategory.value = category;
   }, 100);
}

async function submitForm(values: any) {
   const payload: any = {
      title: values.title,
      description: values.description,
      price: values.price,
      price_type_id: values.price_type_id,
      category_id: route.params.categoryId,
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

function backToCategoryPage() {
   router.push({ name: "create-select-category", query: { category_id: selectedCategory.value?.parent_id } });
}

onMounted(async () => {
   const categoryId = route.params.categoryId as string;
   const { data: category } = await CategoryRepo.show(categoryId);

   if (category) {
      selectCategory(category);
   }
});
</script>
