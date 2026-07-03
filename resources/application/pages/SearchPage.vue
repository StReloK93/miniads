<template>
   <NavigationPageDecorator>
      <template #header>
         <h3 class="font-bold text-xl">Qidiruv</h3>
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
                        <Search class="size-4.5 inline" />
                     </div>
                  </template>
                  <template #right>
                     <BaseButton
                        tab="0"
                        v-if="searchText.length"
                        @mousedown.prevent="setOldSearch('')"
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
               <BaseButton icon-only :severity="hasFilters ? 'primary' : 'secondary'">
                  <template #icon>
                     <SlidersHorizontal class="size-5" @click="isFilterOpen = true" />
                  </template>
               </BaseButton>
            </main>
         </article>
         <aside v-if="hasFilters" class="-mt-2">
            <h3 class="text-(--z-muted-text) text-sm mb-1.5">
               Filterlar
               <BaseButton
                  icon-only
                  class="h-6!"
                  size="sm"
                  rounded
                  variant="text"
                  @click="filters = { price_from: null, price_to: null, city_id: null }"
               >
                  ><template #icon><X class="size-3" /></template
               ></BaseButton>
            </h3>
            <div
               class="mb-4 text-[11px] inline-flex items-center flex-wrap gap-2 bg-white px-2 py-1 rounded-2xl border border-(--z-border)"
            >
               <span v-if="filters.city_id">
                  <b>
                     {{ districts?.find((city) => city.id == filters.city_id)?.name }}
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
                  <FieldSelect name="city_id" :options="districts!" value="name" />
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
         <aside v-if="showRecent">
            <article class="flex justify-between items-center">
               <h3 class="title text-xs">Oxirgi qidiruvlar</h3>
               <button @click="clear" class="text-xs font-semibold">Tozalash</button>
            </article>
            <main class="flex gap-2 flex-wrap mt-3">
               <div
                  v-for="search in searches"
                  @click="setOldSearch(search)"
                  :key="search"
                  class="bg-(--z-muted) text-(--z-foreground) rounded-full text-sm inline-flex gap-1 items-center pl-4"
               >
                  {{ search }}
                  <button
                     @click.stop="removeSearch(search)"
                     class="h-8 w-4 mx-2 inline-flex items-center justify-center"
                  >
                     <X class="size-3" />
                  </button>
               </div>
            </main>
         </aside>

         <BaseProductCard v-for="product in products" :product="product" />
         <!--  -->
      </template>
   </NavigationPageDecorator>
</template>

<script setup lang="ts">
import BaseProductCard from "@/components/BaseProductCard.vue";
import { Form } from "vee-validate";
import ProductRepo from "@shared/entities/Product/ProductRepo";
import NavigationPageDecorator from "@/components/NavigationPageDecorator.vue";
import { Search, SlidersHorizontal, X, Infinity } from "lucide-vue-next";
import { useFetchDecorator } from "@shared/composables/useFetch";
import { useRecentSearches } from "@shared/composables/useRecentSearch";
import DistrictRepo from "@shared/entities/District/DistrictRepo";
import { computed, onMounted, ref } from "vue";
import { IDistrict, IProduct } from "@shared/types";

const isFilterOpen = ref(false);
const filters = ref({
   price_from: null,
   price_to: null,
   city_id: null,
});
const {
   data: products,
   execute: fetchProducts,
   isFirstLoading,
   isLoading,
} = useFetchDecorator<IProduct[]>(ProductRepo.search);
const { searches, addSearch, clear, removeSearch } = useRecentSearches();
const searchText = ref<string>("");

const { data: districts, execute: executeDistricts } = useFetchDecorator<IDistrict[]>(DistrictRepo.index);

async function submitFilter(params) {
   filters.value = params;
   await fetchProducts({ search: searchText.value, ...params });
   isFilterOpen.value = false;
}

const showRecent = computed(() => {
   return !products.value?.length && !searchText.value.length && searches.value.length;
});
async function onSubmit() {
   await fetchProducts({ search: searchText.value, ...filters.value });
   addSearch(searchText.value);
}

async function setOldSearch(text) {
   searchText.value = text;
   await fetchProducts({ search: searchText.value });
}

const hasFilters = computed(() => {
   return Object.values(filters.value || {}).some(Boolean);
});

onMounted(() => {
   executeDistricts();
});
</script>
