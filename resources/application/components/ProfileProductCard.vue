<template>
   <BaseProductCard
      :product="product"
      :show-favorite="false"
      :show-category="false"
      :inactive="!isActive"
      time-field="created_at"
   >
      <template #top-right>
         <div
            v-if="product.days && isActive"
            class="absolute top-2 right-2 text-sm inline-flex items-center gap-1.5 px-1 py-0.5 z-bg-gradient backdrop-blur-sm border rounded-full border-(--z-border) z-10"
         >
            <CircleIndicator :current="product.days.current" :max="product.days.max" />
         </div>
      </template>

      <template #actions>
         <BaseButtonGroup class="absolute bottom-2 right-2 z-10" :options="options" />
      </template>
   </BaseProductCard>
</template>

<script setup lang="ts">
import { useRouter } from "vue-router";
import { Pen, Eye, EyeOff } from "lucide-vue-next";
import BaseButtonGroup from "@shared/ui/BaseButtonGroup.vue";
import CircleIndicator from "@shared/ui/CircleIndicator.vue";
import BaseProductCard from "@/components/BaseProductCard.vue";
import { IProduct } from "@shared/types";
import { computed } from "vue";

const router = useRouter();

const props = defineProps<{
   product: IProduct;
}>();

const emit = defineEmits<{
   (e: "deActivate", product: IProduct): void;
   (e: "activate", product: IProduct): void;
}>();

const isActive = computed(() => {
   return (props.product.days?.current ?? 0) > 0;
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
