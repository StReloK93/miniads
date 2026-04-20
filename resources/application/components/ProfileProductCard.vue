<template>
   <main>
      <section class="bg-(--z-card) p-1.5 rounded-(--z-rounded) select-none border border-(--z-border)">
         <main class="relative">
            <img
               v-if="props.product.images && props.product.images.length > 0"
               :src="productImage"
               class="rounded-[10px] w-full object-cover aspect-2/1"
            />
            <div
               v-else
               :style="{
                  backgroundImage: product.back_color.gradient,
               }"
               class="rounded-[10px] w-full object-cover aspect-2/1 flex items-center"
            >
               <h3 class="font-bold text-2xl text-white px-4 pt-5 text-center w-full">
                  {{ product.title }}
               </h3>
            </div>
            <div
               v-if="product.price"
               class="absolute top-2 left-2 text-sm inline-flex items-center gap-1 px-2 py-0.5 z-bg-gradient backdrop-blur-sm border rounded-full border-(--z-border)"
               :class="{ 'flex-row-reverse': product.price_type.position === 'left' }"
            >
               <span class="font-semibold">
                  {{ formatPrice(product?.price) }}
               </span>
               <span>
                  {{ product.price_type.type }}
               </span>
            </div>

            <div
               v-if="product.days"
               class="absolute bottom-2 left-2 text-sm inline-flex items-center gap-1.5 px-1 py-0.5 z-bg-gradient backdrop-blur-sm border rounded-full border-(--z-border)"
            >
               <span
                  v-for="value in product.days.max"
                  :key="value"
                  :class="[value <= product.days.current ? 'bg-(--z-primary)' : 'bg-white']"
                  class="w-1.5 h-1.5 rounded-full inline-block"
               ></span>
            </div>
            <!-- Indicator days -->
         </main>
         <main>
            <main class="flex flex-col justify-between grow pr-1">
               <main class="px-1.5 mt-2.5">
                  <h3 v-if="props.product.images && props.product.images.length > 0" class="font-medium line-clamp-1">
                     {{ product.title }}
                  </h3>
                  <aside class="text-xs my-1">
                     <span class="text-(--z-muted-text) inline-flex items-center gap-1">
                        {{ product.district?.name || "Navoiy V." }}
                        <span class="inline-flex w-1 h-1 rounded-full bg-(--z-muted-text)"></span>
                        <span>
                           {{ timeAgo(product.created_at) }}
                        </span>
                     </span>
                  </aside>
               </main>
               <main class="flex justify-end gap-2">
                  <RouterLink :to="{ name: 'product-id', params: { id: product.id } }">
                     <BaseButton size="sm" variant="text" severity="secondary" rounded icon-only>
                        <template #icon>
                           <Eye class="size-4" />
                        </template>
                     </BaseButton>
                  </RouterLink>
                  <RouterLink :to="{ name: 'edit-product', params: { product_id: product.id } }">
                     <BaseButton size="sm" variant="text" rounded icon-only>
                        <template #icon>
                           <Pen class="size-4" />
                        </template>
                     </BaseButton>
                  </RouterLink>
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
import { Eye, Pen, Route, Router, Trash } from "lucide-vue-next";
import { timeAgo } from "@/modules/Helpers";
import { IProduct } from "@shared/types";
import { computed, ref } from "vue";
import { formatPrice } from "@/modules/Helpers";
const props = defineProps<{
   product: IProduct;
}>();

const productImage = computed(() => {
   if (props.product.images && props.product.images.length > 0) {
      return `/storage/${props.product.images[0].src}`;
   }
   return "/images/no-image.png"; // Public papkadagi default rasm yo'li
});
</script>
