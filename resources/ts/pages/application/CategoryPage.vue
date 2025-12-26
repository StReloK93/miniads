<template>
   <section class="pt-4 flex flex-col h-dvh">
      <main>
         <BackPreviusPage title="Bo'limlar" class="px-4" />
         <Divider class="mb-0!" />
      </main>
      <main class="grow relative">
         <section class="absolute inset-0 overflow-y-auto pt-2">
            <div
               v-for="(category, index) in categories"
               :id="`category_${index}`"
               class="bg-surface-50 border border-surface-100 px-4 mb-2 py-2"
            >
               <h3 class="text-xl font-semibold text-surface-800 mb-1">
                  {{ category.name }}
               </h3>
               <aside>
                  <div
                     v-for="value in postCategories"
                     class="border-b border-gray-100 flex justify-between items-center"
                  >
                     <span class="text-surface-700 text-sm">
                        {{ value }}
                     </span>
                     <Button icon="pi pi-angle-right" severity="secondary" variant="text" rounded></Button>
                  </div>

                  <Inplace :active="activeCategoryId == `category_${index}`" :display-props="{ class: 'p-0! w-full' }">
                     <template #display>
                        <div class="py-1.5 text-xs text-primary w-full">Barchasi</div>
                     </template>
                     <template #content="{ closeCallback }">
                        <div
                           v-for="value in postCategories"
                           class="border-b border-gray-100 flex justify-between items-center"
                        >
                           <span class="text-surface-700 text-sm">
                              {{ value }}
                           </span>
                           <Button icon="pi pi-angle-right" severity="secondary" variant="text" rounded></Button>
                        </div>
                        <div @click="closeCallback" class="py-1.5 text-xs text-primary">Yopish</div>
                     </template>
                  </Inplace>
               </aside>
            </div>
         </section>
      </main>
   </section>
</template>

<script setup lang="ts">
import { onMounted, nextTick, ref } from "vue";
import { useRoute } from "vue-router";

const route = useRoute();
const activeCategoryId = ref<string | null>(null);
import BackPreviusPage from "@/components/features/BackPreviusPage.vue";
import { categories } from "@/test/data";

const postCategories = ["Mobile & Electronics", "House & apartment", "Washing machine"];

onMounted(async () => {
   const categoryId = route.query.open as string;

   if (categoryId) {
      // 1. Podkategoriyani ochish (Sizning kodingizdagi hide/show mantiqiga moslang)
      activeCategoryId.value = categoryId;

      // 2. DOM yangilanishini kutish (Blok ochilishi uchun vaqt kerak)
      await nextTick();

      // 3. Elementni topish va scroll qilish
      const element = document.getElementById(categoryId);
      if (element) {
         element.scrollIntoView({ behavior: "smooth", block: "start" });
      }
   }
});
</script>
