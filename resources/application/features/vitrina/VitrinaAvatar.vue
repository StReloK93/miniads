<template>
   <!-- Logo; bo'lmasa — nomning bosh harflari vitrina turi rangida -->
   <span class="vitrina-avatar" :style="{ '--tone': meta.tone }">
      <img v-if="logo" :src="logo" :alt="vitrina.name" class="size-full object-cover" loading="lazy" />
      <span v-else class="vitrina-initials">{{ initials }}</span>
   </span>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { IVitrina } from "@shared/types";
import { VITRINA_TYPES, storageUrl } from "@/features/vitrina/vitrinaKit";

const props = defineProps<{ vitrina: Pick<IVitrina, "name" | "logo" | "type"> }>();

const meta = computed(() => VITRINA_TYPES[props.vitrina.type] ?? VITRINA_TYPES.shop);
const logo = computed(() => storageUrl(props.vitrina.logo));
const initials = computed(() =>
   props.vitrina.name
      .split(/[\s—-]+/)
      .filter(Boolean)
      .slice(0, 2)
      .map((word) => word[0]?.toUpperCase())
      .join(""),
);
</script>

<style scoped>
.vitrina-avatar {
   display: inline-grid;
   flex-shrink: 0;
   place-items: center;
   overflow: hidden;
   border-radius: 999px;
   /* Rang qatlami yopiq fon ustida: muqova yoki sahifa ustida turganda orqasi ko'rinib qolmaydi */
   background: linear-gradient(rgb(var(--tone) / 0.16), rgb(var(--tone) / 0.16)), var(--z-background);
   color: rgb(var(--tone));
}

.vitrina-initials {
   font-weight: 800;
   font-size: 0.9em;
   letter-spacing: -0.02em;
}
</style>
