<template>
   <NavigationPageDecorator>
      <template #header>
         <div class="flex items-center justify-between">
            <h3 class="font-bold text-xl">Qidiruv</h3>
         </div>
         <article class="flex gap-4 pt-4 pb-4">
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
                        <SearchIcon class="size-4.5 inline" />
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
            <main class="w-12">
               <BaseButton
                  @click="isFilterOpen = true"
                  icon-only
                  :severity="hasFilters ? 'primary' : 'secondary'"
               >
                  <template #icon>
                     <SlidersHorizontal class="size-5" />
                  </template>
               </BaseButton>
            </main>
         </article>

         <aside v-if="hasFilters" class="-mt-2">
            <h3 class="text-(--z-muted-text) text-sm flex items-center justify-between">
               <span>Filterlar</span>
               <BaseButton
                  icon-only
                  class="h-6! relative top-px"
                  size="sm"
                  rounded
                  variant="text"
                  @click="resetFilters"
               >
                  <template #icon><X class="size-3" /></template>
               </BaseButton>
            </h3>
            <div class="mb-4 text-[11px] inline-flex items-center flex-wrap gap-2">
               <span v-if="filters.city_id">
                  <b>
                     {{ cityStore.cities?.find((city) => city.id == filters.city_id)?.name }}
                  </b>
               </span>
               <span
                  v-if="(filters?.price_from || filters?.price_to) && filters.city_id"
                  class="inline-flex w-1 h-1 rounded-full bg-(--z-muted-text)"
               ></span>
               <span v-if="filters?.price_from">
                  <b>{{ filters.price_from }}</b> dan
               </span>
               <span v-else> <b>0</b> dan </span>
               <span> - </span>
               <span v-if="filters?.price_to">
                  <b>
                     {{ filters.price_to }}
                  </b>
                  gacha
               </span>
               <span v-else class="inline-flex items-center gap-1"> <Infinity class="size-4" /> gacha </span>
            </div>
         </aside>
         <div class="border-b border-(--z-border) -mx-4 px-4"></div>
      </template>
      <template #content>
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

                  <BaseButton type="submit" class="w-full"> Tasdiqlash </BaseButton>
               </div>
            </Form>
         </BaseModal>

         <!-- 1. Qidiruv jarayoni -->
         <div v-if="isLoading" class="py-16 flex flex-col items-center justify-center gap-3">
            <div class="w-8 h-8 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
            <span class="text-sm text-(--z-muted-text)">E'lonlar qidirilmoqda...</span>
         </div>

         <!-- 2. Qidiruv natijalari -->
         <template v-else-if="products?.length">
            <div class="flex items-center justify-between pb-3 text-xs text-(--z-muted-text)">
               <span>Topilgan e'lonlar: <b class="text-(--z-foreground)">{{ products.length }}</b> ta</span>
            </div>
            <div class="grid grid-cols-1 gap-4">
               <BaseProductCard v-for="product in products" :key="product.id" :product="product" />
            </div>
         </template>

         <!-- 3. So'rov bo'yicha hech narsa topilmagan holat -->
         <template v-else-if="activeSearchText">
            <div class="py-10 flex flex-col items-center justify-center text-center px-2">
               <div class="w-16 h-16 rounded-full bg-(--z-card) border border-(--z-border) flex items-center justify-center mb-4 text-(--z-muted-text)">
                  <SearchX class="size-8" />
               </div>
               <h3 class="text-base font-bold mb-1">Hech nima topilmadi</h3>
               <p class="text-xs text-(--z-muted-text) max-w-xs mb-5">
                  "<b>{{ activeSearchText }}</b>" so'rovi bo'yicha e'lon topilmadi. So'zni to'g'ri yozganingizni tekshiring yoki quyidagi ommabop so'rovlarni sinab ko'ring.
               </p>

               <BaseButton v-if="hasFilters" @click="resetFilters" severity="secondary" size="sm" class="mb-5">
                  Filtrlarni tozalash
               </BaseButton>

               <div class="w-full text-left mt-2 border-t border-(--z-border) pt-4">
                  <div class="flex items-center gap-1.5 text-xs font-semibold text-(--z-muted-text) mb-3">
                     <Flame class="size-3.5 text-amber-500 inline" />
                     <span>Ommabop so'rovlar</span>
                  </div>
                  <div class="flex gap-2 flex-wrap">
                     <button
                        v-for="tag in popularTags"
                        :key="tag"
                        @click="setOldSearch(tag)"
                        type="button"
                        class="px-3.5 py-1.5 bg-(--z-card) border border-(--z-border) hover:border-amber-500/40 rounded-full text-xs font-medium active:scale-95 transition flex items-center gap-1.5 shadow-xs"
                     >
                        <Sparkles class="size-3 text-amber-500/70 inline" />
                        {{ tag }}
                     </button>
                  </div>
               </div>
            </div>
         </template>

         <!-- 4. Qidiruv boshlang'ich holati (bo'sh turganida yordamchi bo'limlar) -->
         <div v-else class="flex flex-col gap-6 pt-1">
            <!-- Oxirgi qidiruvlar -->
            <aside v-if="searches.length">
               <article class="flex justify-between items-center mb-2.5">
                  <div class="flex items-center gap-1.5 text-xs font-semibold text-(--z-muted-text)">
                     <History class="size-3.5 inline" />
                     <span>Oxirgi qidiruvlar</span>
                  </div>
                  <button @click="clear" class="text-xs text-red-500 font-medium active:opacity-70">Tozalash</button>
               </article>
               <main class="flex gap-2 flex-wrap">
                  <div
                     v-for="search in searches"
                     @click="setOldSearch(search)"
                     :key="search"
                     class="bg-(--z-card) border border-(--z-border) text-(--z-foreground) rounded-full text-xs font-medium inline-flex gap-1.5 items-center pl-3 pr-1 py-1 cursor-pointer active:scale-95 transition shadow-xs"
                  >
                     <span>{{ search }}</span>
                     <button
                        @click.stop="removeSearch(search)"
                        class="size-5 rounded-full hover:bg-(--z-muted) inline-flex items-center justify-center text-(--z-muted-text)"
                        aria-label="O'chirish"
                     >
                        <X class="size-3" />
                     </button>
                  </div>
               </main>
            </aside>

            <!-- Ommabop qidiruvlar -->
            <aside>
               <div class="flex items-center gap-1.5 text-xs font-semibold text-(--z-muted-text) mb-2.5">
                  <Flame class="size-3.5 text-amber-500 inline" />
                  <span>Ommabop qidiruvlar</span>
               </div>
               <div class="flex gap-2 flex-wrap">
                  <button
                     v-for="tag in popularTags"
                     :key="tag"
                     @click="setOldSearch(tag)"
                     type="button"
                     class="px-3.5 py-1.5 bg-(--z-card) border border-(--z-border) hover:border-amber-500/40 rounded-full text-xs font-medium active:scale-95 transition flex items-center gap-1.5 shadow-xs"
                  >
                     <Sparkles class="size-3 text-amber-500/70 inline" />
                     {{ tag }}
                  </button>
               </div>
            </aside>

            <!-- Bo'limlar / Kategoriyalar -->
            <aside v-if="categoryStore.parentCategories?.length">
               <div class="flex items-center justify-between mb-2.5">
                  <div class="flex items-center gap-1.5 text-xs font-semibold text-(--z-muted-text)">
                     <Layers class="size-3.5 inline" />
                     <span>Bo'limlar</span>
                  </div>
                  <button
                     type="button"
                     @click="router.push({ name: 'categories' })"
                     class="text-xs text-primary font-medium flex items-center gap-0.5 active:opacity-70"
                  >
                     Barchasi
                     <ChevronRight class="size-3 inline" />
                  </button>
               </div>
               <div class="grid grid-cols-2 gap-2">
                  <div
                     v-for="cat in categoryStore.parentCategories.slice(0, 6)"
                     :key="cat.id"
                     @click="quickSearchByCategory(cat)"
                     class="p-2.5 bg-(--z-card) border border-(--z-border) hover:border-primary/40 rounded-xl flex items-center justify-between cursor-pointer active:scale-[0.98] transition shadow-xs"
                  >
                     <span class="text-xs font-medium truncate">{{ cat.name }}</span>
                     <ArrowUpRight class="size-3.5 text-(--z-muted-text) shrink-0" />
                  </div>
               </div>
            </aside>

            <!-- Shaharlar bo'yicha tezkor filter -->
            <aside v-if="cityStore.cities?.length">
               <div class="flex items-center gap-1.5 text-xs font-semibold text-(--z-muted-text) mb-2.5">
                  <MapPin class="size-3.5 inline" />
                  <span>Hududlar bo'yicha</span>
               </div>
               <div class="flex gap-2 flex-wrap">
                  <button
                     v-for="city in cityStore.cities"
                     :key="city.id"
                     @click="quickFilterCity(city.id)"
                     type="button"
                     :class="filters.city_id === city.id ? 'bg-primary text-white border-primary' : 'bg-(--z-card) border-(--z-border) text-(--z-foreground)'"
                     class="px-3 py-1.5 border rounded-full text-xs font-medium active:scale-95 transition shadow-xs"
                  >
                     {{ city.name }}
                  </button>
               </div>
            </aside>
         </div>
      </template>
   </NavigationPageDecorator>
