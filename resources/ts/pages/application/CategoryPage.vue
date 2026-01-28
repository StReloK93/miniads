<template>
   <main class="h-full relative">
      <section class="absolute inset-0 overflow-y-auto pt-2">
         <template v-if="!isLoading">
            <Accordion v-model:value="accordion">
               <AccordionPanel v-for="category in categories" :key="category.id" :value="category.id">
                  <AccordionHeader class="py-3!">
                     <div class="flex items-center gap-4">
                        <img :src="category.image" class="w-6 dark:invert" />
                        {{ category.name }}
                     </div>
                  </AccordionHeader>

                  <AccordionContent>
                     <RouterLink
                        v-for="child in category.children"
                        :to="{ name: 'category-id', params: { id: child.id } }"
                        :key="child.id"
                        class="first:mt-2 py-1 text-tertiary font-semibold cursor-pointer flex items-center justify-between border-b border-secondary last:border-0"
                     >
                        {{ child.name }} <i class="pi pi-angle-right text-xs!"></i>
                     </RouterLink>
                  </AccordionContent>
               </AccordionPanel>
            </Accordion>
         </template>
         <template v-else>
            <Skeleton v-for="value in 5" :key="value" height="130px" class="mb-2 rounded-none!"></Skeleton>
         </template>
      </section>
   </main>
</template>

<script setup lang="ts">
import { nextTick, onMounted, ref, watch } from "vue";
import { useRoute } from "vue-router";
import CategoryRepo from "@/entities/Category/CategoryRepo";
import { useFetchDecorator } from "@/modules/useFetch";
import { ICategory } from "@/types";
const route = useRoute();
const { data: categories, isLoading, execute: fetchCategories } = useFetchDecorator<ICategory[]>(CategoryRepo.parents);

const accordion = ref<null | number>(null);
const scrollToCategory = async () => {
   const categoryId = route.query.open as string;
   console.log(categoryId);

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
