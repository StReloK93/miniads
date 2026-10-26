<template>
   <section class="admin-page">
      <header class="admin-page-heading">
         <div>
            <h1>Kanalga post</h1>
            <p class="admin-muted">Kanalga xabar yuboring. Ostiga ilovaga olib kiradigan tugma qo'shish mumkin.</p>
         </div>
      </header>

      <p v-if="error" class="admin-alert admin-alert-error">{{ error }}</p>
      <p v-if="success" class="admin-alert admin-alert-success">{{ success }}</p>

      <div class="grid grid-cols-1 lg:grid-cols-5 gap-5 items-start">
         <!-- Forma -->
         <form class="lg:col-span-3 admin-panel p-4 sm:p-5 flex flex-col gap-5" @submit.prevent="askConfirm">
            <div>
               <div class="flex items-center justify-between mb-1.5">
                  <label for="post-text" class="admin-label">Matn</label>
                  <span class="text-xs tabular-nums" :class="textTooLong ? 'text-(--z-danger)' : 'text-(--z-muted-text)'">
                     {{ text.length }} / {{ textLimit }}
                  </span>
               </div>
               <textarea
                  id="post-text"
                  v-model="text"
                  rows="7"
                  class="admin-input w-full py-2.5"
                  placeholder="Masalan: Navoiy viloyati e'lonlari endi bitta joyda! E'loningizni bepul joylang."
               ></textarea>
               <p v-if="photo && textTooLong" class="admin-hint text-(--z-danger)!">
                  Rasmli postda matn 1024 belgidan oshmasligi kerak (Telegram cheklovi).
               </p>
            </div>

            <!-- Rasm -->
            <div>
               <span class="admin-label mb-1.5 block">Rasm (ixtiyoriy)</span>
               <div v-if="photoPreview" class="flex items-center gap-3">
                  <img :src="photoPreview" alt="" class="size-16 rounded-lg object-cover border border-(--z-border)" />
                  <div class="min-w-0">
                     <p class="text-sm font-medium truncate">{{ photo?.name }}</p>
                     <button type="button" class="admin-text-link text-xs mt-0.5 cursor-pointer" @click="removePhoto">
                        Olib tashlash
                     </button>
                  </div>
               </div>
               <BaseButton v-else type="button" size="sm" severity="secondary" @click="fileInput?.click()">
                  <template #icon><ImagePlus class="size-4" /></template>
                  Rasm tanlash
               </BaseButton>
               <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="onPhoto" />
            </div>

            <!-- Tugma -->
            <div class="flex flex-col gap-3 pt-4 border-t border-(--z-border)">
               <label class="flex items-center justify-between gap-3 cursor-pointer">
                  <span>
                     <span class="admin-label block">Ostida tugma</span>
                     <span class="admin-hint mt-0.5! block">Masalan "E'lon joylash" — bosilganda ilova ochiladi</span>
                  </span>
                  <span class="relative inline-flex shrink-0">
                     <input v-model="withButton" type="checkbox" class="sr-only peer" />
                     <span
                        class="w-11 h-6 bg-(--z-muted) rounded-full peer-checked:bg-(--z-primary) transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:size-5 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:after:translate-x-5"
                     ></span>
                  </span>
               </label>

               <template v-if="withButton">
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                     <div>
                        <label for="button-text" class="admin-label mb-1.5 block">Tugma yozuvi</label>
                        <input id="button-text" v-model="buttonText" maxlength="64" class="admin-input w-full" placeholder="E'lon joylash" />
                     </div>
                     <div>
                        <label for="button-url" class="admin-label mb-1.5 block">Havola</label>
                        <input
                           id="button-url"
                           v-model="buttonUrl"
                           type="url"
                           inputmode="url"
                           class="admin-input w-full"
                           placeholder="https://t.me/NavoiyElonBot?startapp=create"
                        />
                     </div>
                  </div>
                  <button
                     v-if="defaults && (buttonText !== defaults.button_text || buttonUrl !== defaults.button_url)"
                     type="button"
                     class="admin-text-link text-xs self-start cursor-pointer"
                     @click="resetButton"
                  >
                     Standart tugmaga qaytarish ("{{ defaults.button_text }}")
                  </button>
               </template>
            </div>

            <BaseButton type="submit" class="w-full sm:w-auto sm:self-end" :disabled="!canSend" :loading="sending">
               <template #icon><Send class="size-4" /></template>
               Kanalga yuborish
            </BaseButton>
         </form>

         <!-- Oldindan ko'rish: Telegram'da qanday ko'rinadi -->
         <aside class="lg:col-span-2 lg:sticky lg:top-20">
            <p class="admin-label mb-2">Oldindan ko'rish</p>
            <div class="rounded-(--z-rounded) bg-(--z-muted) p-3 sm:p-4">
               <div class="max-w-80 mx-auto">
                  <div class="rounded-2xl rounded-bl-md bg-(--z-card) border border-(--z-border) overflow-hidden shadow-sm">
                     <img v-if="photoPreview" :src="photoPreview" alt="" class="w-full max-h-72 object-cover" />
                     <p class="px-3 py-2.5 text-sm whitespace-pre-line wrap-break-word">
                        <template v-if="text.trim()">{{ text }}</template>
                        <span v-else class="text-(--z-muted-text)">Matn shu yerda ko'rinadi…</span>
                     </p>
                  </div>
                  <div
                     v-if="withButton && buttonText.trim()"
                     class="mt-1 rounded-xl bg-(--z-card) border border-(--z-border) py-2 text-center text-sm font-medium inline-flex w-full items-center justify-center gap-1.5"
                  >
                     {{ buttonText }}
                     <ArrowUpRight class="size-3.5 text-(--z-muted-text)" />
                  </div>
               </div>
            </div>
            <p v-if="defaults" class="admin-hint mt-2">
               {{ defaults.channels.length > 1 ? `${defaults.channels.length} ta kanalga` : "Kanalga" }} yuboriladi.
            </p>
         </aside>
      </div>

      <BaseModal
         :open="confirmOpen"
         title="Kanalga yuborilsinmi?"
         description="Post darhol kanalda paydo bo'ladi. Yuborilgandan keyin uni bu yerdan o'chirib bo'lmaydi."
         confirm-text="Yuborish"
         cancel-text="Bekor qilish"
         @close="confirmOpen = false"
         @confirm="send"
      >
         <template #icon><Send class="size-5" /></template>
      </BaseModal>
   </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { ArrowUpRight, ImagePlus, Send } from "lucide-vue-next";
