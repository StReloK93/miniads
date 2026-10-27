<template>
   <NavigationPageDecorator :header-class="['border-b', 'border-(--z-border)']">
      <template #header>
         <h3 class="font-bold text-xl">{{ category?.name ?? " " }}</h3>
         <p class="mt-1 mb-3 text-sm text-(--z-muted-text) truncate">
            <span v-for="(item, index) in breadcrumb" :key="item.id">
               <ChevronRight v-if="index" class="size-3 inline" /> {{ item.name }}
            </span>
         </p>
         <FilterBar class="pb-3" :filters="filters" :total="isLoading ? undefined : category?.products.length" @open="isFilterOpen = true" />
      </template>

      <template #content>
         <!-- Shu bo'limning o'z parametrlari bilan: avtoda yil/probeg, uyda xona/maydon... -->
         <ProductFilterDrawer
            v-model="filters"
            :open="isFilterOpen"
            :parameters="category?.parameters ?? []"
            :with-price="category?.with_price !== false"
            @close="isFilterOpen = false"
         />

         <main v-if="isLoading && !category?.products?.length" class="flex flex-col gap-2.5">
            <ProductCardSkeleton v-for="n in 4" :key="n" />
         </main>

         <main v-else-if="category?.products.length" class="flex flex-col gap-2.5" :class="{ 'opacity-60': isLoading }">
            <ProductCard v-for="product in category.products" :key="product.id" :product />
         </main>

         <main v-else class="py-16 flex flex-col items-center text-center">
            <h3 class="text-base font-semibold mb-1">Hech narsa topilmadi</h3>
            <p class="text-sm text-(--z-muted-text) max-w-64">
               {{ activeCount ? "Tanlangan filtrlar bo'yicha e'lon yo'q." : "Bu bo'limda hozircha faol e'lonlar yo'q." }}
            </p>
            <BaseButton v-if="activeCount" severity="secondary" size="sm" class="mt-4" @click="filters = emptyFilters()">
               Filtrlarni tozalash
            </BaseButton>
         </main>
      </template>
   </NavigationPageDecorator>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { useRoute } from "vue-router";
import { ChevronRight } from "lucide-vue-next";
import { buildBreadcrumb } from "@shared/modules/helpers";
import ProductCard from "@/features/product/ProductCard.vue";
import ProductCardSkeleton from "@/features/product/ProductCardSkeleton.vue";
import FilterBar from "@/features/filters/FilterBar.vue";
import ProductFilterDrawer from "@/features/filters/ProductFilterDrawer.vue";
import { countActiveFilters, emptyFilters, toQueryParams, type ListFilters } from "@/features/filters/listFilters";
import CategoryRepo from "@shared/entities/Category/CategoryRepo";
import { useFetchDecorator } from "@shared/composables/useFetch";
import { ICategory } from "@shared/types";
import NavigationPageDecorator from "@/layouts/NavigationPageDecorator.vue";
import BaseButton from "@shared/ui/BaseButton.vue";

const route = useRoute();
const { data: category, execute, isLoading } = useFetchDecorator<ICategory>(CategoryRepo.products);

const filters = ref<ListFilters>(emptyFilters());
const isFilterOpen = ref(false);
const activeCount = computed(() => countActiveFilters(filters.value));
const breadcrumb = computed(() => buildBreadcrumb(category.value));

function load() {
   return execute(route.params.id, toQueryParams(filters.value));
}

watch(filters, load, { deep: true });
onMounted(load);
</script>
