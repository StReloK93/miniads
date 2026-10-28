<template>
   <section class="admin-page">
      <header class="admin-page-heading">
         <div>
            <h1>Avto-e'lonlar</h1>
            <p class="admin-muted">Telegram kanallardagi e'lonlarni havola orqali yoki har soatda avtomatik qo'shish.</p>
         </div>
      </header>

      <p v-if="message" class="admin-alert admin-alert-success">{{ message }}</p>
      <p v-if="error" class="admin-alert admin-alert-error">{{ error }}</p>

      <AdminTabs v-model="tab" :tabs="tabs" />

      <!-- QO'SHISH: admin tanlagan postlar — filtrsiz, albatta joylanadi -->
      <template v-if="tab === 'link'">
         <section class="admin-chart-card flex flex-col gap-3">
            <div>
               <h2 class="admin-card-title">Post havolasidan e'lon</h2>
               <p class="admin-card-subtitle">
                  O'zingiz tanlagan postlar filtrsiz joylanadi: AI kategoriya, hudud, sarlavha va narxni aniqlaydi,
                  rasmlarni oladi (ish e'lonlariga rasm qo'yilmaydi).
               </p>
            </div>
            <textarea
               v-model="linksInput"
               rows="4"
               class="admin-input font-mono"
               placeholder="https://t.me/kanal/1234&#10;Bir nechta bo'lsa — har qatorda bittadan"
               :disabled="importing"
            />
            <BaseSwitch v-model="publishToChannel" :disabled="importing">
               <span class="admin-card-title block text-sm!">Kanalga ham chiqarish</span>
               <span class="admin-card-subtitle block">Joylangach, Telegram kanalingizga yuboriladi</span>
            </BaseSwitch>
            <BaseButton :disabled="importing || !links.length" @click="importLinks">
               <template #icon><Link2 class="size-4" :class="{ 'animate-pulse': importing }" /></template>
               {{ importing ? `Joylanmoqda… ${importDone}/${importTotal}` : links.length > 1 ? `${links.length} ta e'lonni joylash` : "Joylash" }}
            </BaseButton>
            <p class="admin-field-hint">
               Faqat ochiq kanal posti (t.me/kanal/123). Guruh va yopiq kanaldan o'qib bo'lmaydi. Bir post ikki marta
               qo'shilmaydi.
            </p>
         </section>

         <section v-if="importResults.length" class="admin-chart-card">
            <div class="admin-card-header">
               <h2 class="admin-card-title">Natijalar</h2>
            </div>
            <div class="admin-list">
               <div v-for="(result, index) in importResults" :key="index" class="admin-list-row items-start!">
                  <img
                     v-if="result.product?.images?.[0]"
                     :src="result.product.images[0]"
                     class="size-12 rounded-lg object-cover shrink-0"
                     alt=""
                  />
                  <span v-else class="size-12 rounded-lg bg-(--z-muted) grid place-items-center shrink-0">
                     <ImageOff class="size-4 text-(--admin-muted)" />
                  </span>
                  <span class="admin-list-main">
                     <span class="admin-list-title">{{ result.product?.title || result.url }}</span>
                     <span v-if="result.product" class="admin-list-meta">
                        {{ [result.product.category, result.product.district, result.product.price ? num(result.product.price) : null, `${result.product.images.length} rasm`].filter(Boolean).join(" · ") }}
                     </span>
                     <span class="admin-list-meta whitespace-normal!">
                        {{ result.message }}
                        <template v-if="result.parsed_by === 'rules'"> AI ishlamadi — qoidalar bo'yicha ajratildi, tekshirib chiqing.</template>
                     </span>
                     <span v-if="result.product" class="flex gap-3 mt-1">
                        <a class="admin-text-link" :href="`/product/${result.product.id}`" target="_blank">Ko'rish</a>
                        <a class="admin-text-link" :href="`/edit-product/${result.product.id}`" target="_blank">Tahrirlash</a>
                     </span>
                  </span>
                  <span class="admin-badge shrink-0" :class="STATUS[result.status]?.class ?? 'admin-badge-danger'">
                     {{ STATUS[result.status]?.label ?? "Xato" }}
                  </span>
               </div>
            </div>
         </section>
      </template>

      <!-- HOLAT: bot ishlayaptimi va bugun nima bo'ldi -->
      <template v-else-if="tab === 'status'">
         <section class="admin-chart-card">
            <div class="admin-switch-row">
               <div class="flex items-center gap-2.5 min-w-0">
                  <span
                     class="size-2.5 rounded-full shrink-0"
                     :class="saved.is_enabled ? 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]' : 'bg-rose-500'"
                  />
                  <div class="min-w-0">
                     <h2 class="admin-card-title">{{ saved.is_enabled ? "Bot ishlayapti" : "Bot to'xtatilgan" }}</h2>
                     <p class="admin-card-subtitle">
                        Oxirgi ishga tushish: {{ stats.last_run_at ? timeAgo(stats.last_run_at) : "hali bo'lmagan" }}
                     </p>
                  </div>
               </div>
               <BaseButton size="sm" severity="secondary" :disabled="runningBatch" @click="runBatchNow">
                  <template #icon><RefreshCw class="size-4" :class="{ 'animate-spin': runningBatch }" /></template>
                  {{ runningBatch ? "Ishlayapti…" : "Ishga tushirish" }}
               </BaseButton>
            </div>
         </section>

         <div class="admin-stats-grid">
            <article class="admin-stat-card">
               <span class="admin-stat-label">Bugun qo'shildi</span>
               <strong>{{ stats.today_imported_count }} <small class="text-sm text-(--admin-muted)">/ {{ saved.daily_limit }}</small></strong>
               <div class="admin-rank-track mt-1.5">
                  <div class="admin-rank-fill" :style="{ width: `${percent(stats.today_imported_count, saved.daily_limit)}%` }" />
               </div>
            </article>
            <article class="admin-stat-card">
               <span class="admin-stat-label">Jami bot e'lonlari</span>
               <strong>{{ num(stats.total_bot_products) }}</strong>
               <span class="admin-stat-detail">odamlarniki: {{ num(stats.total_user_products) }}</span>
            </article>
            <article class="admin-stat-card col-span-2 min-[701px]:col-span-1">
               <span class="admin-stat-label">AI bugun ({{ aiLabel }})</span>
               <template v-if="saved.ai_provider === 'regex'">
                  <span class="admin-stat-detail whitespace-normal!">AI o'chirilgan — e'lonlar qoidalar bo'yicha ajratiladi.</span>
               </template>
               <template v-else>
                  <strong>{{ num(stats.ai_usage.requests) }} <small class="text-sm text-(--admin-muted)">so'rov</small></strong>
                  <span class="admin-stat-detail">
                     {{ formatTokens(stats.ai_usage.tokens) }} / {{ formatTokens(stats.ai_usage.limit) }} token (kunlik limit)
                  </span>
                  <div class="admin-rank-track mt-1.5">
                     <div
                        class="admin-rank-fill"
                        :style="{ width: `${percent(stats.ai_usage.tokens, stats.ai_usage.limit)}%`, background: '#8b5cf6' }"
                     />
                  </div>
               </template>
            </article>
         </div>

         <section class="admin-chart-card">
            <div class="admin-card-header">
               <div>
                  <h2 class="admin-card-title">Bugun ko'rilgan postlar</h2>
                  <p class="admin-card-subtitle">Kanallardan o'qilgan postlar nima bo'ldi</p>
               </div>
            </div>
            <AdminRankList :rows="seenRows" empty="Bugun hali post ko'rilmagan." :limit="8" />
         </section>

         <details class="admin-chart-card group">
            <summary class="admin-card-title cursor-pointer list-none flex items-center justify-between">
               Bot qanday ishlaydi?
               <ChevronDown class="size-4 text-(--admin-muted) transition-transform group-open:rotate-180" />
            </summary>
            <ol class="mt-3 flex flex-col gap-2 text-[12.5px] leading-relaxed text-(--admin-muted) list-decimal pl-4">
               <li>Har soatda (cron) kanallarning so'nggi postlari ochiq <code>t.me/s/kanal</code> sahifasidan o'qiladi.</li>
               <li>7 kundan eski postlar olinmaydi. Bir marta ko'rilgan post qayta tahlil qilinmaydi.</li>
               <li>Telefon yoki @username bo'lmagan, tijoriy reklama va yangilik/tabrik postlari tashlab yuboriladi.</li>
               <li>Bir xil matn (har qanday kanaldan) yoki bir xil telefon bilan o'xshash e'lon qayta qo'shilmaydi.</li>
               <li>
                  AI e'lonmi-yo'qmi aniqlaydi va sarlavha, narx, kategoriya, hududni ajratadi. AI kaliti bo'lmasa yoki
                  "AI'siz" tanlangan bo'lsa — qoidalar bo'yicha, faqat taklif aniq bo'lgan postlar olinadi.
               </li>
               <li>Rasmlar (4 tagacha) WebP qilib saqlanadi; ish va xizmat e'lonlariga rasm olinmaydi.</li>
               <li>Taqiqlangan so'zlar (Moderatsiya bo'limi) bot e'lonlariga ham qo'llanadi.</li>
               <li>Bir siklda ko'pi bilan 1 ta bot e'loni sizning kanalingizga chiqadi (Sozlamalar → Kanalga chiqarish).</li>
            </ol>
         </details>
      </template>

      <!-- SOZLAMALAR: yoqish, limitlar, kanalga chiqarish -->
      <template v-else-if="tab === 'settings'">
         <section class="admin-chart-card flex flex-col gap-4">
            <BaseSwitch v-model="form.is_enabled">
               <span class="admin-card-title block">Botni yoqish</span>
               <span class="admin-card-subtitle block">O'chirilsa, soatlik import ishlamaydi</span>
            </BaseSwitch>

            <div class="admin-field">
               <label for="bot-hourly">Bir siklda qo'shiladigan e'lonlar</label>
               <input id="bot-hourly" v-model.number="form.hourly_limit" type="number" min="1" max="50" class="admin-input" />
               <p class="admin-field-hint">Har soatlik ishga tushishda nechta e'lon qo'shilsin (1–50).</p>
            </div>

            <div class="admin-field">
               <label for="bot-daily">Kunlik chegara</label>
               <input id="bot-daily" v-model.number="form.daily_limit" type="number" min="1" max="1000" class="admin-input" />
               <p class="admin-field-hint">Bugun shuncha e'lon qo'shilgach, bot ertagacha to'xtaydi.</p>
            </div>
         </section>

         <section class="admin-chart-card flex flex-col gap-4">
            <BaseSwitch v-model="form.telegram_post_enabled">
               <span class="admin-card-title block">Kanalga chiqarish</span>
               <span class="admin-card-subtitle block">Bot e'lonlaridan sizning kanalingizga</span>
            </BaseSwitch>

            <template v-if="form.telegram_post_enabled">
               <p class="admin-field-hint">
                  Har siklda ko'pi bilan <b>1 ta</b> e'lon chiqadi: rasmli va narxli bo'lsa birinchi navbatda, bo'lmasa
                  rasmlisi. Rasmsiz bot e'lonlari kanalga chiqmaydi. Odamlar qo'ygan e'lonlar esa bu sozlamaga bog'liq emas
                  — doim darhol chiqadi.
               </p>
               <div class="grid grid-cols-2 gap-3">
                  <div class="admin-field">
                     <label for="bot-start">Boshlanish soati</label>
                     <input id="bot-start" v-model.number="form.telegram_start_hour" type="number" min="0" max="23" class="admin-input" />
                  </div>
                  <div class="admin-field">
                     <label for="bot-end">Tugash soati</label>
                     <input id="bot-end" v-model.number="form.telegram_end_hour" type="number" min="0" max="23" class="admin-input" />
                  </div>
               </div>
               <p class="admin-field-hint">
                  Kanalga soat {{ pad(form.telegram_start_hour) }}:00 dan {{ pad(form.telegram_end_hour) }}:59 gacha chiqariladi.
               </p>
            </template>
         </section>
      </template>

      <!-- KANALLAR: qaysi kanallardan o'qiladi -->
      <template v-else-if="tab === 'channels'">
         <section class="admin-chart-card flex flex-col gap-3">
            <div class="admin-card-header mb-0!">
               <div>
                  <h2 class="admin-card-title">Manba kanallar</h2>
                  <p class="admin-card-subtitle">{{ channelCount }} ta kanal · har qatorda bittadan</p>
               </div>
            </div>
            <textarea
               v-model="form.channels"
               rows="8"
               class="admin-input font-mono text-xs"
               placeholder="@navoiy_bozor&#10;@karmana_bozor"
            />
            <p class="admin-field-hint">
               Kanal ochiq (public) bo'lishi shart: bot uni <code>t.me/s/kanal</code> sahifasidan o'qiydi. Yopiq kanal va
               guruhlardan o'qib bo'lmaydi. <code>@kanal</code> yoki <code>t.me/kanal</code> ko'rinishida yozish mumkin.
            </p>
         </section>
      </template>

      <!-- AI: qaysi model e'lonni tahlil qiladi -->
      <template v-else>
         <section class="admin-chart-card flex flex-col gap-3">
            <div>
               <h2 class="admin-card-title">E'lonni kim tahlil qiladi</h2>
               <p class="admin-card-subtitle">E'lonmi-yo'qmi va sarlavha, narx, kategoriya, hududni ajratish</p>
            </div>

            <div class="admin-list">
               <button
                  v-for="option in providerOptions"
                  :key="option.value"
                  type="button"
                  class="admin-list-row bg-transparent border-x-0 border-t-0 text-left cursor-pointer"
                  @click="form.ai_provider = option.value"
               >
                  <span
                     class="size-4.5 rounded-full border-2 shrink-0 grid place-items-center"
                     :class="form.ai_provider === option.value ? 'border-(--z-primary)' : 'border-(--admin-border)'"
                  >
                     <span v-if="form.ai_provider === option.value" class="size-2 rounded-full bg-(--z-primary)" />
                  </span>
                  <span class="admin-list-main">
                     <span class="admin-list-title">{{ option.label }}</span>
                     <span class="admin-list-meta whitespace-normal!">{{ option.hint }}</span>
                  </span>
               </button>
            </div>
         </section>

         <section v-if="form.ai_provider !== 'regex'" class="admin-chart-card flex flex-col gap-3">
            <div class="admin-field">
               <label for="bot-key">API kalit</label>
               <input
                  id="bot-key"
                  v-model="form.ai_api_key"
                  type="password"
                  autocomplete="off"
                  class="admin-input font-mono text-xs"
                  :placeholder="envKeyExists ? 'Bo\'sh — .env dagi kalit ishlatiladi' : 'Kalitni kiriting'"
               />
               <p v-if="form.ai_api_key" class="admin-field-hint">
                  Shu yerda saqlangan kalit ishlatiladi. O'chirsangiz, serverdagi
                  <code>{{ envKeyName }}</code> ishlatiladi ({{ envKeyExists ? "u o'rnatilgan" : "u yo'q" }}).
               </p>
               <p v-else class="admin-field-hint">
                  Bo'sh — serverdagi <code>{{ envKeyName }}</code> ishlatiladi:
                  <b :class="envKeyExists ? 'text-emerald-600' : 'text-(--z-danger)'">{{ envKeyExists ? "o'rnatilgan" : "o'rnatilmagan" }}</b>.
                  <template v-if="!envKeyExists"> Kalitsiz bot AI'siz (qoidalar bo'yicha) ishlaydi.</template>
               </p>
            </div>
            <p class="admin-field-hint">
               Kunlik limit: {{ formatTokens(stats.ai_usage.limit) }} token (<code>AI_DAILY_TOKEN_LIMIT</code>). Tugasa, import
               ertagacha to'xtaydi. AI xato bersa, sizga bot orqali shaxsan xabar keladi.
            </p>
         </section>
      </template>

      <!-- Saqlanmagan o'zgarishlar bo'lsa — pastda doim ko'rinadi -->
      <div v-if="dirty && ['settings', 'channels', 'ai'].includes(tab)" class="admin-savebar">
         <span class="text-xs font-semibold">Saqlanmagan o'zgarishlar bor</span>
         <BaseButton size="sm" :disabled="saving" @click="saveSettings">
            <template #icon><Save class="size-4" /></template>
            {{ saving ? "Saqlanmoqda…" : "Saqlash" }}
         </BaseButton>
      </div>
   </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { ChevronDown, ImageOff, Link2, RefreshCw, Save } from "lucide-vue-next";