</template>

<script setup lang="ts">
import BaseProductCard from "@/components/BaseProductCard.vue";
import { Form } from "vee-validate";
import ProductRepo from "@shared/entities/Product/ProductRepo";
import NavigationPageDecorator from "@/components/NavigationPageDecorator.vue";
import {
   Search as SearchIcon,
   SlidersHorizontal,
   X,
   Infinity,
   Flame,
   Sparkles,
   History,
   Layers,
   MapPin,
   SearchX,
   ChevronRight,
   ArrowUpRight,
} from "lucide-vue-next";
import { useFetchDecorator } from "@shared/composables/useFetch";
import { useRecentSearches } from "@shared/composables/useRecentSearch";
import { computed, ref } from "vue";
import { ICategory, IProduct } from "@shared/types";
import { useCity } from "@shared/entities/District/useCity";
import { useCategory } from "@shared/entities/Category/useCategory";
import { useRouter } from "vue-router";

const router = useRouter();
const cityStore = useCity();
const categoryStore = useCategory();

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

const popularTags = [
   "Kvartira",
   "Ish",
   "Cobalt",
   "Gentra",
   "Ijara",
   "Telefon",
   "Hovli",
   "Damas",
   "Xizmatlar",
   "Mebel",
];

async function submitFilter(params: any) {
   filters.value = { ...filters.value, ...params };
   if (searchText.value) {
      await fetchProducts({ search: searchText.value, ...filters.value });
   }
   isFilterOpen.value = false;
}

