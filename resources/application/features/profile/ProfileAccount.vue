<template>
   <NavigationPageDecorator :header-class="['border-b', 'border-(--z-border)']">
      <template #header>
         <!-- Ixcham sarlavha: avatar + ism; kontentga ko'proq joy -->
         <div class="flex items-center gap-3 pb-3">
            <img v-if="user.photo_url && isImagesReady" :src="user.photo_url" class="size-13 rounded-full object-cover" alt="" />
            <span v-else-if="user.photo_url" class="size-13 rounded-full! skeleton inline-block" />
            <span
               v-else
               class="size-13 shrink-0 rounded-full bg-(--z-muted) text-(--z-primary) inline-flex justify-center items-center text-lg font-bold"
            >
               <template v-if="initials">{{ initials }}</template>
               <Camera v-else class="size-5" />
            </span>
            <div class="min-w-0">
               <h3 class="font-bold text-lg leading-tight truncate">{{ user.name }}</h3>
               <p class="text-sm text-(--z-muted-text) truncate">
                  {{ user.username ? `@${user.username}` : "Telegram username o'rnatilmagan" }}
               </p>
               <!-- Statistika — alohida kartalar emas, bitta ixcham qator; bosilsa E'lonlarim -->
               <RouterLink
                  v-if="summary?.stats.total"
                  :to="{ name: 'my-ads' }"
                  class="mt-0.5 inline-flex items-center gap-1 text-xs text-(--z-muted-text) tabular-nums active:opacity-70"
               >
                  {{ summary.stats.active }} faol e'lon · {{ compact(summary.stats.views) }} ko'rish
                  <ChevronRight class="size-3" />
               </RouterLink>
            </div>
         </div>
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
            <template #icon><LogOut class="size-5" /></template>
         </BaseModal>

         <div class="flex flex-col gap-5 pb-4">
            <section>
               <h4 class="section-title">Kuzatuvlarim</h4>
               <SavedSearchList />
            </section>

            <section>
               <h4 class="section-title">Bildirishnomalar</h4>
               <div class="card divide-y divide-(--z-border)">
                  <!-- Bot yoza olmasa — sozlamalar foydasiz; avval ruxsat -->
                  <div v-if="!authStore.user?.bot_can_message" class="px-4 py-3 flex items-center gap-3">
                     <BotMessageSquare class="size-5 shrink-0 text-amber-500" />
                     <p class="grow text-xs text-(--z-muted-text) leading-snug">
                        Bot sizga hali xabar yubora olmaydi. Ruxsat bering — kuzatuv va eslatmalar shunda keladi.
                     </p>
                     <BaseButton size="xs" :loading="isRequestingAccess" @click="allowMessages">Ruxsat</BaseButton>
                  </div>
                  <div class="px-4 py-3">
                     <BaseSwitch
                        :model-value="!!authStore.user?.notify_saved_searches"
                        :disabled="savingField === 'notify_saved_searches'"
                        @update:model-value="saveSetting('notify_saved_searches', $event)"
                     >
                        <span class="block text-sm font-medium">Kuzatuvlarga mos yangi e'lonlar</span>
                        <span class="block text-xs text-(--z-muted-text)">Ko'pi bilan 15 daqiqada bir marta</span>
                     </BaseSwitch>
                  </div>
                  <div class="px-4 py-3">
                     <BaseSwitch
                        :model-value="!!authStore.user?.notify_expiry"
                        :disabled="savingField === 'notify_expiry'"
                        @update:model-value="saveSetting('notify_expiry', $event)"
                     >
                        <span class="block text-sm font-medium">E'lon muddati tugayotganda</span>
                        <span class="block text-xs text-(--z-muted-text)">Tugashidan 2 kun oldin eslatadi</span>
                     </BaseSwitch>
                  </div>
               </div>
            </section>

            <section>
               <h4 class="section-title">Asosiy telefon raqam</h4>
               <form class="card px-4 py-3 flex items-center gap-2" @submit.prevent="savePhone">
                  <Phone class="size-4.5 shrink-0 text-(--z-muted-text)" />
                  <span class="text-sm text-(--z-muted-text)">+998</span>
                  <input
                     v-model="phoneDraft"
                     type="tel"
                     inputmode="tel"
                     placeholder="93-123-45-67"
                     class="grow min-w-0 bg-transparent outline-none text-sm font-medium"
                     @input="phoneDraft = formatPhone(phoneDraft)"
                  />
                  <BaseButton v-if="phoneChanged" type="submit" size="xs" :loading="savingField === 'phone'">Saqlash</BaseButton>
                  <Check v-else-if="phoneSaved" class="size-4 text-emerald-500" />
               </form>
               <p class="mt-1.5 px-1 text-xs text-(--z-muted-text)">Yangi e'lon formasida avtomatik to'ldiriladi.</p>
            </section>

            <section>
               <h4 class="section-title">Sozlamalar</h4>
               <ul class="card divide-y divide-(--z-border)">
                  <li>
                     <RouterLink :to="{ name: 'my-ads' }" class="row">
                        <Megaphone class="size-4.5 text-(--z-muted-text)" />
                        <span class="grow text-sm font-medium">E'lonlarim</span>
                        <ChevronRight class="size-4 text-(--z-muted-text)" />
                     </RouterLink>
                  </li>
                  <li class="px-4 py-3">
                     <div class="flex items-center gap-3 mb-2.5">
                        <SunMoon class="size-4.5 text-(--z-muted-text)" />
                        <span class="text-sm font-medium">Mavzu</span>
                     </div>
                     <ThemeSwitcher variant="segmented" class="w-full" />
                  </li>
                  <li v-if="authStore.user?.role === 'admin'">
                     <a href="/admin" class="row">
                        <LayoutDashboard class="size-4.5 text-(--z-muted-text)" />
                        <span class="grow text-sm font-medium">Admin panel</span>
                        <ChevronRight class="size-4 text-(--z-muted-text)" />
                     </a>
                  </li>
               </ul>
            </section>

            <section>
               <h4 class="section-title">Yordam</h4>
               <ul class="card divide-y divide-(--z-border)">
                  <li>
                     <button type="button" class="row w-full" @click="openUrl(privacyUrl)">
                        <ShieldCheck class="size-4.5 text-(--z-muted-text)" />
                        <span class="grow text-left text-sm font-medium">Qoidalar va maxfiylik</span>
                        <ChevronRight class="size-4 text-(--z-muted-text)" />
                     </button>
                  </li>
                  <li v-if="summary?.support_username">
                     <button type="button" class="row w-full" @click="openSupport(summary.support_username)">
                        <LifeBuoy class="size-4.5 text-(--z-muted-text)" />
                        <span class="grow text-left text-sm font-medium">Admin bilan bog'lanish</span>
                        <ChevronRight class="size-4 text-(--z-muted-text)" />
                     </button>
                  </li>
                  <!-- Brauzerda (Telegram login) chiqish; Mini App ichida kerak emas — Telegram hisobiga bog'liq -->
                  <li v-if="!inTelegram">
                     <button
                        type="button"
                        class="row w-full text-(--z-danger) disabled:opacity-60"
                        :disabled="isLoggingOut"
                        @click="isLogoutOpen = true"
                     >
                        <LogOut class="size-4.5" />
                        <span class="grow text-left text-sm font-medium">Hisobdan chiqish</span>
                     </button>
                  </li>
               </ul>
            </section>
         </div>
      </template>
   </NavigationPageDecorator>
