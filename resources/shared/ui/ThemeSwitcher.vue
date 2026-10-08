<template>
   <!-- Toggle variant (bitta ixcham tugma - Header yoki Topbar uchun ajoyib) -->
   <button
      v-if="props.variant === 'toggle'"
      type="button"
      @click="toggleTheme"
      class="group relative inline-flex size-9 items-center justify-center rounded-full border border-(--z-border) bg-(--z-card) text-(--z-primary) shadow-xs transition-all hover:bg-(--z-muted) active:scale-90 cursor-pointer"
      :title="isDark ? 'Yorug\' rejimga o\'tish' : 'Qorong\'i rejimga o\'tish'"
      aria-label="Mavzuni almashtirish"
   >
      <Transition
         mode="out-in"
         enter-active-class="transition-all duration-200 ease-out"
         enter-from-class="opacity-0 -rotate-90 scale-75"
         enter-to-class="opacity-100 rotate-0 scale-100"
         leave-active-class="transition-all duration-150 ease-in"
         leave-from-class="opacity-100 rotate-0 scale-100"
         leave-to-class="opacity-0 rotate-90 scale-75"
      >
         <Sun v-if="isDark" class="size-4.5 text-amber-400" />
         <Moon v-else class="size-4.5 text-slate-700 dark:text-slate-200" />
      </Transition>
   </button>

   <!-- Segmented variant (3 ta tanlov: Light, System, Dark - Profil / Sozlamalar uchun) -->
   <div
      v-else
      class="inline-flex items-center rounded-xl border border-(--z-border) bg-(--z-muted) p-1 gap-1"
      role="group"
      aria-label="Mavzu tanlash"
   >
      <button
         v-for="btn in buttons"
         :key="btn.mode"
         type="button"
         @click="setTheme(btn.mode)"
         :class="[
            theme === btn.mode
               ? 'bg-(--z-card) text-(--z-primary) shadow-xs font-semibold'
               : 'text-(--z-muted-text) hover:text-(--z-primary) bg-transparent',
         ]"
         class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs transition-all duration-150 active:scale-95 cursor-pointer"
      >
         <component :is="btn.icon" class="size-3.5" />
         <span>{{ btn.label }}</span>
      </button>
   </div>
</template>

<script setup lang="ts">
import { Sun, Moon, Laptop } from "lucide-vue-next";
import { useTheme, type ThemeMode } from "@shared/composables/useTheme";

const props = withDefaults(
   defineProps<{
      variant?: "toggle" | "segmented";
   }>(),
   {
      variant: "toggle",
   },
);

const { theme, isDark, setTheme, toggleTheme } = useTheme();

const buttons = [
   { mode: "light" as ThemeMode, icon: Sun, label: "Yorug'" },
   { mode: "system" as ThemeMode, icon: Laptop, label: "Tizim" },
   { mode: "dark" as ThemeMode, icon: Moon, label: "Qorong'i" },
];
</script>
