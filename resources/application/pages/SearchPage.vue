<template>
   <NavigationPageDecorator>
      <template #header>
         <div class="flex items-center justify-between">
            <h3 class="font-bold text-xl">Qidiruv</h3>
         </div>
         <article class="flex gap-3 pt-3 pb-3">
            <Form @submit="onSubmit" class="grow flex" autocomplete="off">
               <FieldText
                  v-model="searchText"
                  autocomplete="off"
                  placeholder="Nima qidiryabsiz?"
                  name="search"
                  type="search"
               >
                  <template #left>
                     <div class="px-2.5">
                        <SearchIcon class="size-4.5 inline text-(--z-muted-text)" />
                     </div>
                  </template>
                  <template #right>
                     <BaseButton
                        tabindex="0"
                        v-if="searchText.length"
                        @mousedown.prevent="clearSearch"
                        type="button"
                        icon-only
                        rounded
                        class="h-10!"
                        :loading="isLoading"
                        variant="text"
                     >
                        <template #icon>
                           <X class="size-4 inline" />
                        </template>
                     </BaseButton>
                  </template>
               </FieldText>
            </Form>
            <main class="w-11 shrink-0">
               <BaseButton
                  @click="isFilterOpen = true"
                  icon-only
                  :severity="hasFilters ? 'primary' : 'secondary'"
                  class="relative w-full h-full"
               >
                  <template #icon>
                     <SlidersHorizontal class="size-4.5" />
                  </template>
                  <span
                     v-if="hasFilters"
                     class="absolute top-1.5 right-1.5 size-2 bg-sky-500 rounded-full"
                  ></span>
               </BaseButton>
            </main>
         </article>

         <!-- Tanlangan filtrlar nishonlari -->
         <div v-if="hasFilters" class="flex items-center gap-1.5 flex-wrap pb-3 text-xs">
            <span
               v-if="filters.city_id"
               class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-(--z-card) border border-(--z-border) text-(--z-foreground)"
            >
               <MapPin class="size-3 text-(--z-muted-text)" />
               {{ cityStore.cities?.find((city) => city.id == filters.city_id)?.name }}
               <button
                  type="button"
                  @click="filters.city_id = null; applyFilters()"
                  class="hover:text-red-500 ml-0.5 cursor-pointer"
               >
                  <X class="size-3" />
               </button>
            </span>

            <span
               v-if="filters.price_from || filters.price_to"
               class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-(--z-card) border border-(--z-border) text-(--z-foreground)"
            >
               <span v-if="filters.price_from && filters.price_to">
                  {{ filters.price_from }} - {{ filters.price_to }} so'm
               </span>
               <span v-else-if="filters.price_from">
                  {{ filters.price_from }} dan
               </span>
               <span v-else>
                  {{ filters.price_to }} gacha
               </span>
               <button
                  type="button"
                  @click="filters.price_from = null; filters.price_to = null; applyFilters()"
                  class="hover:text-red-500 ml-0.5 cursor-pointer"
               >
                  <X class="size-3" />
               </button>
            </span>

            <button
               type="button"
               @click="resetFilters"
               class="text-xs text-(--z-muted-text) hover:text-red-500 underline ml-1 cursor-pointer"
            >
               Tozalash
            </button>
         </div>

         <div class="border-b border-(--z-border) -mx-4 px-4"></div>
      </template>

      <template #content>
         <!-- Filtr modali -->
         <BaseModal
            :open="isFilterOpen"
            title="Filterlash"
            confirm-text="Saqlash"
            cancel-text="Yopish"
            :danger="true"
            :show-buttons="false"
            @close="isFilterOpen = false"
         >
            <template #icon>
               <SlidersHorizontal class="size-4" />
            </template>

            <Form @submit="submitFilter">
               <div class="mb-4">
                  <p class="mb-1 text-sm tracking-wide">Shaharni tanlang</p>
                  <FieldSelect name="city_id" :options="cityStore.cities!" value="name" />
               </div>
               <div class="mb-4">
                  <p class="mb-1 text-sm tracking-wide">Narx</p>
                  <main class="flex gap-4">
                     <FieldNumber name="price_from" placeholder="0 So'mdan" class="mb-4" />
                     <FieldNumber name="price_to" placeholder="1 000 So'mgacha" class="mb-4" />
                  </main>
               </div>
               <div class="mt-8 flex gap-4">
                  <BaseButton @click="isFilterOpen = false" type="button" severity="glass" class="w-full">
                     Bekor qilish
                  </BaseButton>

                  <BaseButton type="submit" class="w-full">
                     Tasdiqlash
                  </BaseButton>
               </div>
            </Form>
         </BaseModal>

         <!-- 1. Qidiruv jarayoni -->
         <div v-if="isLoading" class="py-16 flex flex-col items-center justify-center gap-3">
            <div class="w-8 h-8 border-2 border-(--z-primary) border-t-transparent rounded-full animate-spin"></div>
            <span class="text-sm text-(--z-muted-text)">E'lonlar qidirilmoqda...</span>
         </div>

         <!-- 2. Qidiruv natijalari -->
         <template v-else-if="products?.length">
            <div class="flex items-center justify-between pb-3 text-xs text-(--z-muted-text)">
               <span>Topilgan e'lonlar: <b class="text-(--z-foreground)">{{ products.length }}</b> ta</span>
            </div>
            <div class="grid grid-cols-1 gap-2.5">
               <ProductCard v-for="product in products" :key="product.id" :product="product" />
            </div>
         </template>

         <!-- 3. Natija topilmagan holat -->
         <template v-else-if="activeSearchText">
            <div class="py-12 flex flex-col items-center justify-center text-center px-4">
               <div class="w-14 h-14 rounded-full bg-(--z-card) border border-(--z-border) flex items-center justify-center mb-3 text-(--z-muted-text)">
                  <SearchX class="size-6" />
               </div>
               <h3 class="text-base font-bold mb-1">Hech narsa topilmadi</h3>
               <p class="text-xs text-(--z-muted-text) max-w-xs mb-4">
                  "<b>{{ activeSearchText }}</b>" bo'yicha e'lon topilmadi.
               </p>

               <BaseButton v-if="hasFilters" @click="resetFilters" severity="secondary" size="sm" class="mb-5">
                  Filtrlarni tozalash
               </BaseButton>

               <div class="w-full text-left pt-4 border-t border-(--z-border)">
                  <p class="text-xs font-semibold text-(--z-muted-text) mb-2.5 flex items-center gap-1.5">
                     <Sparkles class="size-3.5 text-amber-500" />
                     Ommabop so'rovlar
                  </p>
                  <div class="flex flex-wrap gap-2">
                     <button
                        v-for="tag in popularTags"
                        :key="tag"
                        type="button"
                        @click="setOldSearch(tag)"
                        class="px-3.5 py-1.5 bg-(--z-card) border border-(--z-border) hover:border-(--z-primary)/30 hover:bg-(--z-muted) rounded-full text-xs font-medium active:scale-95 transition cursor-pointer"
                     >
                       {{ tag }}
                     </button>
                  </div>
               </div>
            </div>
         </template>

         <!-- 4. Oddiy va toza boshlang'ich holat -->
         <div v-else class="flex flex-col gap-6 pt-2">
            <!-- Oxirgi qidiruvlar -->
            <div v-if="searches.length" class="flex flex-col gap-2.5">
               <div class="flex justify-between items-center text-xs">
                  <span class="flex items-center gap-1.5 font-medium text-(--z-muted-text)">
                     <History class="size-3.5" />
                     Oxirgi qidiruvlar
                  </span>
                  <button
                     type="button"
                     @click="clear"
                     class="text-(--z-muted-text) hover:text-red-500 transition active:opacity-70 text-xs cursor-pointer"
                  >
                     Tozalash
                  </button>
               </div>
               <div class="flex flex-wrap gap-2">
                  <div
                     v-for="search in searches"
                     :key="search"
                     @click="setOldSearch(search)"
                     class="bg-(--z-card) border border-(--z-border) text-(--z-foreground) rounded-full text-xs font-medium inline-flex items-center gap-1.5 pl-3 pr-1.5 py-1 cursor-pointer active:scale-95 transition hover:bg-(--z-muted)"
                  >
                     <span>{{ search }}</span>
                     <button
                        type="button"
                        @click.stop="removeSearch(search)"
                        class="size-4.5 rounded-full hover:bg-(--z-muted-text)/20 inline-flex items-center justify-center text-(--z-muted-text)"
                        aria-label="O'chirish"
                     >
                        <X class="size-3" />
                     </button>
                  </div>
               </div>
            </div>

            <!-- Ommabop qidiruvlar -->
            <div class="flex flex-col gap-2.5">
               <div class="flex items-center gap-1.5 text-xs font-medium text-(--z-muted-text)">
                  <Sparkles class="size-3.5 text-amber-500" />
                  <span>Ommabop qidiruvlar</span>
               </div>
               <div class="flex flex-wrap gap-2">
                  <button
                     v-for="tag in popularTags"
                     :key="tag"
                     type="button"
                     @click="setOldSearch(tag)"
                     class="px-3.5 py-1.5 bg-(--z-card) border border-(--z-border) hover:border-(--z-primary)/30 hover:bg-(--z-muted) rounded-full text-xs font-medium active:scale-95 transition cursor-pointer"
                  >
                     {{ tag }}
                  </button>
               </div>
            </div>

            <div v-if="!searches.length" class="text-center py-8 text-xs text-(--z-muted-text)">
               Kerakli mahsulot yoki xizmat nomini yozing
            </div>
         </div>
      </template>
   </NavigationPageDecorator>
