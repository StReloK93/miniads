<template>
   <section>
      <main class="py-4 border-slate-100 flex items-center gap-2 text-xs">
         <RouterLink :to="{ name: 'home' }"> Bosh sahifa </RouterLink>
         <ChevronRight class="size-4" />
         <RouterLink :to="{ name: 'categories' }"> Kategoriyalar </RouterLink>
         <ChevronRight class="size-4" v-if="data?.breadcrumbs?.length" />

         <div v-for="(crumb, index) in data?.breadcrumbs" :key="crumb.id" class="flex items-center gap-2">
            <RouterLink :to="{ name: 'categories', params: { id: crumb.id } }">{{ crumb.name }}</RouterLink>
            <ChevronRight class="size-4" v-if="data?.breadcrumbs.length! > index + 1" />
         </div>
      </main>
      <main>
         <RouterLink
            v-for="category in data?.categories"
            :key="category.id"
            :to="is_page(category)"
            class="py-(--space-md) text-sm cursor-pointer flex items-center justify-between"
         >
            {{ category.name }}
            <ChevronRight
               v-if="!category.is_page"
               class="size-4 text-(--color-text-secondary) transition-transform duration-(--duration-fast)"
            />
         </RouterLink>
      </main>
   </section>
</template>

<script setup lang="ts">
import { useCategory } from "@shared/entities/Category/useCategory";
import { ChevronRight } from "lucide-vue-next";
import { nextTick, onMounted, ref, watch } from "vue";
import { useRoute } from "vue-router";
import CategoryRepo from "@shared/entities/Category/CategoryRepo";
import { useFetchDecorator } from "@shared/api/useFetch";
import { ICategory } from "@shared/types";
const route = useRoute();
const CategoryStore = useCategory();

const { data, execute: fetchCategories } = useFetchDecorator<{ categories: ICategory[]; breadcrumbs: any[] }>(
   CategoryRepo.parents,
);

function is_page(category: ICategory) {
   if (category.is_page) {
      return { name: "category", params: { id: category.id } };
   } else {
      return { name: "categories", params: { id: category.id } };
   }
}

onMounted(async () => {
   await fetchCategories(route.params.id);
});
</script>

<!-- 
Main Page

Elon Topishga mo'ljallagan bo'lim

Qidiruv Yonida filter
Pasida Kategoriyalar

---------------
Xizmatlar

---------------
Topilmalar

---------------
Ish o'rinlari 



--------------

Navigator
-->
