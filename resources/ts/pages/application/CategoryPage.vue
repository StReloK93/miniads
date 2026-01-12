<template>
   <section class="pt-4 flex flex-col h-dvh bg-tertiary">
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
                  class="bg-secondary border border-secondary px-4 mb-2 py-2"
               >
                  <h3 class="text-xl font-semibold text-tertiary mb-1">
                     {{ category.name }}
                  </h3>
                  <aside>
                     <div
                        v-for="childCategory in segmentItems(category.children, 2).first"
                        :key="childCategory.id"
                        class="flex justify-between items-center"
                     >
                        <span class="text-secondary text-sm">
                           {{ childCategory.name }}
                        </span>
                        <Button
                           icon="pi pi-angle-right"
                           severity="secondary"
                           variant="text"
                           rounded
                        ></Button>
                     </div>

                     <Inplace
                        v-if="category.children.length > 2"
                        :active="activeCategoryId == `category_${index}`"
                        :display-props="{ class: 'p-0! w-full' }"
                     >
                        <template #display>
                           <div class="py-1.5 text-xs text-primary w-full">Barchasi</div>
                        </template>
                        <template #content="{ closeCallback }">
                           <div
                              v-for="value in segmentItems(category.children, 2).remaining"
                              :key="value"
                              class="flex justify-between items-center"
                           >
                              <span class="text-surface-700 text-sm">
                                 {{ value.name }}
                              </span>
                              <Button
                                 icon="pi pi-angle-right"
                                 severity="secondary"
                                 variant="text"
                                 rounded
                              ></Button>
                           </div>
                           <div
                              @click="closeCallback"
                              class="py-1.5 text-xs text-primary cursor-pointer"
                           >
                              Yopish
                           </div>
                        </template>
                     </Inplace>
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
import { nextTick, ref, watch } from "vue";
import { useRoute } from "vue-router";
import CategoryRepo from "@/entities/Category/CategoryRepo";
import BackPreviusPage from "@/components/BackPreviusPage.vue";

const route = useRoute();
const activeCategoryId = ref<string | null>(null);

const { data: categoryParents, isLoading } = CategoryRepo.parents();

function segmentItems(array: any[], count: number) {
   if (array.length > 2) {
      const first = array.slice(0, count);
      const remaining = array.length > 2 ? array.slice(2) : [];

      return {
         first,
         remaining,
      };
   }
   return { first: array, remaining: [] };
}

// Scroll qilish logikasini alohida funksiya qilib olamiz
const scrollToCategory = async () => {
   const categoryId = route.query.open as string;

   if (categoryId && categoryParents.value?.length) {
      await nextTick();
      const element = document.getElementById(categoryId);

      if (element) {
         activeCategoryId.value = categoryId; // Kerak bo'lsa style uchun
         element.scrollIntoView({ behavior: "smooth", block: "start" });
      }
   }
};

watch(
   () => categoryParents.value,
   (newVal) => {
      if (newVal && newVal.length > 0) {
         scrollToCategory();
      }
   },
   { immediate: true },
);
</script>
