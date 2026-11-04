<template>
   <!-- E'lonlar soni: element burchagida kichik shisha doiracha; 99 dan ko'pi "99+" -->
   <span v-if="count > 0" class="count-badge">{{ count > 99 ? "99+" : count }}</span>
</template>

<script setup lang="ts">
defineProps<{ count: number }>();
</script>

<style scoped>
.count-badge {
   position: absolute;
   z-index: 1;
   min-width: 1.125rem;
   height: 1.125rem;
   padding: 0 0.3rem;
   border-radius: 999px;
   background-color: var(--z-glass-strong, var(--z-card));
   box-shadow:
      inset 0 0 0 1px var(--z-glass-border, var(--z-border)),
      0 2px 6px -2px rgb(15 23 42 / 0.25);
   color: var(--z-foreground);
   font-size: 0.625rem;
   font-weight: 700;
   line-height: 1.125rem;
   text-align: center;
   white-space: nowrap;
   font-variant-numeric: tabular-nums;
   pointer-events: none;
   -webkit-backdrop-filter: blur(8px);
   backdrop-filter: blur(8px);
}
</style>
