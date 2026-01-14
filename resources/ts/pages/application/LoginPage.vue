<template>
   <div ref="telegramWrapper"></div>
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
   script.src = "https://telegram.org/js/telegram-widget.js?24";

   // Widget sozlamalari
   script.setAttribute("data-telegram-login", "NavoElonBot"); // Bot username-ni yozing
   script.setAttribute("data-size", "large");
   script.setAttribute("data-radius", "10");
   script.setAttribute("data-onauth", "onTelegramAuth(user)"); // Callback funksiya nomi
   script.setAttribute("data-request-access", "write");

   // DOM-ga qo'shamiz
   telegramWrapper.value.appendChild(script);

   // Global funksiya yaratamiz (Telegram widget chaqirishi uchun)
   window.onTelegramAuth = (user) => {
      onTelegramAuth(user);
      // Bu yerda foydalanuvchi ma'lumotlarini Vuex/Pinia-ga saqlashingiz mumkin
   };
});

// Telegram Login Widget callback funksiyasi ichida
async function onTelegramAuth(user) {
   // user obyekti ichida id, first_name, last_name, username, photo_url, auth_date, hash bor

   await api
      .post("telegram/widget-sign-in", user) // Header emas, body sifatida yuboramiz
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
