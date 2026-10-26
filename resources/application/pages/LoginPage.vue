<template>
   <main class="h-full overflow-y-auto no-scrollbar px-4 pt-[calc(var(--safe-area-top)+var(--spacing)*4)] pb-[calc(var(--safe-area-bottom)+var(--spacing)*6)]">
      <BackPreviusPage title="Kirish" />
      <TelegramLoginCard
         class="pt-10"
         :title="redirect?.startsWith('/select-category') ? 'E\'lon joylash uchun kiring' : 'Telegram orqali kiring'"
         @success="onSuccess"
      />
   </main>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import BackPreviusPage from "@/components/BackPreviusPage.vue";
import TelegramLoginCard from "@/components/TelegramLoginCard.vue";

const route = useRoute();
const router = useRouter();

// Faqat ilova ichidagi manzillarga qaytaramiz
const redirect = computed(() => {
   const value = route.query.redirect;
   return typeof value === "string" && value.startsWith("/") && !value.startsWith("//") ? value : null;
});

function onSuccess() {
   router.replace(redirect.value ?? { name: "home" });
}
</script>
