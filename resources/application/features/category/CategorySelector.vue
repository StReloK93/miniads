<template>
   <div class="flex flex-col h-full min-h-0 select-none overflow-hidden">
      <div class="relative flex-1 min-h-0">
         <div class="absolute inset-0 overflow-y-auto no-scrollbar pb-6">
            <Transition :name="transitionName" mode="out-in">
               <!-- Asosiy bo'limlar -->
               <ul
                  v-if="!currentParent"
                  key="root"
                  class="bg-(--z-card) border border-(--z-border) rounded-(--z-rounded) divide-y divide-(--z-border) overflow-hidden"
               >
                  <li v-for="category in currentCategories" :key="category.id">
                     <button
                        type="button"
                        data-category-item
                        class="w-full flex items-center gap-3 px-3.5 py-2.5 text-left cursor-pointer active:bg-(--z-muted) transition-colors"
                        @click="selectCategory(category)"
                     >
                        <span class="size-9 shrink-0 rounded-full bg-(--z-muted) text-(--z-primary) grid place-items-center">
                           <component :is="iconOf(category)" class="size-4.5" stroke-width="1.75" />
                        </span>
                        <span class="min-w-0 grow">
                           <span class="block text-sm font-semibold truncate">{{ category.name }}</span>
                           <span class="block text-xs text-(--z-muted-text)">
                              <template v-if="showCounts">{{ $t("common.adsCount", adsCount(category)) }}</template>
                              <template v-else-if="category.children?.length">{{ $t("categories.count", category.children.length) }}</template>
                              <template v-else>{{ $t("common.select") }}</template>
                           </span>
                        </span>
                        <ChevronRight class="size-4 shrink-0 text-(--z-muted-text)" />
                     </button>
                  </li>
               </ul>

               <!-- Ichki bo'limlar -->
               <div v-else :key="currentParent.id">
                  <div class="flex items-center gap-3 mb-3">
                     <button
                        type="button"
                        :aria-label="$t('common.back')"
                        class="size-9 shrink-0 rounded-full bg-(--z-card) border border-(--z-border) grid place-items-center cursor-pointer transition active:scale-90"
                        @click="goBack()"
                     >
                        <ChevronLeft class="size-4.5" />
                     </button>
                     <div class="min-w-0">
                        <p class="text-xs text-(--z-muted-text) truncate">
                           <button type="button" class="cursor-pointer hover:text-(--z-foreground)" @click="goRoot">{{ $t("common.all") }}</button>
                           <template v-for="(item, i) in breadcrumb.slice(0, -1)" :key="item.id">
                              ›
                              <button type="button" class="cursor-pointer hover:text-(--z-foreground)" @click="goBack(i)">
                                 {{ item.name }}
                              </button>
                           </template>
                        </p>
                        <h4 class="font-bold text-base truncate">{{ currentParent.name }}</h4>
                     </div>
                  </div>

                  <ul class="bg-(--z-card) border border-(--z-border) rounded-(--z-rounded) divide-y divide-(--z-border) overflow-hidden">
                     <li v-for="category in currentCategories" :key="category.id">
                        <button
                           type="button"
                           data-category-item
                           class="w-full flex items-center justify-between gap-3 px-4 py-3.5 text-left cursor-pointer active:bg-(--z-muted) transition-colors"
                           @click="selectCategory(category)"
                        >
                           <span class="text-sm font-medium">{{ category.name }}</span>
                           <span class="flex items-center gap-2 text-(--z-muted-text)">
                              <span
                                 v-if="showCounts"
                                 class="min-w-6 h-6 px-2 rounded-full bg-(--z-muted) text-xs font-semibold tabular-nums grid place-items-center"
                                 :class="{ 'opacity-50': !adsCount(category) }"
                                 :aria-label="$t('common.adsCount', adsCount(category))"
                              >
                                 {{ adsCount(category) }}
                              </span>
                              <span v-else-if="category.children?.length" class="text-xs">{{ $t("common.count", category.children.length) }}</span>
                              <ChevronRight class="size-4" />
                           </span>
                        </button>
                     </li>
                  </ul>
               </div>
            </Transition>
         </div>
      </div>
   </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, type Component } from "vue";
