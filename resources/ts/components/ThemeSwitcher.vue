<template>
   <div class="flex gap-2">
      <Button
         icon="pi pi-mobile"
         :severity="mode == 'system' ? 'info' : 'secondary'"
         size="small"
         rounded
         variant="text"
         @click="changeTheme('system')"
      />
      <Button
         size="small"
         icon="pi pi-sun"
         :severity="mode == 'light' ? 'warn' : 'secondary'"
         variant="text"
         rounded
         @click="changeTheme('light')"
      />
      <Button
         icon="pi pi-moon"
         size="small"
         :severity="mode == 'dark' ? 'primary' : 'secondary'"
         variant="text"
         rounded
         @click="changeTheme('dark')"
      />
   </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { applyTheme, type ThemeMode } from "../modules/theme";

const mode = ref<ThemeMode>((localStorage.getItem("theme-mode") as ThemeMode) || "system");

function changeTheme(value: ThemeMode): void {
   mode.value = value;
   applyTheme(value);
}
</script>
