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
import { Sun, Moon, Phone } from "lucide-vue-next";
import { ref } from "vue";
import { applyTheme, type ThemeMode } from "@shared/composables/useTheme";

const mode = ref<ThemeMode>((localStorage.getItem("theme-mode") as ThemeMode) || "system");

function changeTheme(value: ThemeMode): void {
   mode.value = value;
   applyTheme(value);
}

const buttons = [
   { mode: "system", icon: Phone },
   { mode: "light", icon: Sun },
   { mode: "dark", icon: Moon },
];
</script>
