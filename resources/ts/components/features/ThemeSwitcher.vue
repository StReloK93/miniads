<template>
   <div class="flex gap-2">
      <Button label="System" :outlined="mode !== 'system'" size="small" @click="changeTheme('system')" />
      <Button label="Light" :outlined="mode !== 'light'" size="small" @click="changeTheme('light')" />
      <Button label="Dark" :outlined="mode !== 'dark'" size="small" @click="changeTheme('dark')" />
   </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { applyTheme, type ThemeMode } from "./theme";

const mode = ref<ThemeMode>((localStorage.getItem("theme-mode") as ThemeMode) || "system");

function changeTheme(value: ThemeMode): void {
   mode.value = value;
   applyTheme(value);
}
</script>
