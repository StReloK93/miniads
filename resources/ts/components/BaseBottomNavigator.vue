<template>
   <nav class="flex justify-between px-3 py-2">
      <Button icon="pi pi-home" rounded variant="text" />
      <Button icon="pi pi-clipboard" rounded variant="text" />
      <Button icon="pi pi-plus" rounded />
      <Button icon="pi pi-heart" rounded severity="danger" variant="text" />
      <BaseUserMenu />
   </nav>
</template>
<script lang="ts" setup>
import BaseUserMenu from "@components/BaseUserMenu.vue";
</script>