function resetFilters() {
   filters.value = { price_from: null, price_to: null, city_id: null };
   if (activeSearchText.value) {
      fetchProducts({ search: activeSearchText.value });
   }
}

async function onSubmit() {
   if (!searchText.value.trim()) return;
   await fetchProducts({ search: searchText.value.trim(), ...filters.value });
   addSearch(searchText.value.trim());
   activeSearchText.value = searchText.value.trim();
}

async function setOldSearch(text: string) {
   if (!text.trim()) return;
   activeSearchText.value = text.trim();
   searchText.value = text.trim();
   addSearch(text.trim());
   await fetchProducts({ search: text.trim(), ...filters.value });
}

function quickSearchByCategory(cat: ICategory) {
   if (cat.is_page) {
      router.push({ name: "category", params: { id: cat.id } });
   } else {
      setOldSearch(cat.name);
   }
}

async function quickFilterCity(cityId: number) {
   if (filters.value.city_id === cityId) {
      filters.value.city_id = null;
   } else {
      filters.value.city_id = cityId;
   }

   if (activeSearchText.value) {
      await fetchProducts({ search: activeSearchText.value, ...filters.value });
   }
}

const hasFilters = computed(() => {
   return Object.values(filters.value || {}).some(Boolean);
});

function clearSearch() {
   searchText.value = "";
   activeSearchText.value = "";
   filters.value = { price_from: null, price_to: null, city_id: null };
   if (products.value) {
      products.value = [];
   }
}
</script>
