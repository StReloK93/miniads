<template>
   <section class="h-full">
      <main v-if="selectedCategory" class="h-full flex flex-col">
         <aside class="pb-4 border-b border-(--z-border) -mx-4 px-4">
            <h3 class="font-extrabold text-xl mb-1">E'lon joylash</h3>
            <p class="title text-xs">2-qadam: E'lon ma'lumotlari</p>
         </aside>
         <BaseForm
            v-if="selectedCategory"
            :submit="submitForm"
            @close="backToCategoryPage"
            @submit="backToCategoryPage"
            :input-configs="fullInputs"
         />
      </main>
      <main v-else class="h-full grid grid-rows-[auto_1fr]]">
         <aside class="flex flex-col justify-between">
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
            <main class="border-t border-(--z-border) h-28 p-4 pb-0 flex flex-col items-center -mx-4">
               <div class="skeleton h-12 rounded-2xl! mb-3 w-full"></div>
               <p class="skeleton h-3 w-4/5 mb-1"></p>
               <p class="skeleton h-3 w-2/5 mb-1"></p>
            </main>
         </aside>
      </main>
   </section>
</template>

<script setup lang="ts">
import BaseForm from "@shared/ui/BaseForm.vue";
import ProductRepo from "@shared/entities/Product/ProductRepo";
import { useRoute, useRouter } from "vue-router";
import { ICategory, InputConfig } from "@shared/types";
import { Component, onMounted, ref, shallowRef } from "vue";
import { Inputs } from "@/modules/Inputs";
import { productInputs, ZodTypeMapping } from "@shared/entities/Product/ProductInputs";
import CategoryRepo from "@shared/entities/Category/CategoryRepo";
import { useFetchDecorator } from "@shared/composables/useFetch";
const route = useRoute();
const router = useRouter();

const { data: category, execute: executeCategory } = useFetchDecorator<ICategory>(CategoryRepo.show);

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
      phone: values.phone,
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
   await executeCategory(categoryId);

   if (category.value) {
      selectCategory(category.value);
   }
});
</script>