import BaseButton from "@shared/ui/BaseButton.vue";
import BaseSwitch from "@shared/ui/BaseSwitch.vue";
import AdminTabs from "@admin/components/AdminTabs.vue";
import AdminRankList from "@admin/components/AdminRankList.vue";
import { api } from "@shared/composables/useFetch";
import { timeAgo } from "@shared/modules/formatters";

interface IBotStats {
   total_bot_products: number;
   total_user_products: number;
   today_imported_count: number;
   last_run_at: string | null;
   ai_usage: { tokens: number; requests: number; limit: number };
   ai_models: { gemini: string | null; openai: string | null };
   ai_keys: { gemini: boolean; openai: boolean };
   today_seen: Record<string, number>;
}

interface IBotSetting {
   is_enabled: boolean;
   hourly_limit: number;
   daily_limit: number;
   channels: string;
   telegram_post_enabled: boolean;
   telegram_start_hour: number;
   telegram_end_hour: number;
   ai_provider: "gemini" | "openai" | "regex";
   ai_api_key: string;
}

const tabs = [
   { value: "link", label: "Qo'shish" },
   { value: "status", label: "Holat" },
   { value: "settings", label: "Sozlamalar" },
   { value: "channels", label: "Kanallar" },
   { value: "ai", label: "AI" },
];
const tab = ref("link");