import BaseButton from "@shared/ui/BaseButton.vue";
import BaseModal from "@shared/ui/BaseModal.vue";
import AdminRepo from "@admin/entities/AdminRepo";

const STORAGE_KEY = "admin_channel_post_button";

const text = ref("");
const photo = ref<File | null>(null);
const photoPreview = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);
const withButton = ref(true);
const buttonText = ref("");
const buttonUrl = ref("");
const defaults = ref<{ channels: string[]; button_text: string; button_url: string } | null>(null);

const sending = ref(false);
const confirmOpen = ref(false);
const error = ref<string | null>(null);
const success = ref<string | null>(null);

// Telegram cheklovi: rasm izohi 1024, oddiy xabar 4096 belgi
const textLimit = computed(() => (photo.value ? 1024 : 4096));
const textTooLong = computed(() => text.value.length > textLimit.value);
const canSend = computed(
   () =>
      text.value.trim().length > 0 &&
      !textTooLong.value &&
      (!withButton.value || (buttonText.value.trim() && buttonUrl.value.trim())),
);

function onPhoto(event: Event) {
   const file = (event.target as HTMLInputElement).files?.[0];
   if (!file) return;
   removePhoto();
   photo.value = file;
   photoPreview.value = URL.createObjectURL(file);
}

function removePhoto() {
   if (photoPreview.value) URL.revokeObjectURL(photoPreview.value);
   photo.value = null;
   photoPreview.value = null;
   if (fileInput.value) fileInput.value.value = "";
}

function resetButton() {
   if (!defaults.value) return;
   buttonText.value = defaults.value.button_text;
   buttonUrl.value = defaults.value.button_url;
}

function askConfirm() {
   if (!canSend.value) return;
   error.value = null;
   success.value = null;
   confirmOpen.value = true;
}

async function send() {
   confirmOpen.value = false;
   sending.value = true;
   try {
      const { data } = await AdminRepo.sendChannelPost({
         text: text.value,
         photo: photo.value,
         button_text: withButton.value ? buttonText.value.trim() : null,
         button_url: withButton.value ? buttonUrl.value.trim() : null,
      });
      success.value = data.message;
      text.value = "";
      removePhoto();
   } catch (err: any) {
      const errors = err.response?.data?.errors;
      error.value = errors ? (Object.values(errors).flat()[0] as string) : (err.response?.data?.message ?? "Yuborib bo'lmadi.");
   } finally {
      sending.value = false;
   }
}

// Oxirgi ishlatilgan tugma eslab qolinadi
watch([buttonText, buttonUrl], ([label, url]) => {
   try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify({ label, url }));
   } catch {
      // ignore
   }
});

onMounted(async () => {
   const { data } = await AdminRepo.channelPostDefaults();
   defaults.value = data;

   let saved: { label?: string; url?: string } = {};
   try {
      saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || "{}");
   } catch {
      // ignore
   }
   buttonText.value = saved.label || data.button_text;
   buttonUrl.value = saved.url || data.button_url;
});
</script>
