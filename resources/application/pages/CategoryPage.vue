<template>
   <main class="h-full relative">
      <section class="absolute inset-0 overflow-y-auto pt-2">
         <template v-if="!isLoading">
            <AccordionGroup v-model="accordion">
               <AccordionItem
                  v-for="category in categories"
                  :key="category.id"
                  :index="category.id"
                  :title="category.name"
               >
                  <RouterLink
                     v-for="child in category.children"
                     :to="{ name: 'category-id', params: { id: child.id } }"
                     :key="child.id"
                     class="py-(--space-md) cursor-pointer flex items-center justify-between border-b border-slate-100 last:border-0"
                  >
                     {{ child.name }} <ChevronRightIcon class="h-3 w-3" />
                  </RouterLink>
               </AccordionItem>
            </AccordionGroup>
         </template>
      </section>
   </main>
</template>

<script setup lang="ts">
import { ChevronRightIcon } from "@heroicons/vue/24/outline";
import AccordionGroup from "@shared/ui/AccordionGroup.vue";
import AccordionItem from "@shared/ui/AccordionItem.vue";
import { nextTick, onMounted, ref, watch } from "vue";
import { useRoute } from "vue-router";
import CategoryRepo from "@/entities/Category/CategoryRepo";
import { useFetchDecorator } from "@/modules/useFetch";
import { ICategory } from "@shared/types";
const route = useRoute();
const { data: categories, isLoading, execute: fetchCategories } = useFetchDecorator<ICategory[]>(CategoryRepo.parents);

const accordion = ref<null | number>(null);
const scrollToCategory = async () => {
   const categoryId = route.query.open as string;

   if (categoryId && categories.value?.length) {
      await nextTick();
      accordion.value = +categoryId;
   }
};

watch(
   () => categories.value,
   (newVal) => {
      if (newVal && newVal.length > 0) {
         scrollToCategory();
      }
   },
   { immediate: true },
);

onMounted(async () => {
   await fetchCategories();
});
</script>