/* ---------- Havoladan qo'shish ---------- */

type ImportResult = {
   url: string;
   status: string;
   message: string;
   parsed_by?: "ai" | "rules" | null;
   product?: { id: number; title: string; price: number | null; category: string | null; district: string | null; images: string[] } | null;
};
const STATUS: Record<string, { label: string; class: string }> = {
   imported: { label: "Qo'shildi", class: "admin-badge-success" },
   exists: { label: "Avval qo'shilgan", class: "admin-badge-muted" },
};

const linksInput = ref("");
const publishToChannel = ref(true);
const importing = ref(false);
const importDone = ref(0);
const importTotal = ref(0);
const importResults = ref<ImportResult[]>([]);
const links = computed(() => [...new Set(linksInput.value.split(/\s+/).map((line) => line.trim()).filter(Boolean))]);

// Bittadan ketma-ket: har biri AI va rasm yuklashni kutadi; muvaffaqiyatlisi maydondan olib tashlanadi
async function importLinks() {
   const queue = links.value;
   importing.value = true;
   importDone.value = 0;
   importTotal.value = queue.length;
   const failed: string[] = [];

   for (const url of queue) {
      try {
         const { data } = await api.post("admin/bot-import-url", { url, publish: publishToChannel.value });
         importResults.value.unshift({ url, ...data });
      } catch (e: any) {
         const data = e?.response?.data ?? {};
         importResults.value.unshift({ url, status: data.status ?? "error", message: data.message || "Joylab bo'lmadi." });
         failed.push(url);
      } finally {
         importDone.value++;
      }
   }

   linksInput.value = failed.join("\n");
   importing.value = false;
   loadSettings();
}

