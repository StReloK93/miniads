<template>
   <!-- Admin bloklagan hisob tahrirlay ham olmaydi (admin o'zi tahrirlasa — cheklov yo'q) -->
   <BlockedNotice v-if="authStore.user?.blocked_at && authStore.user.role !== 'admin'" />
   <main v-else-if="category" class="h-full flex flex-col">
      <aside class="-mx-4 px-4 pb-3 border-b border-(--z-border)">
         <!-- Input fokus olganda klaviaturaga joy bo'shatish uchun yig'iladi -->
         <main :class="[hasFocusedInput ? 'max-h-0' : 'max-h-40']" class="transition-all overflow-hidden">
            <slot name="heading" />
         </main>

         <main class="flex gap-1 items-center">
            <span v-for="(item, index) in breadcrumb" :key="item.id" class="text-sm font-bold">
               <ChevronRight v-if="index" class="size-3 font-semibold inline" /> {{ item.name }}
            </span>
         </main>
      </aside>

      <BaseForm
         v-if="inputs.length"
         :submit="submit"
         :input-configs="inputs"
         :submit-label="submitLabel"
         @submit="emit('submitted')"
      />
   </main>
   <ProductFormSkeleton v-else class="h-full" />
</template>

<script setup lang="ts">
import { computed } from "vue";
import { ChevronRight } from "lucide-vue-next";
import BaseForm from "@shared/ui/BaseForm.vue";
import { buildBreadcrumb } from "@shared/modules/helpers";
import { useFocusedInput } from "@shared/composables/useFocusInput";
import { ICategory, InputConfig } from "@shared/types";
import ProductFormSkeleton from "@/features/product/ProductFormSkeleton.vue";
import BlockedNotice from "@/features/auth/BlockedNotice.vue";
import { useAuth } from "@shared/store/useAuth";

/**
 * E'lon formasi karkasi: sarlavha, kategoriya yo'li va forma. Yaratish va tahrirlash sahifalari uchun umumiy.
 * Sahifaga xos qismlar (ma'lumot yuklash, saqlash, saqlangandan keyingi harakat) sahifaning o'zida qoladi.
 */
const props = defineProps<{
   /** Tanlangan kategoriya; null bo'lsa skeleton ko'rsatiladi */
   category: ICategory | null;
   inputs: InputConfig[];
   submit: (values: Record<string, any>) => Promise<void>;
   submitLabel?: string;
}>();

const emit = defineEmits<{ (e: "submitted"): void }>();

const authStore = useAuth();
const { hasFocusedInput } = useFocusedInput();
const breadcrumb = computed(() => buildBreadcrumb(props.category));
</script>
