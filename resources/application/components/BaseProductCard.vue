<template>
   <RouterLink :to="{ name: 'product-id', params: { id: product.id } }" class>
      <section class="bg-secondary p-1.5 rounded-xl select-none">
         <div>
            <img :src="productImage" @error="handleImageError" class="rounded-md w-full object-cover aspect-video" />
         </div>
         <h3 class="my-1.5 font-semibold line-clamp-1 text-sm">{{ product.title }}</h3>
         <div class="flex flex-wrap gap-x-3 text-xs">
            <span v-for="param in shortParameters" :key="param.id"> {{ param.value }} {{ param.parameter.unit }} </span>
         </div>
         <h3 class="mt-1 text-primary font-semibold text-sm">{{ product.price }}</h3>
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
