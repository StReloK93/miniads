<template>
   <div class="grid grid-cols-2 lg:grid-cols-3 gap-2 content-start">
      <BaseProductCard v-for="product in data?.products || []" :key="product.id" :product />
      <BaseProductCard v-for="product in data?.products || []" :key="product.id" :product />
      <BaseProductCard v-for="product in data?.products || []" :key="product.id" :product />
      <BaseProductCard v-for="product in data?.products || []" :key="product.id" :product />
      <BaseProductCard v-for="product in data?.products || []" :key="product.id" :product />
      <BaseProductCard v-for="product in data?.products || []" :key="product.id" :product />
      <BaseProductCard v-for="product in data?.products || []" :key="product.id" :product />
      <BaseProductCard v-for="product in data?.products || []" :key="product.id" :product />
   </div>
</template>

<script setup lang="ts">
import BaseProductCard from "@/components/BaseProductCard.vue";
import { onMounted, ref } from "vue";
import CategoryRepo from "@/entities/Category/CategoryRepo";
import { useFetchDecorator } from "@/modules/useFetch";
import { useRoute } from "vue-router";
import { ICategory } from "@/types";

const route = useRoute();

const { data, execute } = useFetchDecorator<ICategory>(CategoryRepo.products);

onMounted(() => {
   execute(route.params.id);
});
</script>
