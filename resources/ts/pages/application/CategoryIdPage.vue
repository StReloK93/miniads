<template>
   <div class="flex items-start">
      <BaseProductCard
         v-for="product in data?.products || []"
         :key="product.id"
         :image="'https://picsum.photos/1200'"
         :title="product.name"
         :subtitle="product.description"
         :price="1000 + ' USD'"
         class="w-1/2 lg:w-1/3 p-2"
      />
   </div>
</template>

<script setup lang="ts">
import BaseProductCard from "@/components/BaseProductCard.vue";
import { onMounted, ref } from "vue";
import CategoryRepo from "@/entities/Category/CategoryRepo";
import { useFetchDecorator } from "@/modules/useFetch";
import { useRoute } from "vue-router";

const route = useRoute();

const { data, execute } = useFetchDecorator(
   async () => await CategoryRepo.products(route.params.id as string),
);

onMounted(() => {
   // const categoryId = route.params.id as string;

   execute();
});
</script>