</template>

<script setup lang="ts">
import ProductCard from "@/features/product/ProductCard.vue";
import { Form } from "vee-validate";
import ProductRepo from "@shared/entities/Product/ProductRepo";
import NavigationPageDecorator from "@/layouts/NavigationPageDecorator.vue";
import {
   Search as SearchIcon,
   SlidersHorizontal,
   X,
   Sparkles,
   History,
   MapPin,
   SearchX,
} from "lucide-vue-next";
import { useFetchDecorator } from "@shared/composables/useFetch";
import { useRecentSearches } from "@shared/composables/useRecentSearch";
import { computed, ref, watch } from "vue";
import { IProduct } from "@shared/types";
import { useCity } from "@shared/entities/District/useCity";

const cityStore = useCity();

const isFilterOpen = ref(false);
const filters = ref<{
   price_from: number | null;
   price_to: number | null;
   city_id: number | null;
}>({
   price_from: null,
   price_to: null,
   city_id: null,
});

const { data: products, execute: fetchProducts, isLoading } = useFetchDecorator<IProduct[]>(ProductRepo.search);
const { searches, addSearch, removeSearch, clear } = useRecentSearches();
const searchText = ref<string>("");
const activeSearchText = ref<string>("");

const popularTags = ["Kvartira", "Telefon", "Cobalt", "Gentra", "Ish", "Mebel"];

