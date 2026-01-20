<template>
   <Button @click="openUserSettings = true" icon="pi pi-bars" rounded variant="text" />
   <BottomDrawer v-model="openUserSettings">
      <main class="p-4">
         <ThemeSwitcher />
      </main>
   </BottomDrawer>
</template>

<script setup lang="ts">
import BottomDrawer from "./ui/BottomDrawer.vue";
import { ref } from "vue";
import ThemeSwitcher from "./ThemeSwitcher.vue";
const openUserSettings = ref(false);
</script>
