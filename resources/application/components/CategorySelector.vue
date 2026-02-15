<template>
   <div class="overflow-hidden grid grid-rows-[auto_1fr] h-full">
      <!-- Breadcrumb -->
      <div class="p-2 text-sm flex flex-wrap items-center gap-2 mb-2">
         <span class="cursor-pointer text-(--z-color-text-secondary) active:text-black" @click="goRoot">
            Kategoriyalar
         </span>

         <template v-for="(bc, i) in breadcrumb" :key="bc.id">
            <span class="text-(--z-color-text-secondary)">
               <ChevronRight class="size-4 text-(--z-color-text-secondary)" />
            </span>
            <span class="cursor-pointer text-(--z-color-text-primary) active:text-black" @click="goBack(i)">
               {{ bc.name }}
            </span>
         </template>
      </div>

      <!-- Category list -->
      <div class="overflow-hidden relative">
         <Transition :name="transitionName" mode="out-in">
            <ul :key="stack.length" class="divide-y divide-(--z-color-border)">
               <li
                  v-for="cat in currentCategories"
                  :key="cat.id"
                  @click="selectCategory(cat)"
                  class="flex items-center justify-between p-2 cursor-pointer active:bg-(--z-bg-secondary) rounded-md"
               >
                  <span class="font-medium text-(--z-color-text-primary)">
                     {{ cat.name }}
                  </span>

                  <ChevronRight v-if="!cat.is_page" class="size-4 text-(--z-color-text-secondary)" />
               </li>
            </ul>
         </Transition>
      </div>
   </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { ICategory } from "@shared/types";
import { ChevronRight } from "lucide-vue-next";

const props = defineProps<{
   categories: ICategory[];
   activeCategoryId?: number | null;
}>();

const emit = defineEmits<{
   (e: "selectCategory", category: ICategory): void;
}>();

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

function findCategory(categories: ICategory[], targetId: number, path: ICategory[] = []): ICategory | null {
   for (const cat of categories) {
      const newPath = [...path, cat];

      if (cat.id === targetId) {
         return cat;
      }

      if (cat.children?.length) {
         const result = findCategory(cat.children, targetId, newPath);
         if (result) return result;
      }
   }
   return null;
}

/**
 * Open category by external id
 */
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
    * Agar tanlangan category sahifa bo‘lmasa — uni ham ochamiz
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
   if (cat.is_page) {
      emit("selectCategory", cat);
      return;
   }

   if (cat.children?.length) {
      transitionName.value = "slide-in";
      breadcrumb.value.push(cat);
      stack.value.push(cat.children);
   }
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
      if (!id) return;
      if (!categories || categories.length === 0) return;

      openCategoryById(id);
   },
   { immediate: true },
);
</script>
