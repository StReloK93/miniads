<template>
   <!-- Vitrina turi: do'kon, menyu, portfolio, narxnoma, ko'chmas mulk -->
   <div class="grid grid-cols-1 gap-2">
      <VitrinaOption
         v-for="key in VITRINA_TYPE_KEYS"
         :key="key"
         :icon="VITRINA_TYPES[key].icon"
         :title="$t(`vitrina.types.${key}`)"
         :hint="$t(`vitrina.typeHints.${key}`)"
         :tone="VITRINA_TYPES[key].tone"
         :active="model === key"
         @select="model = key"
      />
   </div>
</template>

<script setup lang="ts">
import type { VitrinaType } from "@shared/types";
import VitrinaOption from "@/features/vitrina/VitrinaOption.vue";
import { VITRINA_TYPES, VITRINA_TYPE_KEYS } from "@/features/vitrina/vitrinaKit";

const model = defineModel<VitrinaType>({ required: true });
</script>
