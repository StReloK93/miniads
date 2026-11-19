<template>
   <section class="admin-page">
      <header class="admin-page-heading">
         <div>
            <h1>Post</h1>
            <p class="admin-muted">Kanalga va bot obunachilariga xabar. Matnni Telegram'dagidek formatlash mumkin.</p>
         </div>
      </header>

      <p v-if="error" class="admin-alert admin-alert-error">{{ error }}</p>
      <p v-if="success" class="admin-alert admin-alert-success">{{ success }}</p>

      <!-- Jonli post: kanal tepasida qadalgan, har 15 daqiqada tahrirlanadi (bildirishnomasiz) -->
      <section v-if="live" class="admin-panel p-4 sm:p-5 mb-5 grid grid-cols-1 lg:grid-cols-5 gap-5 items-start">
         <div class="lg:col-span-3 flex flex-col gap-3">
            <label class="flex items-center justify-between gap-3 cursor-pointer">
               <span>
                  <span class="admin-label flex items-center gap-1.5">
                     <Radio class="size-4" /> Jonli post
                     <span
                        v-if="live.enabled && live.posted"
                        class="rounded-md bg-emerald-500/15 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-600"
                     >
                        Kanalda turibdi
                     </span>
                  </span>
                  <span class="admin-hint mt-1! block">
                     Kanal tepasiga bitta post qadab qo'yiladi: tezkor bo'limlarda nechta faol e'lon borligi. Har 15
                     daqiqada yangilanadi. Yangi post yozilmaydi, o'sha post tahrirlanadi, shuning uchun a'zolarga
                     bildirishnoma bormaydi. O'chirilsa, post kanaldan olib tashlanadi.
                  </span>
               </span>
               <span class="relative inline-flex shrink-0">
                  <input
                     :checked="live.enabled"
                     type="checkbox"
                     class="sr-only peer"
                     :disabled="liveSaving"
                     @change="toggleLive(($event.target as HTMLInputElement).checked)"
                  />
                  <span
                     class="w-11 h-6 bg-(--z-muted) rounded-full peer-checked:bg-(--z-primary) peer-disabled:opacity-60 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:size-5 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:after:translate-x-5"
                  ></span>
               </span>
            </label>
            <p v-if="!live.channels.length" class="admin-hint text-(--z-danger)!">
               Kanal sozlanmagan: .env faylida TELEGRAM_CHANNELS bo'sh.
            </p>
            <p class="admin-hint">
               Qaysi bo'limlar chiqishi: Kategoriyalar → "Tezkor bo'limlarda chiqadi".
            </p>
         </div>

         <!-- Hozirgi holat bilan oldindan ko'rish -->
         <div class="lg:col-span-2">
            <div class="rounded-(--z-rounded) bg-(--z-muted) p-3">
               <div class="max-w-80 mx-auto">
                  <!-- Matn serverda tayyorlanadi, nomlar ekranlangan; faqat <b>/<i> teglari -->
                  <p
                     class="rounded-2xl rounded-bl-md bg-(--z-card) border border-(--z-border) px-3 py-2.5 text-sm whitespace-pre-line"
                     v-html="live.preview"
                  ></p>
                  <div class="mt-1 grid grid-cols-2 gap-1">
                     <span
                        v-for="button in live.buttons"
                        :key="button"
                        class="rounded-xl bg-(--z-card) border border-(--z-border) py-1.5 px-2 text-center text-xs font-medium truncate"
                     >
                        {{ button }}
                     </span>
                  </div>
               </div>
            </div>
         </div>
      </section>

      <div class="grid grid-cols-1 lg:grid-cols-5 gap-5 items-start">
         <!-- Forma -->
         <form class="lg:col-span-3 admin-panel p-4 sm:p-5 flex flex-col gap-5" @submit.prevent="askConfirm">
            <!-- Qayerga: ikkalasini ham tanlash mumkin -->
            <div>
               <span class="admin-label mb-1.5 block">Qayerga</span>
               <div class="flex flex-wrap gap-2">
                  <button
                     v-for="option in targetOptions"
                     :key="option.value"
                     type="button"
                     class="admin-filter-chip"
                     :class="{ 'is-active': targets.includes(option.value) }"
                     :aria-pressed="targets.includes(option.value)"
                     @click="toggleTarget(option.value)"
                  >
                     <component :is="option.icon" class="size-3.5" />
                     {{ option.label }}
                     <span v-if="option.count !== null">{{ option.count }}</span>
                  </button>
               </div>
               <p v-if="targets.includes('bot')" class="admin-hint mt-1.5">
                  Botni ishga tushirgan va bloklanmagan foydalanuvchilarga shaxsan boradi. Ko'p yubormang — bezor
                  bo'lganlar botni to'xtatadi.
               </p>
            </div>

            <div>
               <div class="flex items-center justify-between mb-1.5">
                  <label for="post-text" class="admin-label">Matn</label>
                  <span class="text-xs tabular-nums" :class="textTooLong ? 'text-(--z-danger)' : 'text-(--z-muted-text)'">
                     {{ visibleLength }} / {{ textLimit }}
                  </span>
               </div>

               <!-- Formatlash: belgilangan matnni o'raydi (yoki kursor joyiga qo'yadi) -->
               <div class="post-toolbar" role="toolbar" aria-label="Formatlash">
                  <button
                     v-for="tool in tools"
                     :key="tool.label"
                     type="button"
                     class="post-tool"
                     :title="`${tool.label} — ${tool.hint}`"
                     :aria-label="tool.label"
                     @mousedown.prevent
                     @click="applyTool(tool)"
                  >
                     <component :is="tool.icon" class="size-4" />
                  </button>
               </div>
               <textarea
                  id="post-text"
                  ref="textArea"
                  v-model="text"
                  rows="8"
                  class="admin-input w-full py-2.5 rounded-t-none!"
                  placeholder="Masalan: **Navoiy viloyati e'lonlari** endi bitta joyda! E'loningizni bepul joylang."
               ></textarea>
               <p v-if="textTooLong" class="admin-hint text-(--z-danger)!">
                  {{ photo ? "Rasmli postda matn 1024 belgidan oshmasligi kerak (Telegram cheklovi)." : "Matn 4096 belgidan oshmasin." }}
               </p>
               <details class="admin-hint mt-1.5">
                  <summary class="cursor-pointer select-none">Formatlash belgilari</summary>
                  <p class="mt-1 leading-relaxed">
                     <code>**qalin**</code> · <code>__qiya__</code> · <code>~~o'chirilgan~~</code> · <code>||yashirin||</code> ·
                     <code>`kod`</code> · <code>[matn](https://havola)</code> · qator boshida <code>&gt; </code> — iqtibos
                     (5+ qator — yig'iladigan).
                  </p>
               </details>
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

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
               <!-- Hammaga ketishidan oldin: formatlash Telegram'da qanday chiqishini o'zida ko'radi -->
               <BaseButton type="button" severity="secondary" :disabled="!canTest" :loading="testing" @click="sendTest">
                  <template #icon><FlaskConical class="size-4" /></template>
                  O'zimga sinab ko'rish
               </BaseButton>
               <BaseButton type="submit" :disabled="!canSend" :loading="sending">
                  <template #icon><Send class="size-4" /></template>
                  Yuborish
               </BaseButton>
            </div>
         </form>

         <!-- Oldindan ko'rish: serverdagi o'sha HTML — Telegram'da qanday ko'rinsa, shunday -->
         <aside class="lg:col-span-2 lg:sticky lg:top-20">
            <p class="admin-label mb-2">Oldindan ko'rish</p>
            <div class="rounded-(--z-rounded) bg-(--z-muted) p-3 sm:p-4">
               <div class="max-w-80 mx-auto">
                  <div class="rounded-2xl rounded-bl-md bg-(--z-card) border border-(--z-border) overflow-hidden shadow-sm">
                     <img v-if="photoPreview" :src="photoPreview" alt="" class="w-full max-h-72 object-cover" />
                     <!-- HTML serverda tayyorlanadi: matn ekranlangan, faqat Telegram teglari -->
                     <div v-if="previewHtml" class="tg-preview px-3 py-2.5 text-sm whitespace-pre-wrap wrap-break-word" v-html="previewHtml"></div>
                     <p v-else class="px-3 py-2.5 text-sm text-(--z-muted-text)">Matn shu yerda ko'rinadi…</p>
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
            <p class="admin-hint mt-2">{{ audienceText }}</p>
         </aside>
      </div>

      <!-- Bot obunachilariga yuborilganlar: nechtasiga yetdi -->
      <section v-if="broadcasts.length" class="admin-panel p-4 sm:p-5 mt-5">
         <div class="admin-card-header">
            <h2 class="admin-card-title">Botga yuborilganlar</h2>
            <span class="admin-card-subtitle">oxirgi 10 ta</span>
         </div>
         <ul class="divide-y divide-(--z-border)">
            <li v-for="item in broadcasts" :key="item.id" class="py-2.5 flex flex-col gap-1">
               <p class="text-sm line-clamp-2 wrap-break-word">{{ item.text }}</p>
               <p class="text-xs text-(--z-muted-text) flex flex-wrap gap-x-2 gap-y-0.5 tabular-nums">
                  <span>{{ timeAgo(item.created_at) }}</span>
                  <span v-if="!item.finished_at" class="font-semibold text-(--z-foreground)">
                     yuborilmoqda: {{ item.sent + item.failed }} / {{ item.total }}
                  </span>
                  <template v-else>
                     <span class="text-emerald-600 font-semibold">{{ item.sent }} kishiga yetdi</span>
                     <span v-if="item.failed">{{ item.failed }} ta yetmadi (botni to'xtatgan)</span>
                  </template>
                  <span v-if="item.author">· {{ item.author }}</span>
               </p>
            </li>
         </ul>
      </section>

      <BaseModal
         :open="confirmOpen"
         title="Yuborilsinmi?"
         :description="confirmText"
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
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch, type Component } from "vue";
import {
   ArrowUpRight,
   Bold,
   Bot,
   Code,
   EyeOff,
   FlaskConical,
   ImagePlus,
   Italic,
   Link,
   Megaphone,
   Quote,
   Radio,
   Send,
   Strikethrough,
} from "lucide-vue-next";
import BaseButton from "@shared/ui/BaseButton.vue";
import BaseModal from "@shared/ui/BaseModal.vue";
import AdminRepo, { type BroadcastItem } from "@admin/entities/AdminRepo";
import { timeAgo } from "@shared/modules/formatters";

const STORAGE_KEY = "admin_channel_post_button";

const text = ref("");
const photo = ref<File | null>(null);
const photoPreview = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);
const withButton = ref(true);
const buttonText = ref("");
const buttonUrl = ref("");
const defaults = ref<{ channels: string[]; button_text: string; button_url: string; bot_audience: number } | null>(null);

/* ===== Qayerga ===== */
type Target = "channel" | "bot";
const targets = ref<Target[]>(["channel"]);
const targetOptions = computed(() => [
   { value: "channel" as const, label: "Kanal", icon: Megaphone, count: null },
   { value: "bot" as const, label: "Bot obunachilari", icon: Bot, count: defaults.value?.bot_audience ?? null },
]);

function toggleTarget(value: Target) {
   targets.value = targets.value.includes(value) ? targets.value.filter((item) => item !== value) : [...targets.value, value];
}

const audienceText = computed(() => {
   const parts: string[] = [];
   if (targets.value.includes("channel")) {
      const count = defaults.value?.channels.length ?? 1;
      parts.push(count > 1 ? `${count} ta kanalga` : "kanalga");
   }
   if (targets.value.includes("bot")) parts.push(`bot orqali ${defaults.value?.bot_audience ?? 0} kishiga`);
   return parts.length ? `${parts.join(" va ")} yuboriladi.`.replace(/^./, (c) => c.toUpperCase()) : "Qayerga yuborishni tanlang.";
});

const confirmText = computed(
   () => `${audienceText.value} Yuborilgandan keyin uni bu yerdan o'chirib bo'lmaydi — avval "O'zimga sinab ko'rish" bilan tekshirib oling.`,
);

/* ===== Formatlash ===== */
type Tool = { label: string; hint: string; icon: Component; before: string; after: string; placeholder: string; line?: boolean };
const tools: Tool[] = [
   { label: "Qalin", hint: "**matn**", icon: Bold, before: "**", after: "**", placeholder: "qalin" },
   { label: "Qiya", hint: "__matn__", icon: Italic, before: "__", after: "__", placeholder: "qiya" },
   { label: "O'chirilgan", hint: "~~matn~~", icon: Strikethrough, before: "~~", after: "~~", placeholder: "eski narx" },
   { label: "Yashirin (spoiler)", hint: "||matn||", icon: EyeOff, before: "||", after: "||", placeholder: "yashirin" },
   { label: "Kod", hint: "`matn`", icon: Code, before: "`", after: "`", placeholder: "kod" },
   { label: "Havola", hint: "[matn](https://...)", icon: Link, before: "[", after: "](https://)", placeholder: "havola matni" },
   { label: "Iqtibos", hint: "> qator", icon: Quote, before: "> ", after: "", placeholder: "iqtibos", line: true },
];
const textArea = ref<HTMLTextAreaElement | null>(null);

/** Belgilangan matnni o'raydi; iqtibos — har bir qator boshiga "> ". Havolada kursor manzilga turadi */
async function applyTool(tool: Tool) {
   const area = textArea.value;
   if (!area) return;
   const { selectionStart: start, selectionEnd: end } = area;
   const selected = text.value.slice(start, end) || tool.placeholder;

   let inserted: string;
   if (tool.line) {
      const atLineStart = start === 0 || text.value[start - 1] === "\n";
      inserted = (atLineStart ? "" : "\n") + selected.split("\n").map((line) => `> ${line}`).join("\n");
   } else {
      inserted = tool.before + selected + tool.after;
   }
   text.value = text.value.slice(0, start) + inserted + text.value.slice(end);

   await nextTick();
   area.focus();
   if (tool.label === "Havola") {
      const urlStart = start + tool.before.length + selected.length + 2;
      area.setSelectionRange(urlStart, urlStart + "https://".length);
   } else if (tool.line) {
      area.setSelectionRange(start + inserted.length, start + inserted.length);
   } else {
      area.setSelectionRange(start + tool.before.length, start + tool.before.length + selected.length);
   }
}

/* ===== Oldindan ko'rish (serverdagi o'sha konvertor) ===== */
const previewHtml = ref("");
const visibleLength = ref(0);
let previewTimer: ReturnType<typeof setTimeout> | undefined;
let previewRequest = 0;

watch(text, (value) => {
   clearTimeout(previewTimer);
   if (!value.trim()) {
      previewHtml.value = "";
      visibleLength.value = 0;
      return;
   }
   previewTimer = setTimeout(async () => {
      const request = ++previewRequest;
      try {
         const { data } = await AdminRepo.postPreview(value);
         // Eskirgan javob yangisini bosib ketmasin
         if (request !== previewRequest) return;
         previewHtml.value = data.html;
         visibleLength.value = data.length;
      } catch {
         // ko'rinish ixtiyoriy — yuborishda server baribir tekshiradi
      }
   }, 250);
});

const sending = ref(false);
const testing = ref(false);
const confirmOpen = ref(false);
const error = ref<string | null>(null);
const success = ref<string | null>(null);

// Telegram cheklovi: rasm izohi 1024, oddiy xabar 4096 belgi
const textLimit = computed(() => (photo.value ? 1024 : 4096));
const textTooLong = computed(() => visibleLength.value > textLimit.value);
const canTest = computed(
   () =>
      text.value.trim().length > 0 &&
      !textTooLong.value &&
      (!withButton.value || Boolean(buttonText.value.trim() && buttonUrl.value.trim())),
);
const canSend = computed(() => canTest.value && targets.value.length > 0);

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

function payload(test = false) {
   return {
      text: text.value,
      photo: photo.value,
      button_text: withButton.value ? buttonText.value.trim() : null,
      button_url: withButton.value ? buttonUrl.value.trim() : null,
      targets: targets.value,
      test,
   };
}

function showError(err: any) {
   const errors = err.response?.data?.errors;
   error.value = errors ? (Object.values(errors).flat()[0] as string) : (err.response?.data?.message ?? "Yuborib bo'lmadi.");
}

async function send() {
   confirmOpen.value = false;
   sending.value = true;
   try {
      const { data } = await AdminRepo.sendChannelPost(payload());
      success.value = data.message;
      text.value = "";
      removePhoto();
      if (targets.value.includes("bot")) loadBroadcasts();
   } catch (err: any) {
      showError(err);
   } finally {
      sending.value = false;
   }
}

/** Faqat adminning o'ziga — matn va rasm saqlanib qoladi */
async function sendTest() {
   if (!canTest.value) return;
   error.value = null;
   success.value = null;
   testing.value = true;
   try {
      const { data } = await AdminRepo.sendChannelPost(payload(true));
      success.value = data.message;
   } catch (err: any) {
      showError(err);
   } finally {
      testing.value = false;
   }
}

/* ===== Botga yuborilganlar: tugamagani bo'lsa har 5 soniyada yangilanadi ===== */
const broadcasts = ref<BroadcastItem[]>([]);
let broadcastTimer: ReturnType<typeof setTimeout> | undefined;

async function loadBroadcasts() {
   clearTimeout(broadcastTimer);
   try {
      const { data } = await AdminRepo.broadcasts();
      broadcasts.value = data;
   } catch {
      return;
   }
   if (broadcasts.value.some((item) => !item.finished_at)) {
      broadcastTimer = setTimeout(loadBroadcasts, 5000);
   }
}

onBeforeUnmount(() => {
   clearTimeout(broadcastTimer);
   clearTimeout(previewTimer);
});

/* ===== Jonli post ===== */
const live = ref<{ enabled: boolean; posted: boolean; channels: string[]; preview: string; buttons: string[] } | null>(
   null,
);
const liveSaving = ref(false);

async function loadLive() {
   const { data } = await AdminRepo.channelLivePost();
   live.value = data;
}

async function toggleLive(enabled: boolean) {
   if (!live.value) return;
   error.value = null;
   success.value = null;
   liveSaving.value = true;
   try {
      const { data } = await AdminRepo.updateChannelLivePost(enabled);
      success.value = data.message;
   } catch (err: any) {
      error.value = err.response?.data?.message ?? "Saqlab bo'lmadi.";
   } finally {
      liveSaving.value = false;
      // Kalit holati serverdagi haqiqiy holatga qaytadi (xato bo'lsa ham)
      await loadLive().catch(() => {});
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
   loadLive().catch((err) => console.error("Live post state failed.", err));
   loadBroadcasts();
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

<style scoped>
/* Formatlash paneli matn maydoni bilan bitta blok bo'lib turadi */
.post-toolbar {
   display: flex;
   flex-wrap: wrap;
   gap: 2px;
   border: 1px solid var(--z-border);
   border-bottom: 0;
   border-radius: 0.75rem 0.75rem 0 0;
   background: var(--z-muted);
   padding: 3px;
}

.post-tool {
   display: grid;
   width: 2.25rem;
   height: 2rem;
   place-items: center;
   border-radius: 0.5rem;
   color: var(--z-muted-text);
   cursor: pointer;
   transition: background-color 0.15s ease, color 0.15s ease;
}

.post-tool:hover,
.post-tool:focus-visible {
   background: var(--z-card);
   color: var(--z-foreground);
}

/* Telegram teglari ko'rinishi */
.tg-preview :deep(a) {
   color: #2481cc;
   text-decoration: none;
}

.tg-preview :deep(code),
.tg-preview :deep(pre) {
   font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
   font-size: 0.8125rem;
}

.tg-preview :deep(pre) {
   margin: 0.25rem 0;
   border-radius: 0.375rem;
   background: var(--z-muted);
   padding: 0.375rem 0.5rem;
   white-space: pre-wrap;
}

.tg-preview :deep(tg-spoiler) {
   border-radius: 0.25rem;
   background: var(--z-muted-text);
   color: transparent;
   cursor: pointer;
   transition: color 0.2s ease, background-color 0.2s ease;
}

.tg-preview :deep(tg-spoiler:hover) {
   background: transparent;
   color: inherit;
}

.tg-preview :deep(blockquote) {
   display: block;
   margin: 0.25rem 0;
   border-left: 3px solid #2481cc;
   border-radius: 0.25rem;
   background: color-mix(in srgb, #2481cc 8%, transparent);
   padding: 0.25rem 0.5rem;
}
</style>
