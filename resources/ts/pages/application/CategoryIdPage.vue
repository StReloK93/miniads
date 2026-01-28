<template>
   <section>
      <h3 class="flex items-center gap-1 px-1 mb-2 text-secondary text-sm">
         <RouterLink :to="{ name: 'categories', query: { open: `${category?.parent.id}` } }" class="underline"
            >{{ category?.parent.name }}
         </RouterLink>
         <i class="pi pi-angle-right text-xs!"></i>
         {{ category?.name }}
      </h3>
      <div class="grid grid-cols-2 lg:grid-cols-3 gap-2 content-start">
         <BaseProductCard v-for="product in category?.products || []" :key="product.id" :product />
      </div>
   </section>
</template>

<script setup lang="ts">
import BaseProductCard from "@/components/BaseProductCard.vue";
import { onMounted } from "vue";
import CategoryRepo from "@/entities/Category/CategoryRepo";
import { useFetchDecorator } from "@/modules/useFetch";
import { useRoute } from "vue-router";
import { ICategory } from "@/types";

const route = useRoute();

const { data: category, execute } = useFetchDecorator<ICategory>(CategoryRepo.products);

onMounted(() => {
   execute(route.params.id);
});
</script>
