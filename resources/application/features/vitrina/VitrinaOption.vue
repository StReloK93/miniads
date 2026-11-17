<template>
   <!-- Tanlov kartasi (vitrina turi, mahsulotlar ko'rinishi): belgi, nom, izoh; tanlangani — tur rangidagi ramka -->
   <div class="relative">
      <button
         type="button"
         class="option"
         :class="{ 'is-active': active, 'pr-12!': $slots.aside }"
         :style="{ '--tone': tone }"
         @click="emit('select')"
      >
         <span class="option-icon"><component :is="icon" class="size-4.5" /></span>
         <span class="min-w-0 text-left">
            <span class="block text-sm font-semibold">{{ title }}</span>
            <span class="block text-xs leading-snug text-(--z-muted-text)">{{ hint }}</span>
         </span>
      </button>
      <!-- O'ng chetdagi qo'shimcha tugma (masalan "misolni ko'rish") — tanlovni o'zgartirmaydi -->
      <span v-if="$slots.aside" class="absolute right-2.5 top-1/2 -translate-y-1/2">
         <slot name="aside" />
      </span>
   </div>
</template>

<script setup lang="ts">
import type { Component } from "vue";

defineProps<{ icon: Component; title: string; hint: string; tone: string; active: boolean }>();
const emit = defineEmits<{ (e: "select"): void }>();
</script>

<style scoped>
.option {
   display: flex;
   width: 100%;
   align-items: center;
   gap: 0.75rem;
   border: 1px solid var(--z-border);
   border-radius: var(--z-rounded);
   background: var(--z-card);
   padding: 0.625rem 0.75rem;
   cursor: pointer;

   &.is-active {
      border-color: rgb(var(--tone));
      box-shadow: 0 0 0 1px rgb(var(--tone));
   }
}

.option-icon {
   display: grid;
   width: 2.25rem;
   height: 2.25rem;
   flex-shrink: 0;
   place-items: center;
   border-radius: 999px;
   background: rgb(var(--tone) / 0.14);
   color: rgb(var(--tone));
}
</style>
