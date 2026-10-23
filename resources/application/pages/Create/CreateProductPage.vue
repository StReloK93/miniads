<template>
   <section class="h-full">
      <main v-if="selectedCategory" class="h-full flex flex-col">
         <aside class="-mx-4 px-4 pb-3 border-b border-(--z-border)">
            <main :class="[hasFocusedInput ? 'max-h-0' : ' max-h-40']" class="transition-all overflow-hidden">
               <div class="flex items-center gap-2">
                  <h3 class="font-extrabold text-xl mb-1">E'lon joylash</h3>
                  <span class="text-xs text-(--z-muted-text) font-medium">2 / 2</span>
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

         <BaseForm
            v-if="formInputs.length"
            :submit="submitForm"
            @submit="onSubmit"
            :input-configs="formInputs"
         />
      </main>
      <ProductFormSkeleton v-else class="h-full" />

      <!-- Success Modal -->
      <BaseModal
         :open="showSuccessModal"
         title="E'loningiz qabul qilindi!"
         description="E'loningiz muvaffaqiyatli joylandi va Telegram kanalda e'lon qilinadi."
         :show-buttons="false"
         @close="viewCreatedProduct"
      >
         <template #icon>
            <div class="size-10 rounded-2xl bg-emerald-500/15 text-emerald-500 flex items-center justify-center">
               <CheckCircle2 class="size-6 text-emerald-500" />
            </div>
         </template>

         <div class="flex flex-col gap-3 mt-2 select-none">
            <div
               v-if="createdProduct?.title"
               class="p-3 bg-(--z-muted)/40 border border-(--z-border) rounded-xl flex flex-col gap-1"
            >
               <span class="text-xs text-(--z-muted-text)">E'lon sarlavhasi:</span>
               <p class="text-sm font-semibold text-(--z-foreground) line-clamp-1">
                  {{ createdProduct.title }}
               </p>
               <span v-if="createdProduct.id" class="text-[11px] text-(--z-muted-text)">
                  Raqami: <strong class="text-(--z-primary)">#{{ createdProduct.id }}</strong>
               </span>
            </div>

            <div class="grid grid-cols-1 gap-2 pt-1">
               <BaseButton @click="viewCreatedProduct" class="w-full">
                  <template #icon>
                     <Eye class="size-4 mr-2" />
                  </template>
                  E'lonni ko'rish
               </BaseButton>

               <BaseButton @click="createAnother" severity="secondary" class="w-full">
                  <template #icon>
                     <Plus class="size-4 mr-2" />
                  </template>
                  Yana e'lon berish
               </BaseButton>

               <BaseButton @click="goToProfile" severity="glass" class="w-full">
                  <template #icon>
                     <User class="size-4 mr-2" />
                  </template>
                  Mening e'lonlarim
               </BaseButton>
            </div>
         </div>
      </BaseModal>
   </section>
</template>

<script setup lang="ts">
import ProductFormSkeleton from "@/components/ProductFormSkeleton.vue";
import { useFocusedInput } from "@shared/composables/useFocusInput";
import { buildBreadcrumb } from "@/modules/Helpers";
import BaseForm from "@shared/ui/BaseForm.vue";
import BaseModal from "@shared/ui/BaseModal.vue";
import BaseButton from "@shared/ui/BaseButton.vue";
import ProductRepo from "@shared/entities/Product/ProductRepo";
import { useRoute, useRouter } from "vue-router";
import { ICategory, InputConfig } from "@shared/types";
import { Component, computed, onMounted, ref, shallowRef } from "vue";
import { Inputs } from "@/modules/Inputs";
import { productInputs, ZodTypeMapping } from "@shared/entities/Product/ProductInputs";
import { useFetchDecorator } from "@shared/composables/useFetch";
import CategoryRepo from "@shared/entities/Category/CategoryRepo";
import { ChevronRight, CheckCircle2, Eye, Plus, User } from "lucide-vue-next";
import { useCity } from "@shared/entities/District/useCity";
import { useAuth } from "@shared/store/useAuth";