const defaults: IBotSetting = {
   is_enabled: false,
   hourly_limit: 7,
   daily_limit: 150,
   channels: "",
   telegram_post_enabled: true,
   telegram_start_hour: 8,
   telegram_end_hour: 23,
   ai_provider: "gemini",
   ai_api_key: "",
};
const form = ref<IBotSetting>({ ...defaults });
const saved = ref<IBotSetting>({ ...defaults });

const stats = ref<IBotStats>({
   total_bot_products: 0,
   total_user_products: 0,
   today_imported_count: 0,
   last_run_at: null,
   ai_usage: { tokens: 0, requests: 0, limit: 0 },
   ai_models: { gemini: null, openai: null },
   ai_keys: { gemini: false, openai: false },
   today_seen: {},
});

const saving = ref(false);
const runningBatch = ref(false);
const message = ref<string | null>(null);
const error = ref<string | null>(null);

const dirty = computed(() => JSON.stringify(form.value) !== JSON.stringify(saved.value));
const channelCount = computed(() => form.value.channels.split("\n").filter((line) => line.trim()).length);
const envKeyName = computed(() => (form.value.ai_provider === "openai" ? "OPENAI_API_KEY" : "GEMINI_API_KEY"));
const envKeyExists = computed(() => stats.value.ai_keys[form.value.ai_provider === "openai" ? "openai" : "gemini"]);

