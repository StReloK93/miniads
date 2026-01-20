<template>
   <nav class="flex justify-between px-3 pt-2 pb-safe-bottom">
      <RouterLink :to="{ name: 'home' }">
         <Button icon="pi pi-home" rounded variant="text" />
      </RouterLink>
      <Button icon="pi pi-clipboard" rounded variant="text" />

      <Button @click="visible = true" icon="pi pi-plus" rounded />
      <Button icon="pi pi-heart" rounded severity="danger" variant="text" />
      <BaseUserMenu />
      <BottomDrawer v-model="visible" fullScreen>
         <CreateProductForm @close="visible = false" />
      </BottomDrawer>
   </nav>
</template>
<script lang="ts" setup>
import BottomDrawer from "./ui/BottomDrawer.vue";
import BaseUserMenu from "@components/BaseUserMenu.vue";
import CreateProductForm from "@/entities/Product/CreateProductForm.vue";
import { ref } from "vue";
const visible = ref(false);
</script>
