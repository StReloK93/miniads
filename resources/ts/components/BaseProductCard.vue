<template>
   <RouterLink :to="{ name: 'product-id', params: { id: product.id } }" class>
      <section class="bg-secondary p-1.5 pb-0 rounded-xl select-none border border-secondary shadow-xs">
         <div>
            <img :src="`/storage/${product.images[0].src}`" class="rounded-md w-full object-cover aspect-video" />
         </div>
         <h3 class="my-1.5 font-semibold line-clamp-1 text-sm">{{ product.title }}</h3>
         <div class="flex flex-wrap gap-2 text-xs">
            <span v-for="param in shortParameters" :key="param.id">
               {{ param.parameter.placeholder }}: {{ param.value }}
            </span>
         </div>
         <h3 class="mt-1 text-primary font-semibold text-sm">{{ product.price }}</h3>
      </section>
   </RouterLink>
</template>

<script setup lang="ts">
import { IProduct } from "@/types";
import { computed } from "vue";
const props = defineProps<{
   product: IProduct;
}>();

const shortParameters = computed(() => {
   return props.product.parameter_values.slice(0, 2);
});
</script>
