<template>
   <NavigationPageDecorator>
      <template #header>
         <h3 class="font-bold text-xl">Profil</h3>
         <aside class="flex flex-col items-center py-4">
            <div class="mb-2 flex">
               <img
                  v-if="user.photo_url && isImagesReady"
                  :src="user.photo_url"
                  class="rounded-full w-18 h-18 object-fill"
               />
               <span v-else-if="user.photo_url && !isImagesReady" class="rounded-full! w-18 h-18 skeleton inline-block">
               </span>
               <span
                  v-else
                  class="rounded-full w-18 h-18 bg-(--z-muted) text-(--z-primary) inline-flex justify-center items-center text-xl font-bold"
               >
                  <template v-if="initials">{{ initials }}</template>
                  <Camera v-else class="size-5" stroke-width="2" stroke="currentColor" />
               </span>
            </div>
            <h3 class="font-semibold">{{ user?.name }}</h3>
            <div v-if="user?.username" class="text-(--z-muted-text)">@{{ user?.username }}</div>
            <main v-else class="text-center">
               <div class="text-(--z-muted-text) flex items-center justify-center gap-2">
                  <Megaphone class="inline size-4 text-(--z-primary)" /> Foydalanuvchi nomi mavjud emas
               </div>
               <p class="text-(--z-muted-text) text-center text-xs mt-1">
                  E'lon qo'yganingizda sizga Telegram orqali to'g'ridan-to'g'ri xabar yozishlarini istasangiz
                  <span class="font-semibold text-(--z-primary)">Telegram</span>
                  sozlamalariga o'ting va "Foydalanuvchi nomi" maydonini to'ldiring.
               </p>
            </main>
         </aside>
      </template>
      <template #content>
         <BaseModal
            :open="isLogoutOpen"
            title="Hisobdan chiqish"
            description="Rostdan ham chiqmoqchimisiz? Qayta kirish uchun Telegram orqali login qilishingiz kerak bo'ladi."
            confirm-text="Chiqish"
            cancel-text="Bekor qilish"
            @close="isLogoutOpen = false"
            @confirm="logout"
         >
            <template #icon>
               <LogOut class="size-5" />
            </template>
         </BaseModal>

         <!-- Sozlamalar ro'yxati: har qator — bitta amal -->
         <ul class="bg-(--z-card) border border-(--z-border) rounded-(--z-rounded) divide-y divide-(--z-border) overflow-hidden">
            <li>
               <RouterLink :to="{ name: 'my-ads' }" class="flex items-center gap-3 px-4 h-13 active:bg-(--z-muted)">
                  <Megaphone class="size-4.5 text-(--z-muted-text)" />
                  <span class="grow text-sm font-medium">E'lonlarim</span>
                  <ChevronRight class="size-4 text-(--z-muted-text)" />
               </RouterLink>
            </li>
            <!-- Uch tanlov bir qatorga sig'maydi: sarlavha tepada, tugmalar to'liq kenglikda -->
            <li class="px-4 py-3">
               <div class="flex items-center gap-3 mb-2.5">
                  <SunMoon class="size-4.5 text-(--z-muted-text)" />
                  <span class="text-sm font-medium">Mavzu</span>
               </div>
               <ThemeSwitcher variant="segmented" class="w-full" />
            </li>
            <!-- Faqat adminlarga: admin panel alohida ilova, shuning uchun oddiy havola -->
            <li v-if="authStore.user?.role === 'admin'">
               <a href="/admin" class="flex items-center gap-3 px-4 h-13 active:bg-(--z-muted)">
                  <LayoutDashboard class="size-4.5 text-(--z-muted-text)" />
                  <span class="grow text-sm font-medium">Admin panel</span>
                  <ChevronRight class="size-4 text-(--z-muted-text)" />
               </a>
            </li>
            <!-- Brauzerda (Telegram login) chiqish; Mini App ichida kerak emas — Telegram hisobiga bog'liq -->
            <li v-if="!inTelegram">
               <button
                  type="button"
                  class="w-full flex items-center gap-3 px-4 h-13 text-(--z-danger) cursor-pointer active:bg-(--z-muted) disabled:opacity-60"
                  :disabled="isLoggingOut"
                  @click="isLogoutOpen = true"
               >
                  <LogOut class="size-4.5" />
                  <span class="grow text-left text-sm font-medium">Hisobdan chiqish</span>
               </button>
            </li>
         </ul>
      </template>
   </NavigationPageDecorator>
</template>

<script setup lang="ts">
import { preloadImages } from "@shared/modules/helpers";
import { computed, inject, onMounted, ref } from "vue";
import NavigationPageDecorator from "@/layouts/NavigationPageDecorator.vue";
import BaseModal from "@shared/ui/BaseModal.vue";
import ThemeSwitcher from "@shared/ui/ThemeSwitcher.vue";
import { isTMA } from "@tma.js/bridge";
import { Camera, ChevronRight, LayoutDashboard, LogOut, Megaphone, SunMoon } from "lucide-vue-next";
import { useAuth } from "@shared/store/useAuth";

const isImagesReady = ref(false);
const userData: any = inject("userData");

const authStore = useAuth();
const inTelegram = isTMA();

// Ism va username bazadagi foydalanuvchidan; rasm faqat Mini App'da (Telegram beradi)
const user = computed(() => ({
   name: authStore.user?.name,
   username: authStore.user?.username,
   photo_url: inTelegram ? userData?.tgWebAppData?.user?.photo_url : null,
}));

const initials = computed(() =>
   (user.value.name || "")
      .split(" ")
      .filter(Boolean)
      .slice(0, 2)
      .map((part: string) => part[0].toUpperCase())
      .join(""),
);

const isLogoutOpen = ref(false);
const isLoggingOut = ref(false);

async function logout() {
   isLoggingOut.value = true;
   try {
      await authStore.logout();
   } finally {
      isLoggingOut.value = false;
      isLogoutOpen.value = false;
   }
}

onMounted(async () => {
   if (user.value.photo_url) {
      await preloadImages([user.value.photo_url]);
   }

   setTimeout(() => {
      isImagesReady.value = true;
   }, 1000);
});
</script>
