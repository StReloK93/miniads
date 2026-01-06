<template>
   <section
      class="bg-surface-50 p-1.5 rounded-xl select-none border border-surface-100 dark:bg-surface-900 dark:border-surface-800"
   >
      <div>
         <img :src="image" class="rounded-md w-full object-cover aspect-square" />
      </div>
      <h3 class="mt-1.5 font-semibold line-clamp-1">{{ title }}</h3>
      <div class="text-sm text-surface-600 dark:text-surface-300 line-clamp-1">{{ subtitle }}</div>
      <h3 class="mt-1 text-primary font-semibold text-sm">{{ price }}</h3>
   </section>
</template>

<script setup lang="ts">
defineProps<{
   image: string;
   title: string;
   subtitle: string;
   price: string;
}>();
</script>
