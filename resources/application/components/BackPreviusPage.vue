<template>
   <div class="flex justify-between items-center">
      <BaseButton @click="goBack" icon="pi pi-heart" iconOnly rounded severity="secondary">
         <template #icon>
            <ChevronLeft class="size-4" />
         </template>
      </BaseButton>
      <span class="text-(--z-color-text-primary)">{{ props.title }}</span>
      <span class="min-w-8">
         <slot></slot>
      </span>
   </div>
</template>

<script setup lang="ts">
import { ChevronLeft } from "lucide-vue-next";
import { useRouter } from "vue-router";

const emit = defineEmits(["close"]);
const props = withDefaults(
   defineProps<{
      title?: string;
      model?: boolean;
   }>(),
   {
      model: false,
   },
);

const router = useRouter();

const goBack = () => {
   if (props.model) return emit("close");
   // window.history.length > 1 bo'lsa, demak orqada qaysidir sahifa bor
   if (window.history.length > 1) {
      router.back(); // yoki router.go(-1)
   } else {
      // Agar istoriya bo'lmasa, Bosh sahifaga (Home) qaytaradi
      router.push("/");
   }
};
</script>
