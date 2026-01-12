<template>
   <section>
      <div class="pt-4">
         <main v-for="category in categories">
            <nav class="py-1 text-secondary">
               {{ category.name }}
            </nav>
            <ul>
               <li
                  v-for="child in category.children"
                  @click="pageData.selectedCategory = child.id"
                  class="pl-4 py-1 text-primary-500"
               >
                  {{ child.name }}
               </li>
            </ul>
         </main>
      </div>
      <Drawer
         v-model:visible="isVisible"
         position="bottom"
         style="height: 80%"
         class="headless-drawer"
         :show-close-icon="false"
      >
         {{ pageData.selectedCategory }}
      </Drawer>
   </section>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive } from "vue";
import CategoryRepo from "../Category/CategoryRepo";

const { data: categories } = CategoryRepo.parents();

const pageData = reactive<{
   selectedCategory: string | null;
}>({
   selectedCategory: null,
});

onMounted(() => {});
const isVisible = computed({
   get: () => !!pageData.selectedCategory, // Agar null bo'lmasa true qaytaradi
   set: (value) => {
      if (!value) pageData.selectedCategory = null; // Modal yopilganda datani null qiladi
   },
});
</script>
