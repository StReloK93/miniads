<template>
   <!-- E'lonni admin panelning o'zida tahrirlash: ilovadagi forma (rasmlar, parametrlar, narx) — o'sha maydonlar -->
   <BaseDrawer :open="productId !== null" title="E'lonni tahrirlash" :description="description" @close="close">
      <div v-if="loading" class="admin-loading">Forma yuklanmoqda…</div>
      <p v-else-if="error" class="admin-alert admin-alert-error">{{ error }}</p>
      <div v-else-if="category && inputs.length" class="flex h-full flex-col">
         <p class="mb-1 text-xs text-(--z-muted-text)">
            <span v-for="(item, index) in breadcrumb" :key="item.id">
               <template v-if="index"> › </template>{{ item.name }}
            </span>
         </p>
         <!-- key: boshqa e'lon ochilsa forma qiymatlari yangidan olinadi -->
         <BaseForm
            :key="productId ?? 0"
            class="min-h-0 flex-1"
            :input-configs="inputs"
            :submit="submit"
            submit-label="Saqlash"
            @submit="onSaved"
         />
      </div>
   </BaseDrawer>
</template>

<script setup lang="ts">
import { computed, ref, shallowRef, watch } from "vue";
import BaseDrawer from "@shared/ui/BaseDrawer.vue";
import BaseForm from "@shared/ui/BaseForm.vue";
import ProductRepo from "@shared/entities/Product/ProductRepo";
import CategoryRepo from "@shared/entities/Category/CategoryRepo";
import { buildBreadcrumb } from "@shared/modules/helpers";
import { buildProductFormInputs, buildProductPayload } from "@/features/product/useProductForm";
import type { ICategory, InputConfig } from "@shared/types";

const props = defineProps<{ productId: number | null }>();
const emit = defineEmits<{ (e: "close"): void; (e: "saved", message: string): void }>();

const loading = ref(false);
const error = ref("");
const title = ref("");
const districtId = ref<number | null>(null);
const category = ref<ICategory | null>(null);
const inputs = shallowRef<InputConfig[]>([]);

const description = computed(() => (props.productId ? `#${props.productId}${title.value ? ` · ${title.value}` : ""}` : ""));
const breadcrumb = computed(() => buildBreadcrumb(category.value));

async function open(id: number) {
   loading.value = true;
   error.value = "";
   category.value = null;
   inputs.value = [];
   title.value = "";
   try {
      const { data: product } = await ProductRepo.edit(id);
      // Drawer yuklanayotganda boshqa e'lon ochilgan bo'lsa — eskisini ko'rsatmaymiz
      if (props.productId !== id) return;
      title.value = product.title ?? "";
      if (!product.category_id) {
         error.value = "E'lonning kategoriyasi yo'q — tahrirlab bo'lmaydi.";
         return;
      }
      const { data: productCategory } = await CategoryRepo.show(product.category_id);
      districtId.value = product.district_id ?? null;
      inputs.value = await buildProductFormInputs(productCategory, { districtId: districtId.value, product });
      category.value = productCategory;
   } catch (exception: any) {
      console.error("E'lon formasini yuklab bo'lmadi.", exception);
      error.value = exception?.response?.data?.message || "E'lon formasini yuklab bo'lmadi.";
   } finally {
      loading.value = false;
   }
}

async function submit(values: unknown) {
   if (!props.productId) return;
   const payload = buildProductPayload(values as Record<string, any>, {
      categoryId: category.value?.id,
      fallbackDistrictId: districtId.value,
   });
   await ProductRepo.update(props.productId, payload);
}

function onSaved() {
   emit("saved", "E'lon saqlandi.");
   close();
}

function close() {
   emit("close");
}

watch(
   () => props.productId,
   (id) => {
      if (id) open(id);
   },
   { immediate: true },
);
</script>
