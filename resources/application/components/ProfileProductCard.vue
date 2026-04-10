<template>
   <main>
      <section class="bg-(--z-card) p-1.5 rounded-(--z-rounded) select-none border border-(--z-border)">
         <main class="flex gap-2">
            <img :src="productImage" @error="handleImageError" class="rounded-[10px] w-30 object-cover aspect-square" />
            <main class="flex flex-col justify-between grow pr-1">
               <aside>
                  <h3 class="font-extrabold line-clamp-1">{{ product.title }}</h3>
                  <div v-if="product.price">
                     <span
                        class="inline-flex items-center gap-1"
                        :class="{ 'flex-row-reverse': product.price_type.position === 'left' }"
                     >
                        <span>
                           {{ formatPrice(product?.price) }}
                        </span>
                        <span>
                           {{ product.price_type.type }}
                        </span>
                     </span>
                  </div>
               </aside>
               <main class="flex justify-end gap-2">
                  <RouterLink :to="{ name: 'product-id', params: { id: product.id } }">
                     <BaseButton size="sm" variant="text" severity="secondary" rounded icon-only>
                        <template #icon>
                           <Eye class="size-4" />
                        </template>
                     </BaseButton>
                  </RouterLink>
                  <BaseButton size="sm" variant="text" rounded icon-only>
                     <template #icon>
                        <Pen class="size-4" />
                     </template>
                  </BaseButton>
                  <BaseButton size="sm" severity="danger" variant="text" rounded icon-only>
                     <template #icon>
                        <Trash class="size-4" />
                     </template>
                  </BaseButton>
               </main>
            </main>
         </main>
      </section>
   </main>
</template>

<script setup lang="ts">
import { Eye, Pen, Route, Trash } from "lucide-vue-next";
import { timeAgo } from "@/modules/Helpers";
import { IProduct } from "@shared/types";
import { computed, ref } from "vue";
import FavoriteRepo from "@shared/entities/Favotire/FavoriteRepo";
import { formatPrice } from "@/modules/Helpers";
const props = defineProps<{
   product: IProduct;
}>();

const isFavoriteButtonLoading = ref(false);

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
