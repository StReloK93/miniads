<template>
   <!-- Mavzu tanlagichi (ThemeSwitcher) bilan bir xil ko'rinish: uchta teng tugma -->
   <div class="inline-flex items-center rounded-xl border border-(--z-border) bg-(--z-muted) p-1 gap-1" role="group" :aria-label="$t('profile.language')">
      <button
         v-for="item in LOCALES"
         :key="item.code"
         type="button"
         class="flex-1 justify-center inline-flex items-center rounded-lg px-3 py-1.5 text-xs font-medium whitespace-nowrap transition cursor-pointer"
         :class="locale === item.code ? 'bg-(--z-card) text-(--z-foreground) shadow-sm' : 'text-(--z-muted-text) hover:text-(--z-foreground)'"
         :aria-pressed="locale === item.code"
         @click="select(item.code)"
      >
         {{ item.label }}
      </button>
   </div>
</template>

<script setup lang="ts">
import { useI18n } from "vue-i18n";
import { applyLocale, LOCALES, type Locale } from "@shared/i18n";

const { locale } = useI18n();

/**
 * Tanlov eslab qolinadi (Telegram tilidan ustun). Sahifa qayta yuklanadi: kategoriya, shahar va parametr nomlari
 * serverdan yangi tilda kelishi uchun (ular ilova ochilganda bir marta yuklanadi).
 */
function select(code: Locale) {
   if (locale.value === code) return;
   applyLocale(code, true);
   window.location.reload();
}
</script>
