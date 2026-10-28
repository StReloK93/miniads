<template>
   <p v-if="!rows.length" class="admin-muted">{{ empty }}</p>
   <div v-else class="admin-rank-list">
      <div v-for="(row, index) in visibleRows" :key="`${row.label}-${index}`" class="admin-rank-item">
         <div class="admin-rank-header">
            <span class="admin-rank-name">{{ row.label }}</span>
            <span class="admin-rank-count">{{ row.detail }}</span>
         </div>
         <div class="admin-rank-track">
            <div class="admin-rank-fill" :style="{ width: `${(row.value / max) * 100}%`, background: color }" />
         </div>
      </div>
      <button
         v-if="rows.length > limit"
         type="button"
         class="admin-text-link self-start bg-transparent border-0 p-0 cursor-pointer"
         @click="expanded = !expanded"
      >
         {{ expanded ? "Yig'ish" : `Yana ${rows.length - limit} ta` }}
      </button>
   </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";

/** Reyting ro'yxati: nom, o'ngda qisqa son, ostida nisbiy chiziq. Uzun ro'yxat avval qisqa ko'rsatiladi. */
const props = withDefaults(
   defineProps<{
      rows: { label: string; value: number; detail: string }[];
      empty: string;
      limit?: number;
      color?: string;
   }>(),
   { limit: 6, color: undefined },
);

const expanded = ref(false);
const max = computed(() => Math.max(...props.rows.map((row) => row.value), 1));
const visibleRows = computed(() => (expanded.value ? props.rows : props.rows.slice(0, props.limit)));
</script>
