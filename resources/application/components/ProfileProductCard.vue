<template>
   <main @click="$router.push({ name: 'product-id', params: { id: product.id } })">
      <section class="bg-(--z-card) p-1.5 rounded-(--z-rounded) select-none border border-(--z-border)">
         <main class="relative rounded-[10px] overflow-hidden">
            <div v-if="!isActive" class="absolute inset-0 inactive"></div>
            <img
               v-if="props.product.images && props.product.images.length > 0"
               :src="productImage"
               class="w-full object-cover aspect-2/1"
            />
            <div
               v-else
               :style="{
                  backgroundImage: product.back_color.gradient,
               }"
               class="w-full object-cover aspect-2/1 flex items-center"
            >
               <h3 class="font-bold text-2xl text-white px-4 pt-3 text-center w-full">
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
            <!-- Indicator days -->
            <div
               v-if="product.days && isActive"
               class="absolute top-2 right-2 text-sm inline-flex items-center gap-1.5 px-1 py-0.5 z-bg-gradient backdrop-blur-sm border rounded-full border-(--z-border)"
            >
               <CircleIndicator :current="product.days.current" :max="product.days.max" />
            </div>
            <!-- Indicator days -->

            <BaseButtonGroup class="absolute bottom-2 right-2" :options="options" />
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
            </main>
         </main>
      </section>
   </main>
</template>

<script setup lang="ts">
import { useRouter } from "vue-router";
import { Pen, Eye, EyeOff } from "lucide-vue-next";
import BaseButtonGroup from "@shared/ui/BaseButtonGroup.vue";
import CircleIndicator from "@shared/ui/CircleIndicator.vue";
import { timeAgo } from "@/modules/Helpers";
import { IProduct } from "@shared/types";
import { computed, ref } from "vue";
import { formatPrice } from "@/modules/Helpers";

const router = useRouter();

const props = defineProps<{
   product: IProduct;
}>();

const emit = defineEmits<{
   (e: "deActivate", product: IProduct): void;
   (e: "activate", product: IProduct): void;
}>();

const isActive = computed(() => {
   return props.product.days.current > 0;
});

const options = computed(() => {
   const items = [
      {
         value: "edit",
         icon: Pen,
         onClick: () => {
            router.push({ name: "edit-product", params: { product_id: props.product.id } });
         },
      },
   ];

   if (props.product.days.current <= 0) {
      items.unshift({
         value: "activate",
         icon: Eye,
         onClick: () => {
            emit("activate", props.product);
         },
      });
   } else {
      items.unshift({
         value: "deactivate",
         icon: EyeOff,
         onClick: () => {
            emit("deActivate", props.product);
         },
      });
   }

   return items;
});

const productImage = computed(() => {
   if (props.product.images && props.product.images.length > 0) {
      return `/storage/${props.product.images[0].src}`;
   }
});
</script>