const cityStore = useCity();
const authStore = useAuth();
const route = useRoute();
const router = useRouter();
const { hasFocusedInput } = useFocusedInput();

const selectedCityId = ref<number | null>(null);

const { data: category, execute: executeCategory } = useFetchDecorator<ICategory>(CategoryRepo.show);

const selectedCategory = ref<ICategory | null>(null);
const formInputs = shallowRef<InputConfig[]>([]);

const showSuccessModal = ref(false);
const createdProduct = ref<{ id?: number | string; title?: string } | null>(null);

async function selectCategory(category: ICategory) {
   const baseInputs = productInputs({
      withPrice: category.with_price !== false,
      withImage: category.with_image !== false,
      hasTelegramUsername: Boolean(authStore.user?.username),
      telegramUsername: authStore.user?.username,
      districts: cityStore.cities || undefined,
      defaultDistrictId: selectedCityId.value,
      categoryName: category.name,
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
      const paramName = `parameter_${parameter.id}`;
      return {
         component: Inputs[parameter.component] as Component,
         name: paramName,
         class: latest ? [] : ["mb-3"],
         required: false,
         optional: true,
         value: undefined,
         props: {
            title: parameter.title,
            placeholder: parameter.placeholder,
            options: parameter.options || [],
            inputmode: parameter.type === "number" ? "numeric" : undefined,
         },
         schema: ZodTypeMapping[parameter.type](false),
      };
   });

   formInputs.value = [...baseInputs, ...customInputs];
   selectedCategory.value = category;
}

async function submitForm(values: any) {
   const districtId = values.district_id !== undefined ? values.district_id : selectedCityId.value;
   const phoneValue = values.phone === "telegram" || !values.phone ? null : values.phone;

   const payload: any = {
      title: values.title,
      phone: phoneValue,
      back_color_id: values.back_color_id,
      description: values.description,
      price: values.price,
      price_type_id: values.price_type_id,
      category_id: route.params.categoryId,
      parameters: [],
      district_id: districtId ? Number(districtId) : null,
      images: values.images,
   };

   Object.keys(values).forEach((key) => {
      if (key.startsWith("parameter_")) {
         const paramId = key.replace("parameter_", "");
         const val = values[key];
         if (val !== null && val !== undefined && val !== "") {
            payload.parameters.push({
               id: paramId,
               value: val,
            });
         }
      }
   });

   const res = await ProductRepo.store(payload);
   createdProduct.value = {
      id: res.data?.id,
      title: values.title,
   };
}

function onSubmit() {
   showSuccessModal.value = true;
}

function viewCreatedProduct() {
   showSuccessModal.value = false;
   if (createdProduct.value?.id) {
      router.push({ name: "product-id", params: { id: String(createdProduct.value.id) } });
   } else {
      router.push({ name: "profile" });
   }
}

function createAnother() {
   showSuccessModal.value = false;
   router.push({ name: "create-select-category" });
}

function goToProfile() {
   showSuccessModal.value = false;
   router.push({ name: "profile", query: { category_id: selectedCategory.value?.parent_id } });
}

onMounted(async () => {
   try {
      Object.keys(localStorage)
         .filter((k) => k.startsWith("miniads_draft_"))
         .forEach((k) => localStorage.removeItem(k));
   } catch {
      // ignore
   }

   const routeCity = route.params.cityId ? Number(route.params.cityId) : null;
   const storedCity = localStorage.getItem("last_selected_city");
   selectedCityId.value =
      routeCity || (authStore.user as any)?.active_district_id || (storedCity ? Number(storedCity) : 1);

   if (!cityStore.cities?.length) {
      await cityStore.getCities();
   }

   const categoryId = route.params.categoryId as string;
   await executeCategory(categoryId);

   if (category.value) {
      selectCategory(category.value);
   }
});
</script>