const providerOptions = computed(() => [
   {
      value: "gemini" as const,
      label: `Google Gemini${stats.value.ai_models.gemini ? ` · ${stats.value.ai_models.gemini}` : ""}`,
      hint: "Asosiy variant. Model serverdagi GEMINI_MODEL bilan belgilanadi.",
   },
   {
      value: "openai" as const,
      label: `OpenAI${stats.value.ai_models.openai ? ` · ${stats.value.ai_models.openai}` : ""}`,
      hint: "Model serverdagi OPENAI_MODEL bilan belgilanadi.",
   },
   {
      value: "regex" as const,
      label: "AI'siz — qoidalar bo'yicha",
      hint: "Bepul, lekin aniqligi past: faqat taklifi aniq postlar olinadi, kategoriya kalit so'zlardan topiladi.",
   },
]);
const aiLabel = computed(() => {
   const provider = saved.value.ai_provider;
   return provider === "regex" ? "o'chirilgan" : (stats.value.ai_models[provider] ?? provider);
});

const SEEN_LABELS: Record<string, string> = {
   imported: "Qo'shildi",
   filtered: "Filtrlandi (kontakt yo'q, reklama…)",
   duplicate: "Dublikat",
   rejected: "AI: e'lon emas",
   old: "Eski (7 kundan oshgan)",
};
const seenRows = computed(() =>
   Object.entries(stats.value.today_seen ?? {})
      .sort((a, b) => b[1] - a[1])
      .map(([status, count]) => ({ label: SEEN_LABELS[status] ?? status, value: count, detail: `${count} ta` })),
);

