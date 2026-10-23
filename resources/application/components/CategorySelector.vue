<template>
   <div class="flex flex-col h-full min-h-0 select-none overflow-hidden">
      <!-- Search Input -->
      <div class="shrink-0 mb-2">
         <div class="relative flex items-center">
            <Search class="size-4 absolute left-3 text-(--z-muted-text) pointer-events-none" />
            <input
               v-model="searchQuery"
               type="text"
               placeholder="Kategoriyani qidirish..."
               class="w-full pl-9 pr-8 py-2 bg-(--z-card) border border-(--z-border) rounded-xl text-sm placeholder:text-(--z-muted-text) focus:outline-none focus:border-(--z-primary) transition-all"
            />
            <button
               v-if="searchQuery"
               @click="searchQuery = ''"
               type="button"
               class="absolute right-2.5 p-1 rounded-full hover:bg-(--z-muted) text-(--z-muted-text) active:scale-90 transition-transform"
            >
               <X class="size-3.5" />
            </button>
         </div>
      </div>

      <!-- Breadcrumb (shown only when not searching) -->
      <div v-if="!searchQuery" class="shrink-0 py-2 text-sm flex flex-wrap items-center gap-1">
         <span class="font-bold cursor-pointer hover:text-(--z-primary) transition-colors" @click="goRoot"> Barchasi </span>
         <template v-for="(bc, i) in breadcrumb" :key="bc.id">
            <span class="font-bold text-(--z-muted-text)">
               <ChevronRight class="size-3 font-bold" />
            </span>
            <span class="font-bold cursor-pointer hover:text-(--z-primary) transition-colors" @click="goBack(i)">
               {{ bc.name }}
            </span>
         </template>
      </div>

      <!-- Category list / Search Results -->
      <div class="flex-1 min-h-0 overflow-y-auto pb-6 no-scrollbar">
         <!-- Search Results -->
         <div v-if="searchQuery">
            <ul v-if="filteredCategories.length > 0" class="divide-y divide-(--z-muted)">
               <li
                  v-for="item in filteredCategories"
                  :key="item.category.id"
                  @click="selectCategory(item.category)"
                  class="cursor-pointer"
               >
                  <div class="flex items-center justify-between py-2.5 px-1.5 active:bg-(--z-card) rounded-lg transition-colors">
                     <div class="flex flex-col min-w-0 pr-2">
                        <span class="font-medium text-sm text-(--z-foreground) truncate">
                           {{ item.category.name }}
                        </span>
                        <span v-if="item.pathText" class="text-[11px] text-(--z-muted-text) truncate">
                           {{ item.pathText }}
                        </span>
                     </div>
                     <ChevronRight class="size-4 shrink-0 text-(--z-muted-text)" />
                  </div>
               </li>
            </ul>

            <div v-else class="flex flex-col items-center justify-center py-12 text-center">
               <FolderTree class="size-9 text-(--z-muted-text)/50 mb-2" />
               <p class="text-sm font-medium text-(--z-muted-text)">"{{ searchQuery }}" bo'yicha kategoriya topilmadi</p>
               <button
                  @click="searchQuery = ''"
                  type="button"
                  class="mt-2 text-xs text-(--z-primary) font-semibold hover:underline"
               >
                  Barcha kategoriyalarni ko'rish
               </button>
            </div>
         </div>

         <!-- Normal Hierarchical Navigation -->
         <div v-else>
            <Transition :name="transitionName" mode="out-in">
               <ul :key="stack.length" class="divide-y divide-(--z-muted)">
                  <li v-for="cat in currentCategories" :key="cat.id" @click="selectCategory(cat)" class="cursor-pointer">
                     <div class="flex items-center justify-between py-2.5 px-1.5 active:bg-(--z-card) rounded-md transition-colors">
                        <span class="text-sm font-medium"> {{ cat.name }} </span>

                        <ChevronRight v-if="!cat.is_page" class="size-4 text-(--z-muted-text)" />
                     </div>
                  </li>
               </ul>
            </Transition>
         </div>
      </div>
   </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { ICategory } from "@shared/types";
