<template>
   <main class="h-dvh flex items-center justify-center flex-col">
      <h3 class="text-tertiary font-semibold text-xl py-4">Dasturga kiring</h3>
      <div ref="telegramWrapper"></div>
   </main>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { api } from "@/modules/useFetch";
import { useRouter } from "vue-router";
import { useAuth } from "@/store/useAuth";
const router = useRouter();
const { getUser } = useAuth();
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
      onTelegramAuth(user);
   };
});

async function onTelegramAuth(user) {
   await api
      .post("telegram/widget-sign-in", user)
      .then(async (result) => {
         localStorage.setItem("token", `${result.data.type} ${result.data.token}`);
         await getUser();
         router.push({ name: "home" });
      })
      .catch((err) => {
         console.error("Widget login xatosi:", err);
      });
}
</script>