const num = (value: number | null | undefined) => Number(value ?? 0).toLocaleString("ru-RU");
const pad = (hour: number) => String(hour ?? 0).padStart(2, "0");
const percent = (value: number, total: number) => (total > 0 ? Math.min(100, Math.round((value / total) * 100)) : 0);

function formatTokens(value: number) {
   return value >= 1000 ? `${Math.round(value / 1000)}K` : String(value ?? 0);
}

async function loadSettings() {
   try {
      const { data } = await api.get("admin/bot-settings");
      if (data?.setting) {
         const setting = {
            ...defaults,
            ...Object.fromEntries(Object.keys(defaults).map((key) => [key, data.setting[key]])),
            channels: data.setting.channels || "",
            ai_api_key: data.setting.ai_api_key || "",
         } as IBotSetting;
         form.value = { ...setting };
         saved.value = { ...setting };
      }
      if (data?.stats) stats.value = data.stats;
   } catch (e: any) {
      error.value = e?.response?.data?.message || "Bot sozlamalarini yuklashda xatolik.";
   }
}

async function saveSettings() {
   saving.value = true;
   message.value = null;
   error.value = null;
   try {
      const { data } = await api.post("admin/bot-settings", form.value);
      message.value = data?.message || "Sozlamalar saqlandi.";
      await loadSettings();
   } catch (e: any) {
      error.value = e?.response?.data?.message || "Saqlashda xatolik yuz berdi.";
   } finally {
      saving.value = false;
   }
}

async function runBatchNow() {
   runningBatch.value = true;
   message.value = null;
   error.value = null;
   try {
      const { data } = await api.post("admin/bot-settings/run-now");
      message.value = data?.message || "Sikl bajarildi.";
      await loadSettings();
   } catch (e: any) {
      error.value = e?.response?.data?.message || "Siklni ishga tushirishda xatolik.";
   } finally {
      runningBatch.value = false;
   }
}

onMounted(loadSettings);
</script>
