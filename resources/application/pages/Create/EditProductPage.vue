<template>
   <section class="h-full">
      <main v-if="selectedCategory" class="h-full flex flex-col">
         <aside class="-mx-4 px-4 pb-4 border-b border-(--z-border)">
            <main :class="[hasFocusedInput ? 'max-h-0' : ' max-h-40']" class="transition-all overflow-hidden">
               <div class="flex gap-2 items-center justify-between">
                  <h3 class="font-extrabold text-xl mb-1">E'lonni tahrirlash</h3>
                  <ProductChangeDistrictModal
                     @district-changed="onDistrictChanged"
                     :selected-city-id="selectedCityId"
                  />
               </div>
               <p class="title text-xs mb-4">E'lon ma'lumotlari</p>
            </main>

            <main class="flex gap-1 items-center">
               <span
                  v-for="(category, index) in buildBreadcrumb(selectedCategory)"
                  :key="category.id"
                  class="text-sm font-bold"
               >
                  <ChevronRight v-if="index" class="size-3 font-semibold inline" /> {{ category.name }}
               </span>
            </main>
         </aside>
         <BaseForm
            v-if="formInputs.length"
            :submit="submitForm"
            @submit="onSubmit"
            :input-configs="formInputs"
            submit-label="E'lonni yangilash"
         />
      </main>
      <ProductFormSkeleton v-else />
   </section>
</template>

<script setup lang="ts">
import ProductFormSkeleton from "@/components/ProductFormSkeleton.vue";
import ProductChangeDistrictModal from "@components/ProductChangeDistrictModal.vue";
import { useFocusedInput } from "@shared/composables/useFocusInput";
import { buildBreadcrumb } from "@/modules/Helpers";
import BaseForm from "@shared/ui/BaseForm.vue";
import ProductRepo from "@shared/entities/Product/ProductRepo";
import { useRoute, useRouter } from "vue-router";
import { ICategory, InputConfig, IProduct } from "@shared/types";
import { Component, onMounted, ref, shallowRef } from "vue";
import { Inputs } from "@/modules/Inputs";
import { productInputs, ZodTypeMapping } from "@shared/entities/Product/ProductInputs";
import CategoryRepo from "@shared/entities/Category/CategoryRepo";
import { useFetchDecorator } from "@shared/composables/useFetch";
import { ChevronRight } from "lucide-vue-next";

const route = useRoute();
const router = useRouter();
const { hasFocusedInput } = useFocusedInput();

const { data: category, execute: executeCategory } = useFetchDecorator<ICategory>(CategoryRepo.show);

const props = defineProps<{
   product_id: string | number;
}>();

const selectedCityId = ref<number | null>(null);
const selectedCategory = ref<ICategory | null>(null);
const formInputs = shallowRef<InputConfig[]>([]);

async function selectCategory(category: ICategory, product: IProduct) {
   selectedCityId.value = product.district_id;

   const baseInputs = productInputs({
      withPrice: category.with_price !== false,
      withImage: category.with_image !== false,
   });

   await Promise.all(
      baseInputs.map(async (input) => {
         if (input.generateProps) await input.generateProps();
         return input;
      }),
   );

   const parameters = category.parameters || [];
   const phoneInput = baseInputs.find((i) => i.name === "phone");
   if (phoneInput) {
      phoneInput.class = parameters.length ? ["mb-3"] : [];
   }

   const customInputs: InputConfig[] = parameters.map((parameter, index) => {
      const latest = parameters.length - 1 === index;
      return {
         component: Inputs[parameter.component] as Component,
         name: `parameter_${parameter.id}`,
         class: latest ? [] : ["mb-3"],
         props: {
            title: parameter.title,
            placeholder: parameter.placeholder,
            options: parameter.options || [],
            inputmode: parameter.type === "number" ? "numeric" : undefined,
         },
         schema: ZodTypeMapping[parameter.type](parameter.pivot.is_required),
         value: product.parameter_values?.find((p: any) => p.parameter_id === parameter.id)?.value || "",
      };
   });

   baseInputs.forEach((input) => {
      if ((product as any)[input.name] !== undefined) {
         input.value = (product as any)[input.name];
      }
   });

   formInputs.value = [...baseInputs, ...customInputs];
   selectedCategory.value = category;
}

function onDistrictChanged(districtId: number) {
   selectedCityId.value = districtId;
}

async function submitForm(values: any) {
   const payload: any = {
      title: values.title,
      phone: values.phone,
      back_color_id: values.back_color_id,
      description: values.description,
      price: values.price,
      price_type_id: values.price_type_id,
      category_id: selectedCategory.value?.id,
      parameters: [],
      images: values.images,
      district_id: selectedCityId.value,
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

   await ProductRepo.update(props.product_id, payload);
}

function onSubmit() {
   router.push({ name: "profile", query: { category_id: selectedCategory.value?.parent_id } });
}

onMounted(async () => {
   const { data: product } = await ProductRepo.edit(props.product_id);
   if (product.category_id) {
      executeCategory(product.category_id).then(() => {
         if (category.value) {
            selectCategory(category.value, product);
         }
      });
   }
});
</script>
