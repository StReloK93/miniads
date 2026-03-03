<template>
   <main class="h-dvh flex items-center justify-center flex-col">
      <h3 class="text-tertiary font-semibold text-xl py-4">Dasturga kiring</h3>
      <div ref="telegramWrapper"></div>
   </main>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useAuth } from "@shared/store/useAuth";
const { widgetTelegramAuth } = useAuth();
const telegramWrapper = ref(null);

onMounted(() => {
   // Telegram taqdim etgan scriptni yaratamiz
   const script = document.createElement("script");
   script.async = true;
   script.src = `https://telegram.org/js/telegram-widget.js?66v=${new Date().getTime()}`;

   script.setAttribute("data-telegram-login", "NavoElonBot");
   script.setAttribute("data-size", "large");
   script.setAttribute("data-radius", "2");
   script.setAttribute("data-onauth", "onTelegramAuth(user)");
   script.setAttribute("data-request-access", "write");

   telegramWrapper.value.appendChild(script);

   window.onTelegramAuth = (user) => {
      widgetTelegramAuth(user);
   };
});
</script>
