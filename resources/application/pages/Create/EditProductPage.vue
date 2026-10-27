<template>
   <section class="h-full">
      <ProductFormShell
         :category="selectedCategory"
         :inputs="formInputs"
         :submit="submitForm"
         :submit-label="$t('create.update')"
         @submitted="onSubmit"
      >
         <template #heading>
            <h3 class="font-extrabold text-xl mb-1">{{ $t("create.editTitle") }}</h3>
            <p class="title text-xs mb-4">{{ $t("create.details") }}</p>
         </template>
      </ProductFormShell>
   </section>
</template>

<script setup lang="ts">
import ProductFormShell from "@/features/product/ProductFormShell.vue";
import ProductRepo from "@shared/entities/Product/ProductRepo";
import CategoryRepo from "@shared/entities/Category/CategoryRepo";
import { useRoute, useRouter } from "vue-router";
import { ICategory, InputConfig } from "@shared/types";
import { onMounted, ref, shallowRef } from "vue";
import { buildProductFormInputs, buildProductPayload } from "@/features/product/useProductForm";

const router = useRouter();
const route = useRoute();

const props = defineProps<{
   product_id: number;
}>();

const selectedCityId = ref<number | null>(null);
const selectedCategory = ref<ICategory | null>(null);
const formInputs = shallowRef<InputConfig[]>([]);

async function submitForm(values: Record<string, any>) {
   const payload = buildProductPayload(values, {
      categoryId: selectedCategory.value?.id,
      fallbackDistrictId: selectedCityId.value,
   });

   await ProductRepo.update(props.product_id, payload);
}

function onSubmit() {
   // Admin paneldan kelgan bo'lsa — o'sha yerga qaytadi (admin — alohida ilova, to'liq o'tish)
   if (route.query.from === "admin") {
      window.location.href = "/admin/products";
      return;
   }
   router.push({ name: "my-ads" });
}

onMounted(async () => {
   const { data: product } = await ProductRepo.edit(props.product_id);
   if (!product.category_id) return;

   const { data: category } = await CategoryRepo.show(product.category_id);
   selectedCityId.value = product.district_id;
   formInputs.value = await buildProductFormInputs(category, { districtId: product.district_id, product });
   selectedCategory.value = category;
});
</script>
