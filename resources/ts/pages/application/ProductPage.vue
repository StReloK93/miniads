<template>
   <div class="px-4">{{ product }}</div>
</template>

<script setup lang="ts">
import { useRoute } from "vue-router";
import ProductRepo from "@/entities/Product/ProductRepo";
import { useFetchDecorator } from "@/modules/useFetch";
import { onMounted } from "vue";
const route = useRoute();

const { data: product, execute: executeProduct } = useFetchDecorator(ProductRepo.show);

onMounted(() => {
   executeProduct(route.params.id);
});
</script>