let debounceTimer: ReturnType<typeof setTimeout> | undefined;

// Foydalanuvchi yozayotganda qulay avto-qidiruv (debounce 350ms)
watch(searchText, (newVal) => {
   const trimmed = newVal.trim();
   if (!trimmed) {
      if (activeSearchText.value) {
         clearSearch();
      }
      return;
   }

   clearTimeout(debounceTimer);
   debounceTimer = setTimeout(async () => {
      if (trimmed.length >= 2) {
         activeSearchText.value = trimmed;
         await fetchProducts({ search: trimmed, ...filters.value });
      }
   }, 350);
});

async function submitFilter(params: Partial<typeof filters.value>) {
   filters.value = { ...filters.value, ...params };
   await applyFilters();
   isFilterOpen.value = false;
}

async function applyFilters() {
   if (activeSearchText.value) {
      await fetchProducts({ search: activeSearchText.value, ...filters.value });
   } else if (searchText.value.trim()) {
      activeSearchText.value = searchText.value.trim();
      await fetchProducts({ search: searchText.value.trim(), ...filters.value });
   }
}

function resetFilters() {
   filters.value = { price_from: null, price_to: null, city_id: null };
   if (activeSearchText.value) {
      fetchProducts({ search: activeSearchText.value });
   }
}

async function onSubmit() {
   clearTimeout(debounceTimer);
   const trimmed = searchText.value.trim();
   if (!trimmed) return;
   activeSearchText.value = trimmed;
   addSearch(trimmed);
   await fetchProducts({ search: trimmed, ...filters.value });
}

async function setOldSearch(text: string) {
   clearTimeout(debounceTimer);
   const trimmed = text.trim();
   if (!trimmed) return;
   searchText.value = trimmed;
   activeSearchText.value = trimmed;
   addSearch(trimmed);
   await fetchProducts({ search: trimmed, ...filters.value });
}

const hasFilters = computed(() => {
   return Object.values(filters.value || {}).some(Boolean);
});

function clearSearch() {
   clearTimeout(debounceTimer);
   searchText.value = "";
   activeSearchText.value = "";
   if (products.value) {
      products.value = [];
   }
}
</script>
