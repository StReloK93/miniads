<template>
   <div ref="telegramWrapper"></div>
</template>

<script setup>
import { ref, onMounted } from "vue";

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
      console.log("Tizimga kirdi:", user);
      // Bu yerda foydalanuvchi ma'lumotlarini Vuex/Pinia-ga saqlashingiz mumkin
   };
});
</script>
