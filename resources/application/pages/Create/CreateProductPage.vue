<template>
   <section class="grid grid-rows-[auto_1fr] gap-4 h-full">
      <BackPreviusPage title="Ortga qaytish" model @close="backToCategoryPage" />
      <BaseForm
         v-if="selectedCategory"
         :submit="submitForm"
         @close="backToCategoryPage"
         @submit="backToCategoryPage"
         :input-configs="fullInputs"
      >
      </BaseForm>
   </section>
</template>

<script setup lang="ts">
import BaseForm from "@shared/ui/BaseForm.vue";
import ProductRepo from "@shared/entities/Product/ProductRepo";
import BackPreviusPage from "@/components/BackPreviusPage.vue";
import { useRoute, useRouter } from "vue-router";
import { ICategory, InputConfig } from "@shared/types";
import { Component, onMounted, ref } from "vue";
import { Inputs } from "@/modules/Inputs";
import { productInputs, ZodTypeMapping } from "@shared/entities/Product/ProductInputs";
import CategoryRepo from "@shared/entities/Category/CategoryRepo";

const route = useRoute();
const router = useRouter();

var fullInputs: InputConfig[] = [];
const selectedCategory = ref<ICategory | null>(null);

function selectCategory(category: ICategory) {
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
