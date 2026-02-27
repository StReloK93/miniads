<template>
   <main @click="$router.push({ name: 'product-id', params: { id: product.id } })">
      <section class="bg-(--z-card) p-1.5 rounded-(--z-rounded) select-none border border-(--z-border)">
         <div class="mb-2.5 relative">
            <img :src="productImage" @error="handleImageError" class="rounded-xl w-full object-cover aspect-4/2" />
            <div
               class="absolute top-3.5 left-2 text-sm inline-flex items-center gap-1 px-2 py-0.5 z-bg-gradient backdrop-blur-sm border rounded-full border-(--z-border)"
               :class="{ 'flex-row-reverse': product.price_type.position === 'left' }"
            >
               <span class="font-semibold">
                  {{ formatPrice(product?.price) }}
               </span>
               <span>
                  {{ product.price_type.type }}
               </span>
            </div>
            <!--  -->
            <BaseButton
               @click.stop=""
               class="absolute top-2 right-2 border border-(--z-border)"
               icon="pi pi-heart"
               iconOnly
               rounded
               severity="glass"
            >
               <!-- fill-red-500 text-red-500 -->
               <template #icon>
                  <Heart class="size-4" />
               </template>
            </BaseButton>
         </div>
         <main class="px-1.5">
            <h3 class="font-medium line-clamp-1">{{ product.title }}</h3>
            <aside class="text-xs my-1">
               <span class="text-(--z-muted-text) inline-flex items-center gap-1">
                  Uchquduq <span class="inline-flex w-1 h-1 rounded-full bg-(--z-muted-text)"></span>
                  <span>
                     {{ timeAgo(product.created_at) }}
                  </span>
               </span>
            </aside>
         </main>
      </section>
   </main>
</template>

<script setup lang="ts">
import { Heart } from "lucide-vue-next";
import { timeAgo } from "@/modules/Helpers";
import { IProduct } from "@shared/types";
import { computed } from "vue";
import { formatPrice } from "@/modules/Helpers";
const props = defineProps<{
   product: IProduct;
}>();

const shortParameters = computed(() => {
   return props.product.parameter_values.slice(0, 2);
});

// 1. Agar massiv bo'sh bo'lsa, default rasm yo'lini qaytaramiz
const productImage = computed(() => {
   if (props.product.images && props.product.images.length > 0) {
      return `/storage/${props.product.images[0].src}`;
   }
   return "/images/no-image.png"; // Public papkadagi default rasm yo'li
});

// 2. Agar rasm serverda topilmasa (404), ushbu funksiya ishlaydi
const handleImageError = (event) => {
   event.target.src = "/images/no-image.webp"; // Public papkadagi default rasm yo'li
};
</script>
