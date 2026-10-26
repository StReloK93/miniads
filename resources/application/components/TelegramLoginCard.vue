<template>
   <section class="flex flex-col items-center text-center">
      <div class="size-20 rounded-full bg-[#2AABEE]/10 flex items-center justify-center mb-5">
         <div class="size-14 rounded-full bg-[#2AABEE] text-white flex items-center justify-center shadow-lg shadow-[#2AABEE]/30">
            <Send class="size-6 -translate-x-0.5 translate-y-px" />
         </div>
      </div>

      <h3 class="text-xl font-bold">{{ props.title }}</h3>
      <p class="text-sm text-(--z-muted-text) mt-2 max-w-72">{{ props.description }}</p>

      <ul class="w-full mt-6 bg-(--z-card) border border-(--z-border) rounded-(--z-rounded) divide-y divide-(--z-border) text-left">
         <li v-for="item in benefits" :key="item.title" class="flex items-center gap-3 px-4 py-3">
            <span class="size-9 shrink-0 rounded-full bg-(--z-muted) text-(--z-primary) flex items-center justify-center">
               <component :is="item.icon" class="size-4" />
            </span>
            <div>
               <div class="text-sm font-semibold">{{ item.title }}</div>
               <div class="text-xs text-(--z-muted-text)">{{ item.text }}</div>
            </div>
         </li>
      </ul>

      <button
         type="button"
         :disabled="loading"
         class="mt-6 w-full h-12 rounded-full bg-[#2AABEE] hover:bg-[#229ED9] text-white font-semibold flex items-center justify-center gap-2 cursor-pointer transition active:scale-95 disabled:opacity-60 disabled:pointer-events-none"
         @click="login"
      >
         <LoaderCircle v-if="loading" class="size-5 animate-spin" />
         <Send v-else class="size-5" />
         {{ inTelegram ? "Qayta urinish" : "Telegram orqali kirish" }}
      </button>

      <p v-if="error" class="text-xs text-(--z-danger) mt-3">{{ error }}</p>
      <p class="text-xs text-(--z-muted-text) mt-4 max-w-72">
         Parol kerak emas. Telegram faqat ismingiz va foydalanuvchi nomingizni ulashadi.
      </p>
   </section>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { Heart, LoaderCircle, Megaphone, Send, UserRound } from "lucide-vue-next";
import { isTMA, retrieveRawInitData } from "@tma.js/bridge";
import { useAuth } from "@shared/store/useAuth";

const props = withDefaults(
   defineProps<{
      title?: string;
      description?: string;
   }>(),
   {
      title: "Telegram orqali kiring",
      description: "E'lon joylash, sevimlilarni saqlash va e'lonlaringizni boshqarish uchun bir marta kiring.",
   },
);

const emit = defineEmits<{ (e: "success"): void }>();

const benefits = [
   { icon: Megaphone, title: "E'lon joylash", text: "Bepul va bir necha daqiqada" },
   { icon: Heart, title: "Sevimlilar", text: "Yoqqan e'lonlarni saqlab qo'ying" },
   { icon: UserRound, title: "Shaxsiy kabinet", text: "E'lonlaringizni boshqaring" },
];

const authStore = useAuth();
const inTelegram = isTMA();
const loading = ref(false);
const error = ref<string | null>(null);

const WIDGET_SRC = "https://telegram.org/js/telegram-widget.js?22";
let widgetReady: Promise<void> | null = null;

function loadWidget(): Promise<void> {
   if ((window as any).Telegram?.Login) return Promise.resolve();
   if (widgetReady) return widgetReady;

   widgetReady = new Promise((resolve, reject) => {
      const script = document.createElement("script");
      script.src = WIDGET_SRC;
      script.async = true;
      script.onload = () => resolve();
      script.onerror = () => {
         widgetReady = null;
         reject(new Error("Telegram skriptini yuklab bo'lmadi"));
      };
      document.head.appendChild(script);
   });

   return widgetReady;
}

async function login() {
   error.value = null;
   loading.value = true;

   // Mini App ichida login avtomatik; bu yerga faqat u muvaffaqiyatsiz bo'lsa kelinadi
   if (inTelegram) {
      try {
         await authStore.signInTelegram(retrieveRawInitData());
         emit("success");
      } catch {
         error.value = "Kirib bo'lmadi. Ilovani yopib, qayta oching.";
      } finally {
         loading.value = false;
      }
      return;
   }

   try {
      await loadWidget();
   } catch {
      error.value = "Telegram bilan bog'lanib bo'lmadi. Internetni tekshirib, qayta urining.";
      loading.value = false;
      return;
   }

   (window as any).Telegram.Login.auth(
      { bot_id: ENV.TELEGRAM_BOT_ID, request_access: "write", lang: "uz" },
      async (telegramUser: Record<string, unknown> | false) => {
         if (!telegramUser) {
            // Foydalanuvchi oynani yopdi yoki rad etdi
            loading.value = false;
            return;
         }

         try {
            await authStore.widgetTelegramAuth(telegramUser);
            emit("success");
         } catch {
            error.value = "Kirishda xatolik yuz berdi. Qayta urinib ko'ring.";
         } finally {
            loading.value = false;
         }
      },
   );
}

onMounted(() => {
   // Oldindan yuklab qo'yamiz: tugma bosilganda popup darhol ochilishi uchun (aks holda brauzer bloklaydi)
   if (!inTelegram) loadWidget().catch(() => {});
});
</script>
