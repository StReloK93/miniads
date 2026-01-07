<template>
   <main>
      <div class="flex items-center gap-3">
         <Button
            @click="openUserSettings = true"
            icon="pi pi-map-marker"
            severity="secondary"
            size="small"
            rounded
            aria-label="Star"
         />
         <span class="text-sm text-surface-500">Uchquduq</span>
      </div>
      <Drawer
         v-model:visible="openUserSettings"
         style="height: auto; max-height: 70%"
         class="headless-drawer"
         position="bottom"
         :show-close-icon="false"
      >
         <div class="pt-4 pb-safe-bottom">
            <ThemeSwitcher />
         </div>
      </Drawer>
   </main>
</template>

<script setup lang="ts">
import { ref } from "vue";
import ThemeSwitcher from "./features/ThemeSwitcher.vue";
const openUserSettings = ref(false);
</script>
