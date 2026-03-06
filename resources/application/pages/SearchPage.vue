<template>
   <NavigationPageDecorator>
      <template #header>
         <h3 class="font-bold text-xl">Qidiruv</h3>
         <article class="flex gap-4 p-4 border-b border-(--z-border) -mx-4">
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
               <BaseButton icon-only>
                  <template #icon>
                     <SlidersHorizontal class="size-5" @click="isFilterOpen = true" />
                  </template>
               </BaseButton>
            </main>
         </article>
      </template>
      <template #content>
         <BaseModal
            :open="isFilterOpen"
            title="Filterlash"
            confirm-text="Saqlash"
            cancel-text="Yopish"
            :danger="true"
            @close="isFilterOpen = false"
         >
            <template #icon>
               <SlidersHorizontal class="size-4" />
            </template>

            <FieldSelect name="city" :options="['Uchquduq', 'Zarafshon', 'Navoiy']" />
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
                  <button @click.stop="removeSearch(search)" class="h-8 w-8 inline-flex items-center justify-center">
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
import { Search, SlidersHorizontal, X } from "lucide-vue-next";
import { useFetchDecorator } from "@shared/composables/useFetch";
import { useRecentSearches } from "@shared/composables/useRecentSearch";
import { computed, ref } from "vue";
import { IProduct } from "@shared/types";

const isFilterOpen = ref(false);
const {
   data: products,
   execute: fetchProducts,
   isFirstLoading,
   isLoading,
} = useFetchDecorator<IProduct[]>(ProductRepo.search);
const { searches, addSearch, clear, removeSearch } = useRecentSearches();
const searchText = ref<string>("");

const showRecent = computed(() => {
   return !products.value?.length && !searchText.value.length && searches.value.length;
});
async function onSubmit() {
   await fetchProducts(searchText.value);
   addSearch(searchText.value);
}

async function setOldSearch(text) {
   searchText.value = text;
   await fetchProducts(searchText.value);
}
</script>
