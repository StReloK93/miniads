<template>
   <ProductCard
      :product="product"
      :show-favorite="false"
      :show-category="false"
      show-views
      :inactive="!isActive"
      time-field="published_at"
   >
      <!-- Qolgan kunlar va amallar -->
      <template #footer>
         <div class="mt-3 pt-3 border-t border-(--z-border) flex items-center justify-between gap-3" @click.stop>
            <!-- Egasi "Sotildi" deb to'xtatgan (qayta faollashtirsa yo'qoladi) -->
            <p v-if="product.sold_at && !isActive" class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600 dark:text-emerald-400">
               <CircleCheck class="size-3.5" /> {{ $t("myAds.soldBadge") }}
            </p>
            <ListingDaysLeft
               v-else-if="product.days"
               :current="product.days.current"
               :max="product.days.max"
               :permanent="product.days.permanent"
            />
            <!-- Muddati tugayotganda: bir bosishda uzaytirish (o'chirib-yoqish shart emas) -->
            <BaseButton
               v-if="canExtend"
               class="shrink-0"
               size="xs"
               severity="glass"
               rounded
               :loading="extending"
               @click="emit('extend', product)"
            >
               <template #icon><RefreshCw class="size-3.5" /></template>
               {{ $t("myAds.extend") }}
            </BaseButton>
            <BaseButtonGroup class="shrink-0 ml-auto" size="sm" :options="options" />
         </div>
      </template>
   </ProductCard>
</template>

<script setup lang="ts">
import { useRouter } from "vue-router";
import { Pen, Eye, EyeOff, Trash2, CircleCheck, RefreshCw } from "lucide-vue-next";
import BaseButton from "@shared/ui/BaseButton.vue";
import BaseButtonGroup, { ButtonOption } from "@shared/ui/BaseButtonGroup.vue";
import ListingDaysLeft from "@/features/profile/ListingDaysLeft.vue";
import ProductCard from "@/features/product/ProductCard.vue";
import { IProduct } from "@shared/types";
import { computed } from "vue";

const router = useRouter();

const props = defineProps<{
   product: IProduct;
   /** Uzaytirish so'rovi ketyapti */
   extending?: boolean;
}>();

const emit = defineEmits<{
   (e: "deActivate", product: IProduct): void;
   (e: "activate", product: IProduct): void;
   (e: "delete", product: IProduct): void;
   (e: "extend", product: IProduct): void;
}>();

// Shuncha kun (yoki kamroq) qolsa "Uzaytirish" tugmasi chiqadi — eslatma ham shu oraliqda keladi
const EXTEND_DAYS = 3;

const isActive = computed(() => Boolean(props.product.days?.permanent) || (props.product.days?.current ?? 0) > 0);

const canExtend = computed(
   () => isActive.value && !props.product.days?.permanent && (props.product.days?.current ?? 0) <= EXTEND_DAYS,
);

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

   if (!isActive.value) {
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
