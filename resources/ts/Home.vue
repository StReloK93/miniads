<template>
   <div class="p-4 container mx-auto">
      <!-- <ThemeSwitcher /> -->
      <BaseNavigation></BaseNavigation>
      <div class="-mx-4">
         <Divider />
      </div>
      <section class="flex justify-between items-center mb-3">
         <span class="font-semibold">Bo'limlar</span>
         <div class="text-primary cursor-pointer">Barchasini ko'rish</div>
      </section>
      <section class="mb-3 -mx-1">
         <Carousel
            :value="categories"
            :responsiveOptions="responsiveOptions"
            :showNavigators="false"
            :showIndicators="false"
            class="w-full"
         >
            <template #item="slotProps" class="visible!">
               <div class="px-1">
                  <div class="aspect-square gap-2 flex flex-col items-center justify-center bg-surface-100 rounded-2xl">
                     <img :src="`${slotProps.data.icon_path}`" class="md:w-16 w-10" />
                     <div class="text-xs font-semibold text-surface-600">
                        {{ slotProps.data.name }}
                     </div>
                  </div>
               </div>
            </template>
         </Carousel>
      </section>
      <section class="flex justify-between items-center mb-3">
         <span class="font-semibold">Yangiliklar</span>
         <div class="text-primary cursor-pointer">Barchasini ko'rish</div>
      </section>
      <section class="mb-4 -mx-1">
         <Carousel
            :value="products"
            :responsiveOptions="responsiveOptions"
            :showNavigators="false"
            :showIndicators="false"
            class="w-full"
         >
            <template #item="slotProps">
               <div class="px-1 h-full">
                  <BaseProductCard
                     :image="slotProps.data.image"
                     :title="slotProps.data.title"
                     :subtitle="slotProps.data.subtitle"
                     :price="slotProps.data.price"
                  />
               </div>
            </template>
         </Carousel>
      </section>
   </div>
</template>

<script setup lang="ts">
import BaseProductCard from "./components/BaseProductCard.vue";
import { ref } from "vue";
import BaseNavigation from "./components/BaseNavigation.vue";

interface Category {
   name: string;
   icon_path: string;
}

interface Product {
   title: string;
   subtitle: string;
   price: string;
   image: string;
}

// 2. Arrayni to'g'ri oching va objectlarni qo'shing
const categories: Category[] = [
   { name: "Transport", icon_path: "./icons/car.svg" },
   { name: "Ko'chmas mulk", icon_path: "./icons/house.svg" },
   { name: "Elektronika", icon_path: "./icons/electronics.svg" },
   { name: "Ish", icon_path: "./icons/music.svg" },
   { name: "Ko'chmas mulk", icon_path: "./icons/house.svg" },
   { name: "Transport", icon_path: "./icons/car.svg" },
   { name: "Ko'chmas mulk", icon_path: "./icons/house.svg" },
   { name: "Elektronika", icon_path: "./icons/electronics.svg" },
   { name: "Ish", icon_path: "./icons/music.svg" },
   { name: "Ko'chmas mulk", icon_path: "./icons/house.svg" },
];

const products = ref<Product[]>([
   {
      title: "Macbook Pro 16",
      subtitle: "M2 Max, 32GB RAM, 1TB SSD",
      price: "$2 499",
      image: "./images/macbook.webp",
   },
   {
      title: "iPhone 15 Pro",
      subtitle: "Titanium, 256GB",
      price: "$1 199",
      image: "https://primefaces.org/cdn/primevue/images/product/black-watch.jpg",
   },
   {
      title: "Samsung Galaxy S24",
      subtitle: "Ultra 5G, 512GB",
      price: "$1 299",
      image: "https://primefaces.org/cdn/primevue/images/product/blue-t-shirt.jpg",
   },
   {
      title: "Sony PlayStation 5",
      subtitle: "Digital Edition",
      price: "$450",
      image: "https://primefaces.org/cdn/primevue/images/product/gaming-set.jpg",
   },
   {
      title: "Canon EOS R5",
      subtitle: "Body Only, 45MP",
      price: "$3 899",
      image: "https://primefaces.org/cdn/primevue/images/product/green-earbuds.jpg",
   },
]);

const responsiveOptions = ref([
   {
      breakpoint: "2560px",
      numVisible: 8,
      numScroll: 4,
   },
   {
      breakpoint: "1400px",
      numVisible: 5,
      numScroll: 1,
   },
   {
      breakpoint: "1199px",
      numVisible: 5,
      numScroll: 1,
   },
   {
      breakpoint: "767px",
      numVisible: 3.5,
      numScroll: 1,
   },
   {
      breakpoint: "575px",
      numVisible: 2.2,
      numScroll: 1,
   },
]);
</script>

<style>
.p-carousel-item {
   visibility: visible !important;
}
</style>