import { ICategory } from "@shared/types";
import { ChevronLeft, ChevronRight, LayoutGrid } from "lucide-vue-next";
import { useRoute, useRouter } from "vue-router";
import icons from "@/features/category/icons";

const route = useRoute();
const router = useRouter();

const props = defineProps<{
   categories: ICategory[];
   activeCategoryId?: number | null;
   /** Berilsa, har bir bo'limda faol e'lonlar soni ko'rsatiladi ({ [category_id]: soni }) */
   counts?: Record<number, number> | null;
}>();

// Ota bo'lim soni = o'zidagi + barcha ichki bo'limlardagi e'lonlar. Daraxt bir marta aylanib chiqiladi
const totals = computed(() => {
   const map = new Map<number, number>();
   if (!props.counts) return map;

   const walk = (category: ICategory): number => {
      const total =
         (props.counts![category.id] ?? 0) + (category.children ?? []).reduce((sum, child) => sum + walk(child), 0);
      map.set(category.id, total);
      return total;
   };
   (props.categories || []).forEach(walk);
   return map;
});

const showCounts = computed(() => Boolean(props.counts));
const adsCount = (category: ICategory) => totals.value.get(category.id) ?? 0;

const emit = defineEmits<{
   (e: "selectCategory", category: ICategory): void;
}>();

// Bazada ikonka nomi saqlanadi (masalan "Car")
function iconOf(category: ICategory): Component {
   return (icons as Record<string, Component>)[category.image] ?? LayoutGrid;
}

// --- Bo'limlar bo'ylab yurish ---
const stack = ref<ICategory[][]>([props.categories]);
const breadcrumb = ref<ICategory[]>([]);
const transitionName = ref<"slide-in" | "slide-out">("slide-in");

const currentCategories = computed(() => stack.value[stack.value.length - 1]);
const currentParent = computed(() => breadcrumb.value[breadcrumb.value.length - 1] ?? null);

function findCategoryPath(categories: ICategory[], targetId: number, path: ICategory[] = []): ICategory[] | null {
   for (const category of categories) {
      const newPath = [...path, category];
      if (category.id === targetId) return newPath;

      if (category.children?.length) {
         const result = findCategoryPath(category.children, targetId, newPath);
         if (result) return result;
      }
   }
   return null;
}

function openCategoryById(categoryId: number) {
   const path = findCategoryPath(props.categories, categoryId);
   if (!path) return;

   transitionName.value = "slide-in";
   breadcrumb.value = path.slice(0, -1);

   const newStack: ICategory[][] = [props.categories];
   for (const category of breadcrumb.value) {
      if (category.children?.length) newStack.push(category.children);
   }

   // Tanlangan bo'lim ichida yana bo'limlar bo'lsa — uni ham ochamiz
   const active = path[path.length - 1];
   if (!active.is_page && active.children?.length) {
      breadcrumb.value.push(active);
      newStack.push(active.children);
   }

   stack.value = newStack;
}

// URL'ga yozamiz: orqaga tugmasi (Telegram'niki ham) bir daraja yuqoriga qaytaradi
function rememberInUrl(category: ICategory) {
   router.push({ query: { ...route.query, category_id: category.id } });
}

function selectCategory(category: ICategory) {
   rememberInUrl(category);

   // Ichida bo'lim bo'lmasa (yoki "sahifa" bo'lsa) — tanlanadi, aks holda ichiga kiriladi
   if (category.is_page || !category.children?.length) {
      emit("selectCategory", category);
      return;
   }

   transitionName.value = "slide-in";
   breadcrumb.value.push(category);
   stack.value.push(category.children);
}

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

watch(
   () => [props.activeCategoryId, props.categories] as const,
   ([id, categories]) => {
      if (!id) goRoot();
      if (!id || !categories?.length) return;
      openCategoryById(id);
   },
   { immediate: true },
);
</script>
