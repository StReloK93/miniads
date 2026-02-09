<template>
   <main class="container mx-auto flex flex-col h-dvh w-dvw relative">
      <RouterView
         class="pt-[calc(var(--safe-area-top)+var(--spacing)*4)] pb-[calc(var(--safe-area-bottom)+var(--spacing)*2)] h-full px-4"
      />
   </main>
</template>

<script setup lang="ts"></script>
