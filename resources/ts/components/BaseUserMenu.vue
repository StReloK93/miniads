<template>
   <Button @click="openUserSettings = true" icon="pi pi-bars" rounded variant="text" />
   <Drawer v-model:visible="openUserSettings" class="headless-drawer" position="right" :show-close-icon="false">
      <div class="pt-4">
         <ThemeSwitcher />
      </div>
   </Drawer>
</template>

<script setup lang="ts">
import { ref } from "vue";
import ThemeSwitcher from "./features/ThemeSwitcher.vue";
const openUserSettings = ref(false);
</script>
