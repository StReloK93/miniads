<template>
   <nav class="flex justify-between px-3 pt-2 pb-safe-bottom">
      <Button icon="pi pi-home" rounded variant="text" />
      <Button icon="pi pi-clipboard" rounded variant="text" />

      <Button @click="visible = true" icon="pi pi-plus" rounded />
      <Button icon="pi pi-heart" rounded severity="danger" variant="text" />
      <BaseUserMenu />
      <Drawer
         v-model:visible="visible"
         position="bottom"
         style="height: 100%"
         class="headless-drawer"
         :show-close-icon="false"
      >
         <CreateProductForm class="pb-safe-bottom" />
      </Drawer>
   </nav>
</template>
<script lang="ts" setup>
import BaseUserMenu from "@components/BaseUserMenu.vue";
import CreateProductForm from "@/entities/Product/CreateProductForm.vue";
import { ref } from "vue";
const visible = ref(false);
</script>
