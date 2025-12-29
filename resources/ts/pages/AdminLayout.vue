<template>
   <div class="flex h-dvh">
      <nav class="w-64 border-r border-surface-200 p-4">
         <RouterLink :to="{ name: 'admin-categories' }"> Bo'limlar </RouterLink>
      </nav>
      <main class="p-4 grow bg-surface-50">
         <!-- <BaseForm /> -->
         <RouterView />
      </main>
   </div>
</template>

<script setup lang="ts">
import BaseForm from "@/components/BaseForm.vue";
</script>
