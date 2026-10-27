<template>
   <section class="h-full">
      <ProductFormShell :category="selectedCategory" :inputs="formInputs" :submit="submitForm" @submitted="onSubmit">
         <template #heading>
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
         </template>
      </ProductFormShell>

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
import ProductFormShell from "@/features/product/ProductFormShell.vue";
import BaseModal from "@shared/ui/BaseModal.vue";
import BaseButton from "@shared/ui/BaseButton.vue";
import ProductRepo from "@shared/entities/Product/ProductRepo";
import { useRoute, useRouter } from "vue-router";
import { ICategory, InputConfig } from "@shared/types";
import { onMounted, ref, shallowRef } from "vue";
import { buildProductFormInputs, buildProductPayload } from "@/features/product/useProductForm";
import CategoryRepo from "@shared/entities/Category/CategoryRepo";
import { CheckCircle2, Eye, Plus, User } from "lucide-vue-next";
import { useAuth } from "@shared/store/useAuth";

const authStore = useAuth();
const route = useRoute();
const router = useRouter();

const selectedCityId = ref<number | null>(null);
const selectedCategory = ref<ICategory | null>(null);
const formInputs = shallowRef<InputConfig[]>([]);

const showSuccessModal = ref(false);
const createdProduct = ref<{ id?: number | string; title?: string } | null>(null);

async function submitForm(values: Record<string, any>) {
   const payload = buildProductPayload(values, {
      categoryId: route.params.categoryId as string,
      fallbackDistrictId: selectedCityId.value,
   });

   const res = await ProductRepo.store(payload);
   // Server birinchi raqamni profilga yozdi — keyingi e'lon formasi shu sessiyada ham to'ldirilsin
   if (authStore.user && !authStore.user.phone && payload.phone) {
      authStore.user.phone = payload.phone;
   }
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
      router.push({ name: "my-ads" });
   }
}

function createAnother() {
   showSuccessModal.value = false;
   router.push({ name: "create-select-category" });
}

function goToProfile() {
   showSuccessModal.value = false;
   router.push({ name: "my-ads" });
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
   selectedCityId.value = routeCity || authStore.user?.active_district_id || (storedCity ? Number(storedCity) : 1);

   const { data: category } = await CategoryRepo.show(route.params.categoryId as string);
   formInputs.value = await buildProductFormInputs(category, { districtId: selectedCityId.value });
   selectedCategory.value = category;
});
</script>
