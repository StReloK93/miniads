<template>
   <ProductCard
      :product="product"
      :show-favorite="false"
      :show-category="false"
      :inactive="!isActive"
      time-field="created_at"
   >
      <!-- Qolgan kunlar va amallar -->
      <template #footer>
         <div class="mt-3 pt-3 border-t border-(--z-border) flex items-center justify-between gap-3" @click.stop>
            <ListingDaysLeft v-if="product.days" :current="product.days.current" :max="product.days.max" />
            <BaseButtonGroup class="shrink-0 ml-auto" size="sm" :options="options" />
         </div>
      </template>
   </ProductCard>
</template>

<script setup lang="ts">
import { useRouter } from "vue-router";
import { Pen, Eye, EyeOff, Trash2 } from "lucide-vue-next";
import BaseButtonGroup, { ButtonOption } from "@shared/ui/BaseButtonGroup.vue";
import ListingDaysLeft from "@/features/profile/ListingDaysLeft.vue";
import ProductCard from "@/features/product/ProductCard.vue";
import { IProduct } from "@shared/types";
import { computed } from "vue";

const router = useRouter();

const props = defineProps<{
   product: IProduct;
}>();

const emit = defineEmits<{
   (e: "deActivate", product: IProduct): void;
   (e: "activate", product: IProduct): void;
   (e: "delete", product: IProduct): void;
}>();

const isActive = computed(() => {
   return (props.product.days?.current ?? 0) > 0;
});

const options = computed(() => {
   const items: ButtonOption[] = [
      {
         value: "edit",
         icon: Pen,
         onClick: () => {
            router.push({ name: "edit-product", params: { product_id: props.product.id } });
         },
      },
      {
         value: "delete",
         icon: Trash2,
         class: "text-rose-500 hover:text-rose-600 focus:bg-rose-500/10",
         onClick: () => {
            emit("delete", props.product);
         },
      },
   ];

   if ((props.product.days?.current ?? 0) <= 0) {
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
</script>