</template>

<script setup lang="ts">
import { computed, inject, onMounted, ref, watch } from "vue";
import { isTMA, postEvent } from "@tma.js/bridge";
import {
   BotMessageSquare,
   Camera,
   Check,
   ChevronRight,
   LayoutDashboard,
   LifeBuoy,
   LogOut,
   Megaphone,
   Phone,
   ShieldCheck,
   SunMoon,
} from "lucide-vue-next";
import NavigationPageDecorator from "@/layouts/NavigationPageDecorator.vue";
import BaseModal from "@shared/ui/BaseModal.vue";
import BaseButton from "@shared/ui/BaseButton.vue";
import BaseSwitch from "@shared/ui/BaseSwitch.vue";
import ThemeSwitcher from "@shared/ui/ThemeSwitcher.vue";
import SavedSearchList from "@/features/notifications/SavedSearchList.vue";
import { useSavedSearches } from "@/features/notifications/useSavedSearches";
import { useBotAccess } from "@/features/notifications/useBotAccess";
import ProfileRepo, { type IProfileSummary } from "@shared/entities/Profile/ProfileRepo";
import { preloadImages } from "@shared/modules/helpers";
import { useAuth } from "@shared/store/useAuth";

const userData: any = inject("userData");
const authStore = useAuth();
const inTelegram = isTMA();
const savedSearches = useSavedSearches();
const { ensureWriteAccess } = useBotAccess();

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

