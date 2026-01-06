<template>
   <section class="pt-4 flex flex-col h-dvh">
      <main class="pt-safe-top">
         <BackPreviusPage title="Bo'limlar" class="px-4" />
         <Divider class="mb-0!" />
      </main>
      <main class="grow relative">
         <section class="absolute inset-0 overflow-y-auto pt-2">
            <template v-if="!isLoading">
               <div
                  v-for="(category, index) in categoryParents"
                  :id="`category_${index}`"
                  :key="category.name"
                  class="bg-surface-50 border border-surface-100 dark:bg-surface-900 dark:border-surface-800 px-4 mb-2 py-2"
               >
                  <h3 class="text-xl font-semibold text-surface-800 dark:text-surface-200 mb-1">
                     {{ category.name }}
                  </h3>
                  <aside>
                     <div
                        v-for="childCategory in category.children"
                        :key="childCategory.id"
                        class="border-b border-gray-100 dark:border-transparent flex justify-between items-center"
                     >
                        <span class="text-surface-700 dark:text-surface-300 text-sm">
                           {{ childCategory.name }}
                        </span>
                        <Button
                           icon="pi pi-angle-right"
                           severity="secondary"
                           variant="text"
                           rounded
                        ></Button>
                     </div>

                     <!-- <Inplace :active="activeCategoryId == `category_${index}`" :display-props="{ class: 'p-0! w-full' }">
                        <template #display>
                           <div class="py-1.5 text-xs text-primary w-full">Barchasi</div>
                        </template>
                        <template #content="{ closeCallback }">
                           <div
                              v-for="value in postCategories"
                              :key="value"
                              class="border-b border-gray-100 flex justify-between items-center"
                           >
                              <span class="text-surface-700 text-sm">
                                 {{ value }}
                              </span>
                              <Button icon="pi pi-angle-right" severity="secondary" variant="text" rounded></Button>
                           </div>
                           <div @click="closeCallback" class="py-1.5 text-xs text-primary">Yopish</div>
                        </template>
                     </Inplace> -->
                  </aside>
               </div>
            </template>
            <template v-else>
               <Skeleton v-for="value in 5" :key="value" height="150px" class="mb-2"></Skeleton>
            </template>
         </section>
      </main>
   </section>
</template>

<script setup lang="ts">
import { nextTick, ref, watch } from "vue"; // watch ni import qiling
import { useRoute } from "vue-router";
import CategoryRepo from "@/repositories/CategoryRepo";
import BackPreviusPage from "@/components/features/BackPreviusPage.vue";

const route = useRoute();
const activeCategoryId = ref<string | null>(null);

// Ma'lumotlarni yuklash (bu asinxron jarayon)
const { data: categoryParents, isLoading } = CategoryRepo.parents();

// Scroll qilish logikasini alohida funksiya qilib olamiz
const scrollToCategory = async () => {
   const categoryId = route.query.open as string;

   if (categoryId && categoryParents.value?.length) {
      // 1. DOM yangilanishini kutish (ma'lumot keldi, endi chizib olishi kerak)
      await nextTick();

      // 2. Elementni topish
      const element = document.getElementById(categoryId);

      // 3. Agar element topilsa, scroll qilish
      if (element) {
         activeCategoryId.value = categoryId; // Kerak bo'lsa style uchun
         element.scrollIntoView({ behavior: "smooth", block: "start" });
      }
   }
};

// 'watch' orqali ma'lumot kelishini kuzatamiz
watch(
   () => categoryParents.value, // nimani kuzatamiz?
   (newVal) => {
      // Agar ma'lumot kelgan bo'lsa va bo'sh bo'lmasa
      if (newVal && newVal.length > 0) {
         scrollToCategory();
      }
   },
   { immediate: true }, // Agar ma'lumot keshda bo'lsa, darhol ishlashi uchun
);
</script>
