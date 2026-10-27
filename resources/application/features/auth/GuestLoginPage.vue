<template>
   <NavigationPageDecorator>
      <template #header>
         <div class="flex items-center justify-between">
            <h3 class="font-bold text-xl">{{ props.title }}</h3>
            <ThemeSwitcher v-if="props.showThemeSwitcher" variant="segmented" />
         </div>
      </template>
      <template #content>
         <!-- Mehmon ham tilni o'zgartira olsin (kirishdan oldin) -->
         <LanguageSwitcher class="w-full" />
         <TelegramLoginCard class="pt-6" :title="props.cardTitle" :description="props.cardDescription" />
      </template>
   </NavigationPageDecorator>
</template>

<script setup lang="ts">
import NavigationPageDecorator from "@/layouts/NavigationPageDecorator.vue";
import TelegramLoginCard from "@/features/auth/TelegramLoginCard.vue";
import ThemeSwitcher from "@shared/ui/ThemeSwitcher.vue";
import LanguageSwitcher from "@shared/ui/LanguageSwitcher.vue";

// Mehmon ochgan bo'limlar (Profil, Sevimlilar) o'rniga ko'rsatiladi.
// Login bo'lgach store'dagi user o'zgaradi va ota sahifa o'zi asl ko'rinishiga qaytadi.
const props = defineProps<{
   title: string;
   cardTitle?: string;
   cardDescription?: string;
   showThemeSwitcher?: boolean;
}>();
</script>
