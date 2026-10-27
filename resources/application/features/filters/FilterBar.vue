<template>
   <div class="flex items-center gap-2">
      <button type="button" class="filter-pill" :class="{ 'is-active': activeCount > 0 }" @click="emit('open')">
         <SlidersHorizontal class="size-4" />
         Filtrlar
         <span v-if="activeCount" class="filter-pill-count">{{ activeCount }}</span>
      </button>

      <span v-if="total !== undefined" class="ml-auto text-xs text-(--z-muted-text) whitespace-nowrap tabular-nums">
         {{ total }} ta e'lon
      </span>
   </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { SlidersHorizontal } from "lucide-vue-next";
import { countActiveFilters, type ListFilters } from "@/features/filters/listFilters";

const props = defineProps<{ filters: ListFilters; total?: number }>();
const emit = defineEmits<{ (e: "open"): void }>();

const activeCount = computed(() => countActiveFilters(props.filters));
</script>

<style scoped>
.filter-pill {
   display: inline-flex;
   height: 2.25rem;
   align-items: center;
   gap: 0.375rem;
   border: 1px solid var(--z-glass-border);
   border-radius: 999px;
   background: var(--z-card);
   box-shadow: var(--z-glass-edge, none);
   padding: 0 0.875rem;
   color: var(--z-foreground);
   font-size: 0.8125rem;
   font-weight: 500;
   white-space: nowrap;
   cursor: pointer;
   transition: transform var(--duration) var(--ease-soft);

   &:active {
      transform: scale(0.96);
   }

   &.is-active {
      border-color: var(--z-primary);
   }
}

.filter-pill-count {
   display: grid;
   min-width: 1.25rem;
   height: 1.25rem;
   place-items: center;
   border-radius: 999px;
   background: var(--z-primary);
   padding: 0 0.3rem;
   color: var(--z-primary-foreground);
   font-size: 0.6875rem;
   font-weight: 700;
}
</style>
