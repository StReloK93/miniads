<template>
   <div class="relative w-full max-w-md mx-auto overflow-hidden grid grid-rows-[auto_1fr] h-full">
      <!-- Breadcrumb -->
      <div class="p-2 text-sm flex flex-wrap items-center gap-2 mb-2">
         <span class="cursor-pointer text-gray-500 hover:text-black transition" @click="goRoot"> Kategoriyalar </span>

         <template v-for="(bc, i) in breadcrumb" :key="bc.id">
            <span class="text-gray-500">
               <ChevronRight class="size-4" />
            </span>
            <span class="cursor-pointer text-gray-600 hover:text-black transition" @click="goBack(i)">
               {{ bc.name }}
            </span>
         </template>
      </div>

      <!-- Category list -->
      <div class="overflow-hidden relative">
         <Transition :name="transitionName" mode="out-in">
            <ul :key="stack.length" class="divide-y divide-slate-100">
               <li
                  v-for="cat in currentCategories"
                  :key="cat.id"
                  @click="selectCategory(cat)"
                  class="flex items-center justify-between p-2 cursor-pointer hover:bg-gray-50 active:bg-gray-100 transition"
               >
                  <span class="font-medium text-gray-800">
                     {{ cat.name }}
                  </span>

                  <span v-if="!cat.is_page" class="text-gray-400"> → </span>
               </li>
            </ul>
         </Transition>
      </div>
   </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { ICategory } from "@shared/types";
import { ChevronRight } from "lucide-vue-next";

const props = defineProps<{
   categories: ICategory[];
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
 * Select category
 */
function selectCategory(cat: ICategory) {
   if (cat.is_page) {
      emit("selectCategory", cat);
      return;
   }

   if (cat.children && cat.children.length > 0) {
      transitionName.value = "slide-in";
      breadcrumb.value.push(cat);
      stack.value.push(cat.children);
   }
}

/**
 * Go back (1 level or via breadcrumb)
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

function goRoot() {
   transitionName.value = "slide-out";
   breadcrumb.value = [];
   stack.value = [props.categories];
}
</script>