/* ---------- Statistika va kuzatuvlar (bitta so'rov) ---------- */

const summary = ref<IProfileSummary | null>(null);
const compact = (value: number) => (value >= 10_000 ? `${Math.round(value / 1000)}K` : value.toLocaleString("ru-RU"));

/* ---------- Bildirishnomalar ---------- */

const isRequestingAccess = ref(false);
const savingField = ref<string | null>(null);

async function allowMessages() {
   isRequestingAccess.value = true;
   try {
      const allowed = await ensureWriteAccess();
      // Brauzerda so'rab bo'lmaydi: botni ochib /start bosish kerak
      if (!allowed && !inTelegram) {
         window.open(`${import.meta.env.VITE_TELEGRAM_APP_URL || "https://t.me/NavoiyElonBot"}?start=notify`, "_blank");
      }
   } finally {
      isRequestingAccess.value = false;
   }
}

async function saveSetting(field: "notify_saved_searches" | "notify_expiry" | "phone", value: boolean | string | null) {
   savingField.value = field;
   try {
      const { data } = await ProfileRepo.update({ [field]: value });
      authStore.user = data;
   } finally {
      savingField.value = null;
   }
}

/* ---------- Asosiy telefon ---------- */

// Forma bilan bir xil ko'rinish: 93-123-45-67
function formatPhone(value: string) {
   const digits = value.replace(/\D/g, "").replace(/^998/, "").slice(0, 9);
   const parts = [digits.slice(0, 2), digits.slice(2, 5), digits.slice(5, 7), digits.slice(7, 9)].filter(Boolean);
   return parts.join("-");
}

const phoneDraft = ref(authStore.user?.phone ?? "");
const phoneSaved = ref(false);
const phoneChanged = computed(() => phoneDraft.value !== (authStore.user?.phone ?? ""));
watch(phoneDraft, () => (phoneSaved.value = false));

async function savePhone() {
   const value = phoneDraft.value.replace(/\D/g, "").length === 9 ? phoneDraft.value : null;
   if (phoneDraft.value && !value) return;
   await saveSetting("phone", value);
   phoneSaved.value = true;
}

/* ---------- Yordam ---------- */

const privacyUrl = `${window.location.origin}/privacy`;

function openUrl(url: string) {
   if (inTelegram) postEvent("web_app_open_link", { url });
   else window.open(url, "_blank", "noopener");
}

function openSupport(username: string) {
   if (inTelegram) postEvent("web_app_open_tg_link", { path_full: `/${username}` });
   else window.open(`https://t.me/${username}`, "_blank", "noopener");
}

/* ---------- Chiqish ---------- */

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

const isImagesReady = ref(false);

onMounted(async () => {
   ProfileRepo.show()
      .then(({ data }) => {
         summary.value = data;
         // Kuzatuvlar ro'yxati ham shu javobda — alohida so'rov shart emas
         savedSearches.items.value = data.saved_searches;
      })
      .catch((error) => console.error("Profil ma'lumotlari yuklanmadi.", error));

   if (user.value.photo_url) {
      await preloadImages([user.value.photo_url]);
   }
   isImagesReady.value = true;
});
</script>

<style scoped>
.section-title {
   margin: 0 0 0.5rem 0.25rem;
   color: var(--z-muted-text);
   font-size: 0.6875rem;
   font-weight: 700;
   letter-spacing: 0.08em;
   text-transform: uppercase;
}

.card {
   overflow: hidden;
   border: 1px solid var(--z-glass-border);
   border-radius: var(--z-rounded);
   background: var(--z-card);
   box-shadow: var(--z-glass-edge, none);
}

.row {
   display: flex;
   height: 3.25rem;
   align-items: center;
   gap: 0.75rem;
   padding: 0 1rem;
   cursor: pointer;

   &:active {
      background: var(--z-muted);
   }
}
</style>
