<template>
   <RouterLink :to="{ name: 'product-id', params: { id: product.id } }" class>
      <section class="bg-(--z-bg-secondary) p-2 rounded-xl select-none border border-(--z-color-border)">
         <div class="mb-2.5">
            <img :src="productImage" @error="handleImageError" class="rounded-md w-full object-cover aspect-4/2" />
         </div>
         <main class="p-1.5">
            <div class="flex justify-between">
               <h3 class="font-semibold line-clamp-1">{{ product.title }}</h3>
               <h3
                  class="inline-flex items-center gap-1"
                  :class="{ 'flex-row-reverse': product.price_type.type === 'left' }"
               >
                  <span class="font-semibold">
                     {{ product.price }}
                  </span>
                  {{ product.price_type.type }}
               </h3>
            </div>
            <!-- <div class="flex flex-wrap gap-x-3 text-xs">
               <span v-for="param in shortParameters" :key="param.id">
                  {{ param.value }} {{ param.parameter.unit }}
               </span>
            </div> -->
         </main>
      </section>
   </RouterLink>
</template>

<script setup lang="ts">
import { IProduct } from "@shared/types";
import { computed } from "vue";
const props = defineProps<{
   product: IProduct;
}>();

const shortParameters = computed(() => {
   return props.product.parameter_values.slice(0, 2);
});

// 1. Agar massiv bo'sh bo'lsa, default rasm yo'lini qaytaramiz
const productImage = computed(() => {
   if (props.product.images && props.product.images.length > 0) {
      return `/storage/${props.product.images[0].src}`;
   }
   return "/images/no-image.png"; // Public papkadagi default rasm yo'li
});

// 2. Agar rasm serverda topilmasa (404), ushbu funksiya ishlaydi
const handleImageError = (event) => {
   event.target.src = "/images/no-image.webp"; // Public papkadagi default rasm yo'li
};
</script>
