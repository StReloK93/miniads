<template>
   <NavigationPageDecorator>
      <template #header>
         <BackPreviusPage title="Ortga qaytish" model @close="backToCategoryPage" />
      </template>
      <template #content>
         <div v-show="fullMountedPage" class="grid grid-cols-1 gap-4">
            <BaseProductCard v-for="product in category?.products" :key="product.id" :product />
         </div>
         <template v-if="!fullMountedPage">
            <div class="skeleton h-40"></div>
         </template>
         <main v-if="fullMountedPage && category?.products.length == 0" class="h-full flex justify-center items-center">
            <aside class="text-center">
               <h3 class="text-xl font-semibold mb-4">Hech nima yo'q</h3>
               <p>Ushbu kategoriya tegishli fa'ol e'lonlar yo'q</p>
            </aside>
         </main>
      </template>
   </NavigationPageDecorator>
</template>

<script setup lang="ts">
import { preloadImages } from "@/modules/Helpers";
import { useRouter } from "vue-router";
import BackPreviusPage from "@/components/BackPreviusPage.vue";
import BaseProductCard from "@/components/BaseProductCard.vue";
import { onMounted, ref } from "vue";
import CategoryRepo from "@shared/entities/Category/CategoryRepo";
import { useFetchDecorator } from "@shared/composables/useFetch";
import { useRoute } from "vue-router";
import { ICategory } from "@shared/types";
import NavigationPageDecorator from "@/components/NavigationPageDecorator.vue";

const route = useRoute();
const router = useRouter();
const { data: category, execute } = useFetchDecorator<ICategory>(CategoryRepo.products);

function backToCategoryPage() {
   router.push({ name: "categories", query: { category_id: category.value?.parent_id } });
}
const fullMountedPage = ref(false);
onMounted(async () => {
   await execute(route.params.id);

   const products = category.value?.products!;
   const imageUrls: string[] = products
      .filter((product) => product.images.length > 0)
      .map((product) => `/storage/${product.images[0].src}`);

   imageUrls.push("/images/no-image.png");
   await preloadImages(imageUrls);
   fullMountedPage.value = true;
});
</script>
