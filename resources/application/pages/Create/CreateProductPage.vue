<template>
   <section class="h-full">
      <main v-if="selectedCategory" class="h-full flex flex-col">
         <aside class="-mx-4 px-4 pb-3 border-b border-(--z-border)">
            <main :class="[hasFocusedInput ? 'max-h-0' : ' max-h-40']" class="transition-all overflow-hidden">
               <div class="flex gap-2 items-center justify-between">
                  <div class="flex items-center gap-2">
                     <h3 class="font-extrabold text-xl mb-1">E'lon joylash</h3>
                     <span class="text-xs text-(--z-muted-text) font-medium">2 / 2</span>
                  </div>

                  <ProductChangeDistrictModal
                     @district-changed="onDistrictChanged"
                     :selected-city-id="selectedCityId"
                  />
               </div>

               <!-- Segmented progress bar (ikkala etap ham to'lgan) -->
               <div class="mt-2.5 mb-2.5 flex flex-col gap-1.5">
                  <div class="grid grid-cols-2 gap-1.5 w-full">
                     <div class="h-1 bg-(--z-primary) rounded-full"></div>
                     <div class="h-1 bg-(--z-primary) rounded-full"></div>
                  </div>
                  <p class="text-xs text-(--z-muted-text) font-medium">E'lon ma'lumotlari</p>
               </div>
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
         <BaseForm v-if="formInputs.length" :submit="submitForm" @submit="onSubmit" :input-configs="formInputs" />
      </main>
      <ProductFormSkeleton v-else class="h-full" />
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
import { ICategory, InputConfig } from "@shared/types";
import { Component, computed, onMounted, ref, shallowRef } from "vue";
import { Inputs } from "@/modules/Inputs";
import { productInputs, ZodTypeMapping } from "@shared/entities/Product/ProductInputs";
import { useFetchDecorator } from "@shared/composables/useFetch";
import CategoryRepo from "@shared/entities/Category/CategoryRepo";
import { ChevronRight } from "lucide-vue-next";
import { useCity } from "@shared/entities/District/useCity";
import { useAuth } from "@shared/store/useAuth";

const cityStore = useCity();
const authStore = useAuth();
const route = useRoute();
const router = useRouter();
const { hasFocusedInput } = useFocusedInput();

const selectedCityId = ref<number | null>(null);

function onDistrictChanged(districtId: number) {
   selectedCityId.value = districtId;
   try {
      localStorage.setItem("last_selected_city", String(districtId));
   } catch (e) {}
}

const { data: category, execute: executeCategory } = useFetchDecorator<ICategory>(CategoryRepo.show);

const selectedCategory = ref<ICategory | null>(null);
const formInputs = shallowRef<InputConfig[]>([]);

async function selectCategory(category: ICategory) {
   const baseInputs = productInputs({
      withPrice: category.with_price !== false,
      withImage: category.with_image !== false,
      hasTelegramUsername: Boolean(authStore.user?.username),
      telegramUsername: authStore.user?.username,
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
      };
   });

   formInputs.value = [...baseInputs, ...customInputs];
   selectedCategory.value = category;
}

async function submitForm(values: any) {
   const payload: any = {
      title: values.title,
      phone: values.phone,
      back_color_id: values.back_color_id,
      description: values.description,
      price: values.price,
      price_type_id: values.price_type_id,
      category_id: route.params.categoryId,
      parameters: [],
      district_id: selectedCityId.value,
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

function onSubmit() {
   router.push({ name: "profile", query: { category_id: selectedCategory.value?.parent_id } });
}

onMounted(async () => {
   const categoryId = route.params.categoryId as string;
   await executeCategory(categoryId);

   if (category.value) {
      selectCategory(category.value);
   }

   const routeCity = route.params.cityId ? Number(route.params.cityId) : null;
   const storedCity = localStorage.getItem("last_selected_city");
   selectedCityId.value =
      routeCity || (authStore.user as any)?.active_district_id || (storedCity ? Number(storedCity) : 1);
});
</script>
