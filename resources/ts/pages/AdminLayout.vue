<template>
   <div class="flex h-dvh">
      <nav class="w-64 border-r border-surface-200 p-4">
         <RouterLink :to="{ name: 'admin-categories' }"> Kategoriyalar </RouterLink>
      </nav>
      <main class="p-4 grow bg-surface-50">
         <RouterView />
      </main>
   </div>
</template>

<script setup lang="ts"></script>
