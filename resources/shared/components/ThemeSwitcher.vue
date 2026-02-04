<template>
   <div class="flex gap-2">
      <BaseButton
         v-for="button in buttons"
         :key="button.mode"
         :class="{ 'text-primary!': mode === button.mode }"
         severity="secondary"
         @click="changeTheme(button.mode as ThemeMode)"
         icon-only
      >
         <template #icon>
            <component :is="button.icon" class="size-5" />
         </template>
      </BaseButton>
   </div>
</template>

<script setup lang="ts">
import { SunIcon, MoonIcon, DevicePhoneMobileIcon } from "@heroicons/vue/24/outline";
import { ref } from "vue";
import { applyTheme, type ThemeMode } from "@shared/components/theme";

const mode = ref<ThemeMode>((localStorage.getItem("theme-mode") as ThemeMode) || "system");

function changeTheme(value: ThemeMode): void {
   mode.value = value;
   applyTheme(value);
}

const buttons = [
   { mode: "system", icon: DevicePhoneMobileIcon },
   { mode: "light", icon: SunIcon },
   { mode: "dark", icon: MoonIcon },
];
</script>
