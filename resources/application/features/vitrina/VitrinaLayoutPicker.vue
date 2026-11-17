<template>
   <!-- Mahsulotlar ko'rinishi: tanlanmasa (null) — vitrina turidan. Ko'zcha — matnsiz misol (tanlovni o'zgartirmaydi) -->
   <div class="grid grid-cols-1 gap-2">
      <VitrinaOption
         v-for="option in LAYOUT_OPTIONS"
         :key="option.key"
         :icon="option.icon"
         :title="$t(`vitrina.layouts.${option.key}`)"
         :hint="$t(`vitrina.layoutHints.${option.key}`)"
         :tone="VITRINA_TYPES[type].tone"
         :active="effective === option.key"
         @select="model = option.key"
      >
         <template #aside>
            <BaseButton severity="glass" rounded icon-only size="xs" :aria-label="$t('vitrina.layoutPreview')" @click="preview = option.key">
               <template #icon><Eye class="size-3.5 opacity-70" /></template>
            </BaseButton>
         </template>
      </VitrinaOption>
   </div>

   <BaseModal
      :open="preview !== null"
      :show-buttons="false"
      :title="preview ? $t(`vitrina.layouts.${preview}`) : ''"
      :description="$t('vitrina.previewTitle')"
      @close="preview = null"
   >
      <template #icon><Eye class="size-4" /></template>
      <VitrinaLayoutSample v-if="preview" :layout="preview" :style="{ '--tone': VITRINA_TYPES[type].tone }" />
   </BaseModal>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { Eye, LayoutGrid, List } from "lucide-vue-next";
import BaseButton from "@shared/ui/BaseButton.vue";
import BaseModal from "@shared/ui/BaseModal.vue";
import type { VitrinaType } from "@shared/types";
import VitrinaOption from "@/features/vitrina/VitrinaOption.vue";
import VitrinaLayoutSample from "@/features/vitrina/VitrinaLayoutSample.vue";
import { VITRINA_TYPES } from "@/features/vitrina/vitrinaKit";

const props = defineProps<{ type: VitrinaType }>();
const model = defineModel<"grid" | "list" | null>({ required: true });

const LAYOUT_OPTIONS = [
   { key: "grid", icon: LayoutGrid },
   { key: "list", icon: List },
] as const;

const effective = computed(() => model.value ?? VITRINA_TYPES[props.type].layout);
const preview = ref<"grid" | "list" | null>(null);
</script>
