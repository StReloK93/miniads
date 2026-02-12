<template>
   <section class="grid grid-rows-[auto_1fr] gap-4 relative w-full h-dvh">
      <BackPreviusPage title="Yangi e'lon" />
      <aside>
         <CategorySelector
            v-if="CategoryStore.parentCategories"
            :categories="CategoryStore.parentCategories"
            @select-category="selectCategory"
         />
         <Transition name="slide-bottom" mode="out-in">
            <main class="absolute inset-0 bg-white flex flex-col" v-if="selectedCategory">
               <main class="px-4 pt-4">
                  <BackPreviusPage title="Yangi e'lon" @close="selectedCategory = null" :model="true" />
               </main>
               <BaseForm
                  :submit="submitForm"
                  @close="isVisible = false"
                  @submit="$router.back()"
                  :input-configs="fullInputs"
                  class="grow"
               >
                  <template #header>
                     <div
                        class="flex items-center gap-4 px-5 py-3 text-tertiary font-semibold border-b border-secondary"
                     >
                        <span>
                           {{ selectedCategory?.name }}
                        </span>
                     </div>
                  </template>
               </BaseForm>
            </main>
         </Transition>
      </aside>
   </section>
</template>

<script setup lang="ts">
import CategorySelector from "@components/CategorySelector.vue";
import BaseForm from "@shared/ui/BaseForm.vue";
import { Inputs } from "@/modules/Inputs";
import { productInputs, ZodTypeMapping } from "@shared/entities/Product/ProductInputs";
import { ICategory, InputConfig } from "@shared/types";
import { computed, ref } from "vue";
import CategoryRepo from "@shared/entities/Category/CategoryRepo";
import ProductRepo from "@shared/entities/Product/ProductRepo";
import { Component } from "vue";
import BackPreviusPage from "@/components/BackPreviusPage.vue";

import { useCategory } from "@shared/entities/Category/useCategory";

const CategoryStore = useCategory();

const selectedCategory = ref<ICategory | null>(null);

var fullInputs: InputConfig[] = [];

async function selectCategory(cat: ICategory) {
   const { data: category } = await CategoryRepo.show(cat.id);

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
   selectedCategory.value = category;
}

async function submitForm(values: any) {
   const payload: any = {
      title: values.title,
      description: values.description,
      category_id: selectedCategory.value?.id,
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

const isVisible = computed({
   get: () => !!selectedCategory.value, // Agar null bo'lmasa true qaytaradi
   set: (value) => {
      if (!value) selectedCategory.value = null; // Modal yopilganda datani null qiladi
   },
});
</script>