import { ChevronRight, Search, X, FolderTree } from "lucide-vue-next";
import { useRoute, useRouter } from "vue-router";
const route = useRoute();
const router = useRouter();

const props = defineProps<{
   categories: ICategory[];
   activeCategoryId?: number | null;
}>();

const emit = defineEmits<{
   (e: "selectCategory", category: ICategory): void;
}>();

const searchQuery = ref("");

interface FlatCategoryItem {
   category: ICategory;
   pathText: string;
}

function extractFlatCategories(categories: ICategory[], parentNames: string[] = []): FlatCategoryItem[] {
   const list: FlatCategoryItem[] = [];
   for (const cat of categories) {
      const currentPath = [...parentNames];
      if (cat.children?.length) {
         currentPath.push(cat.name);
         list.push(...extractFlatCategories(cat.children, currentPath));
      } else {
         list.push({
            category: cat,
            pathText: currentPath.join(" › "),
         });
      }
   }
   return list;
}

const allFlatCategories = computed(() => {
   return extractFlatCategories(props.categories || []);
});

const filteredCategories = computed(() => {
   const q = searchQuery.value.trim().toLowerCase();
   if (!q) return [];
   return allFlatCategories.value.filter(
      (item) => item.category.name.toLowerCase().includes(q) || item.pathText.toLowerCase().includes(q),
   );
});

/**
 * Internal navigation state
 */
const stack = ref<ICategory[][]>([props.categories]);
const breadcrumb = ref<ICategory[]>([]);

/**
 * Transition direction
 */
const transitionName = ref<"slide-in" | "slide-out">("slide-in");

/**
 * Current visible categories
 */
const currentCategories = computed(() => {
   return stack.value[stack.value.length - 1];
});

/**
 * Recursive path finder
 */
function findCategoryPath(categories: ICategory[], targetId: number, path: ICategory[] = []): ICategory[] | null {
   for (const cat of categories) {
      const newPath = [...path, cat];

      if (cat.id === targetId) {
         return newPath;
      }

      if (cat.children?.length) {
         const result = findCategoryPath(cat.children, targetId, newPath);
         if (result) return result;
      }
   }
   return null;
}

function openCategoryById(categoryId: number) {
   const path = findCategoryPath(props.categories, categoryId);
   if (!path) return;

   transitionName.value = "slide-in";

   // parentlar
   breadcrumb.value = path.slice(0, -1);

   const newStack: ICategory[][] = [props.categories];

   for (const cat of breadcrumb.value) {
      if (cat.children?.length) {
         newStack.push(cat.children);
      }
   }

   /**
    * 🔴 YETISHMAYOTGAN QISM
    * Agar tanlangan category sahifa bo'lmasa — uni ham ochamiz
    */
   const active = path[path.length - 1];

   if (!active.is_page && active.children?.length) {
      breadcrumb.value.push(active);
      newStack.push(active.children);
   }

   stack.value = newStack;
}
/**
 * Select category (user click)
 */
function selectCategory(cat: ICategory) {
   searchQuery.value = "";
   router.push({
      query: {
         ...route.query,
         category_id: cat.id,
      },
   });

   if (cat.is_page || !cat.children?.length) {
      emit("selectCategory", cat);
      return;
   }

   openCategoryById(cat.id);
}

/**
 * Go back (breadcrumb or level)
 */
function goBack(index?: number) {
   transitionName.value = "slide-out";

   if (index === undefined) {
      if (stack.value.length > 1) {
         stack.value.pop();
         breadcrumb.value.pop();
      }
   } else {
      breadcrumb.value = breadcrumb.value.slice(0, index + 1);
      stack.value = stack.value.slice(0, index + 2);
   }
}

/**
 * Go root
 */
function goRoot() {
   transitionName.value = "slide-out";
   breadcrumb.value = [];
   stack.value = [props.categories];
}

watch(
   () => [props.activeCategoryId, props.categories],
   ([id, categories]: any) => {
      if (!id) goRoot();
      if (!categories || categories.length === 0) return;

      openCategoryById(id);
   },
   { immediate: true },
);
</script>
