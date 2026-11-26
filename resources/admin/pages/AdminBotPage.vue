<template>
   <section class="admin-page">
      <AdminProductEditDrawer :product-id="editId" @close="editId = null" @saved="(text) => (message = text)" />

      <header class="admin-page-heading">
         <div>
            <h1>Avto-e'lonlar</h1>
            <p class="admin-muted">Guruh va kanallardagi e'lonlarni matn yoki havola orqali, yoki har soatda avtomatik qo'shish.</p>
         </div>
      </header>

      <p v-if="message" class="admin-alert admin-alert-success">{{ message }}</p>
      <p v-if="error" class="admin-alert admin-alert-error">{{ error }}</p>

      <AdminTabs v-model="tab" :tabs="tabs" />

      <!-- HAVOLA: tanlangan postlar — filtrsiz, albatta joylanadi -->
      <template v-if="tab === 'link'">
         <!-- Buferda havola bor (ruxsat bo'lsa o'zi topadi) yoki "Buferdan" bosildi: avval ko'rib olasiz -->
         <section v-if="clipLinks.length || clipNote" class="adm-clip">
            <header class="adm-clip-head">
               <ClipboardPaste class="size-4.5 shrink-0" aria-hidden="true" />
               <b v-if="clipLinks.length">Buferda {{ clipLinks.length }} ta havola</b>
               <b v-else>{{ clipNote }}</b>
               <button type="button" class="adm-icon-btn is-small ml-auto" aria-label="Yopish" @click="dismissClip">
                  <X class="size-4" />
               </button>
            </header>
            <ul v-if="clipLinks.length" class="adm-link-list">
               <li v-for="link in clipLinks" :key="link.url" class="adm-link-card">
                  <span class="adm-link-icon"><Send class="size-4" aria-hidden="true" /></span>
                  <span class="adm-link-text"><b>@{{ link.channel }}</b><small>post №{{ link.post }}</small></span>
                  <span v-if="hasLink(link)" class="admin-badge admin-badge-muted">ro'yxatda</span>
               </li>
            </ul>
            <div v-if="clipLinks.length" class="adm-clip-actions">
               <BaseButton size="sm" severity="secondary" @click="dismissClip">Kerak emas</BaseButton>
               <BaseButton size="sm" :disabled="!newClipLinks.length" @click="acceptClip">
                  <template #icon><Plus class="size-4" /></template>
                  {{ newClipLinks.length ? `${newClipLinks.length} tasini qo'shish` : "Hammasi ro'yxatda" }}
               </BaseButton>
            </div>
         </section>

         <section class="admin-chart-card flex flex-col gap-3">
            <div class="flex items-start justify-between gap-3">
               <div class="min-w-0">
                  <h2 class="admin-card-title">Post havolasidan e'lon</h2>
                  <p class="admin-card-subtitle">Ochiq kanal posti: t.me/kanal/123. AI kategoriya, narx, hudud va rasmlarni o'zi oladi.</p>
               </div>
               <BaseButton size="sm" severity="secondary" class="shrink-0" @click="peekLinks">
                  <template #icon><ClipboardPaste class="size-4" /></template>
                  Buferdan
               </BaseButton>
            </div>

            <!-- Qo'shilgan havolalar: kartochka ko'rinishida -->
            <ul v-if="links.length" class="adm-link-list">
               <li v-for="link in links" :key="link.url" class="adm-link-card">
                  <span class="adm-link-icon"><Send class="size-4" aria-hidden="true" /></span>
                  <span class="adm-link-text">
                     <b>@{{ link.channel }}</b>
                     <small>post №{{ link.post }}</small>
                  </span>
                  <a :href="link.url" target="_blank" rel="noopener" class="adm-icon-btn is-small" aria-label="Postni ochish">
                     <ExternalLink class="size-4" />
                  </a>
                  <button type="button" class="adm-icon-btn is-small" aria-label="Olib tashlash" :disabled="importing" @click="removeLink(link)">
                     <X class="size-4" />
                  </button>
               </li>
            </ul>

            <!-- Qo'lda: yozing yoki Ctrl+V — havola darhol kartochkaga aylanadi -->
            <label class="admin-search">
               <Link2 class="size-4 shrink-0" aria-hidden="true" />
               <input
                  v-model="linkDraft"
                  type="url"
                  inputmode="url"
                  enterkeyhint="done"
                  placeholder="Havolani shu yerga joylang (Ctrl+V)"
                  :disabled="importing"
                  @paste="onLinkPaste"
                  @keydown.enter.prevent="commitLinkDraft"
                  @blur="commitLinkDraft"
               />
            </label>
            <p v-if="linkDraftError" class="text-xs text-(--z-danger) -mt-1">{{ linkDraftError }}</p>

            <BaseSwitch v-model="publishToChannel" :disabled="importing">
               <span class="admin-card-title block text-sm!">Kanalga ham chiqarish</span>
               <span class="admin-card-subtitle block">Joylangach, Telegram kanalingizga yuboriladi</span>
            </BaseSwitch>
            <BaseButton :disabled="importing || !links.length" @click="importLinks">
               <template #icon><Sparkles class="size-4" :class="{ 'animate-pulse': importing }" /></template>
               {{ importing ? `Joylanmoqda… ${importDone}/${importTotal}` : links.length > 1 ? `${links.length} ta e'lonni joylash` : "Joylash" }}
            </BaseButton>
            <p class="admin-field-hint">Guruh va yopiq kanaldan o'qib bo'lmaydi. Bir post ikki marta qo'shilmaydi.</p>
         </section>
      </template>

      <!-- MATN: guruhdan nusxalangan e'lon matni + rasmlar — filtrsiz, albatta joylanadi -->
      <template v-else-if="tab === 'text'">
         <!-- Buferda matn bor (ruxsat bo'lsa o'zi topadi) -->
         <section v-if="clipText" class="adm-clip">
            <header class="adm-clip-head">
               <ClipboardPaste class="size-4.5 shrink-0" aria-hidden="true" />
               <b>Buferda matn bor</b>
               <button type="button" class="adm-icon-btn is-small ml-auto" aria-label="Yopish" @click="dismissClip">
                  <X class="size-4" />
               </button>
            </header>
            <p class="adm-clip-text">{{ clipText }}</p>
            <div class="adm-clip-actions">
               <BaseButton size="sm" severity="secondary" @click="dismissClip">Kerak emas</BaseButton>
               <BaseButton size="sm" @click="acceptClipText">
                  <template #icon><ClipboardPaste class="size-4" /></template>
                  Qo'yish
               </BaseButton>
            </div>
         </section>

         <section
            v-for="(draft, index) in drafts"
            :key="draft.id"
            class="admin-chart-card flex flex-col gap-2.5"
            @paste="onPaste(draft, $event)"
            @dragover.prevent
            @drop.prevent="addImages(draft, $event.dataTransfer?.files)"
         >
            <div class="flex items-center justify-between gap-2">
               <span class="admin-card-title text-sm!">E'lon {{ index + 1 }}</span>
               <div class="flex items-center gap-1.5">
                  <BaseButton size="sm" severity="secondary" :disabled="importing" @click="pasteInto(draft)">
                     <template #icon><ClipboardPaste class="size-4" /></template>
                     Buferdan qo'yish
                  </BaseButton>
                  <button
                     v-if="drafts.length > 1"
                     type="button"
                     class="admin-icon-button"
                     aria-label="Olib tashlash"
                     :disabled="importing"
                     @click="removeDraft(draft)"
                  >
                     <X class="size-4" />
                  </button>
               </div>
            </div>
            <textarea
               v-model="draft.text"
               rows="6"
               class="admin-input"
               placeholder="E'lon matnini joylang (Ctrl+V). Rasmlarni ham shu yerga Ctrl+V qilish mumkin."
               :disabled="importing"
               @input="draft.error = ''"
            />
            <p v-if="draft.error || needsContact(draft)" class="text-xs text-rose-500 -mt-1">
               {{ draft.error || "Bog'lanish uchun ma'lumot bering: matnga telefon raqami yoki @username yozing." }}
            </p>
            <div class="flex flex-wrap gap-2">
               <span v-for="(image, imageIndex) in draft.images" :key="image.url" class="relative">
                  <img :src="image.url" class="size-16 rounded-lg object-cover" alt="" />
                  <button
                     type="button"
                     class="absolute -top-1.5 -right-1.5 size-5 rounded-full bg-rose-500 text-white grid place-items-center"
                     aria-label="Rasmni olib tashlash"
                     :disabled="importing"
                     @click="removeImage(draft, imageIndex)"
                  >
                     <X class="size-3" />
                  </button>
               </span>
               <label
                  v-if="draft.images.length < MAX_IMAGES"
                  class="size-16 rounded-lg border border-dashed border-(--admin-border) grid place-items-center cursor-pointer text-(--admin-muted)"
                  title="Rasm qo'shish"
               >
                  <ImagePlus class="size-5" />
                  <input type="file" accept="image/*" multiple class="hidden" :disabled="importing" @change="onPick(draft, $event)" />
               </label>
            </div>
            <!-- E'lon qancha turadi: kategoriya muddati, o'zim aytgan kun yoki muddatsiz -->
            <div class="flex flex-wrap items-center gap-2">
               <span class="text-xs text-(--admin-muted)">Turadi:</span>
               <div class="min-w-48">
                  <FieldSelect
                     v-model="draft.duration"
                     size="sm"
                     :disabled="importing"
                     :options="[
                        { label: 'Kategoriya muddati', value: 'category' },
                        { label: 'Necha kun…', value: 'days' },
                        { label: 'Muddatsiz (o\'chmaydi)', value: 'permanent' },
                     ]"
                  />
               </div>
               <template v-if="draft.duration === 'days'">
                  <input v-model.number="draft.days" type="number" min="1" max="3650" class="admin-input w-24! h-9! py-0! text-sm" :disabled="importing" />
                  <span class="text-xs text-(--admin-muted)">kun</span>
               </template>
            </div>
         </section>

         <section class="admin-chart-card flex flex-col gap-3">
            <BaseButton severity="secondary" :disabled="importing" @click="addDraft">
               <template #icon><Plus class="size-4" /></template>
               Yana e'lon qo'shish
            </BaseButton>
            <BaseSwitch v-model="publishToChannel" :disabled="importing">
               <span class="admin-card-title block text-sm!">Kanalga ham chiqarish</span>
               <span class="admin-card-subtitle block">Joylangach, Telegram kanalingizga yuboriladi</span>
            </BaseSwitch>
            <BaseButton :disabled="importing || !readyDrafts.length" @click="importDrafts">
               <template #icon><Sparkles class="size-4" :class="{ 'animate-pulse': importing }" /></template>
               {{ importing ? `Joylanmoqda… ${importDone}/${importTotal}` : readyDrafts.length > 1 ? `${readyDrafts.length} ta e'lonni joylash` : "Joylash" }}
            </BaseButton>
            <p class="admin-field-hint">AI sarlavha, narx, kategoriya va hududni o'zi ajratadi. Aynan shu matn avval qo'shilgan bo'lsa, qayta qo'shilmaydi.</p>
         </section>
      </template>

      <!-- AI: avtomatik import — holat, sozlamalar, kanallar va model bitta joyda -->
      <template v-else>
         <section class="admin-chart-card flex flex-col gap-3">
            <div class="admin-switch-row">
               <div class="flex items-center gap-2.5 min-w-0">
                  <span class="adm-dot" :class="saved.is_enabled ? 'is-active' : ''" />
                  <div class="min-w-0">
                     <h2 class="admin-card-title">{{ saved.is_enabled ? "Bot ishlayapti" : "Bot to'xtatilgan" }}</h2>
                     <p class="admin-card-subtitle">
                        Oxirgi ishga tushish: {{ stats.last_run_at ? timeAgo(stats.last_run_at) : "hali bo'lmagan" }}
                     </p>
                  </div>
               </div>
               <BaseButton size="sm" severity="secondary" :disabled="runningBatch" @click="runBatchNow">
                  <template #icon><RefreshCw class="size-4" :class="{ 'animate-spin': runningBatch }" /></template>
                  {{ runningBatch ? "Ishlayapti…" : "Hozir ishga tushirish" }}
               </BaseButton>
            </div>
            <BaseSwitch v-model="form.is_enabled">
               <span class="admin-card-title block text-sm!">Har soatda avtomatik</span>
               <span class="admin-card-subtitle block">O'chirilsa, kanallardan o'zi olib kelmaydi</span>
            </BaseSwitch>
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
            <article class="admin-stat-card">
               <span class="admin-stat-label">AI bugun ({{ aiLabel }})</span>
               <template v-if="saved.ai_provider === 'regex'">
                  <span class="admin-stat-detail whitespace-normal!">AI o'chirilgan — qoidalar bo'yicha.</span>
               </template>
               <template v-else>
                  <strong>{{ num(stats.ai_usage.requests) }} <small class="text-sm text-(--admin-muted)">so'rov</small></strong>
                  <span class="admin-stat-detail">{{ formatTokens(stats.ai_usage.tokens) }} / {{ formatTokens(stats.ai_usage.limit) }} token</span>
                  <div class="admin-rank-track mt-1.5">
                     <div class="admin-rank-fill" :style="{ width: `${percent(stats.ai_usage.tokens, stats.ai_usage.limit)}%`, background: 'var(--chart-b)' }" />
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

         <!-- Manba kanallar -->
         <section class="admin-chart-card flex flex-col gap-3">
            <div>
               <h2 class="admin-card-title">Qaysi kanallardan olinadi</h2>
               <p class="admin-card-subtitle">{{ channelCount }} ta kanal · har qatorda bittadan · faqat ochiq kanallar</p>
            </div>
            <textarea v-model="form.channels" rows="6" class="admin-input font-mono text-xs" placeholder="@navoiy_bozor&#10;@karmana_bozor" />
         </section>

         <!-- Limitlar va kanalga chiqarish -->
         <section class="admin-chart-card flex flex-col gap-4">
            <h2 class="admin-card-title">Qancha va qachon</h2>
            <div class="grid grid-cols-2 gap-3">
               <div class="admin-field">
                  <label for="bot-hourly">Bir siklda</label>
                  <input id="bot-hourly" v-model.number="form.hourly_limit" type="number" min="1" max="50" class="admin-input" />
                  <p class="admin-field-hint">har soatda (1–50)</p>
               </div>
               <div class="admin-field">
                  <label for="bot-daily">Kunlik chegara</label>
                  <input id="bot-daily" v-model.number="form.daily_limit" type="number" min="1" max="1000" class="admin-input" />
                  <p class="admin-field-hint">keyin ertagacha to'xtaydi</p>
               </div>
            </div>
            <BaseSwitch v-model="form.telegram_post_enabled">
               <span class="admin-card-title block text-sm!">Kanalimga ham chiqarish</span>
               <span class="admin-card-subtitle block">Har siklda ko'pi bilan 1 ta, rasmlisi birinchi</span>
            </BaseSwitch>
            <div v-if="form.telegram_post_enabled" class="grid grid-cols-2 gap-3">
               <div class="admin-field">
                  <label for="bot-start">Soatdan</label>
                  <input id="bot-start" v-model.number="form.telegram_start_hour" type="number" min="0" max="23" class="admin-input" />
               </div>
               <div class="admin-field">
                  <label for="bot-end">Soatgacha</label>
                  <input id="bot-end" v-model.number="form.telegram_end_hour" type="number" min="0" max="23" class="admin-input" />
               </div>
               <p class="admin-field-hint col-span-2">
                  Kanalga {{ pad(form.telegram_start_hour) }}:00 – {{ pad(form.telegram_end_hour) }}:59 oralig'ida chiqadi.
               </p>
            </div>
         </section>

         <!-- Model -->
         <section class="admin-chart-card flex flex-col gap-3">
            <div>
               <h2 class="admin-card-title">E'lonni kim tahlil qiladi</h2>
               <p class="admin-card-subtitle">Havola va matn bilan qo'shishda ham shu ishlatiladi</p>
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
            <div v-if="form.ai_provider !== 'regex'" class="admin-field">
               <label for="bot-key">API kalit</label>
               <input
                  id="bot-key"
                  v-model="form.ai_api_key"
                  type="password"
                  autocomplete="off"
                  class="admin-input font-mono text-xs"
                  :placeholder="envKeyExists ? 'Bo\'sh — .env dagi kalit ishlatiladi' : 'Kalitni kiriting'"
               />
               <p class="admin-field-hint">
                  <template v-if="form.ai_api_key">Shu yerdagi kalit ishlatiladi.</template>
                  <template v-else>
                     Bo'sh — serverdagi <code>{{ envKeyName }}</code>:
                     <b :class="envKeyExists ? 'text-emerald-600' : 'text-(--z-danger)'">{{ envKeyExists ? "o'rnatilgan" : "o'rnatilmagan" }}</b>.
                  </template>
                  Kunlik limit: {{ formatTokens(stats.ai_usage.limit) }} token.
               </p>
            </div>
         </section>

         <details class="admin-chart-card group">
            <summary class="admin-card-title cursor-pointer list-none flex items-center justify-between">
               Bot qanday ishlaydi?
               <ChevronDown class="size-4 text-(--admin-muted) transition-transform group-open:rotate-180" />
            </summary>
            <ol class="mt-3 flex flex-col gap-2 text-[13px] leading-relaxed text-(--admin-muted) list-decimal pl-4">
               <li>Har soatda kanallarning so'nggi postlari ochiq <code>t.me/s/kanal</code> sahifasidan o'qiladi.</li>
               <li>7 kundan eski postlar olinmaydi. Bir marta ko'rilgan post qayta tahlil qilinmaydi.</li>
               <li>Telefon yoki @username bo'lmagan, tijoriy reklama va yangilik/tabrik postlari tashlab yuboriladi.</li>
               <li>Bir xil matn yoki bir xil telefon bilan o'xshash e'lon qayta qo'shilmaydi.</li>
               <li>AI e'lonmi-yo'qmi aniqlaydi va sarlavha, narx, kategoriya, hududni ajratadi.</li>
               <li>Rasmlar (4 tagacha) saqlanadi; rasmi o'chirilgan kategoriyalarga (Ish, Ta'lim) rasm olinmaydi.</li>
               <li>Taqiqlangan so'zlar (Moderatsiya) bot e'lonlariga ham qo'llanadi.</li>
            </ol>
         </details>
      </template>

      <!-- Matndan va havoladan qo'shish natijalari -->
      <section v-if="importResults.length && ['text', 'link'].includes(tab)" class="admin-chart-card">
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
                     <button type="button" class="admin-text-link cursor-pointer" @click="editId = result.product.id">Tahrirlash</button>
                  </span>
               </span>
               <span class="admin-badge shrink-0" :class="STATUS[result.status]?.class ?? 'admin-badge-danger'">
                  {{ STATUS[result.status]?.label ?? "Xato" }}
               </span>
            </div>
         </div>
      </section>

      <!-- Saqlanmagan o'zgarishlar bo'lsa — pastda doim ko'rinadi -->
      <div v-if="dirty && tab === 'ai'" class="admin-savebar">
         <span class="text-xs font-semibold">Saqlanmagan o'zgarishlar bor</span>
         <BaseButton size="sm" :disabled="saving" @click="saveSettings">
            <template #icon><Save class="size-4" /></template>
            {{ saving ? "Saqlanmoqda…" : "Saqlash" }}
         </BaseButton>
      </div>
   </section>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { ChevronDown, ClipboardPaste, ExternalLink, ImageOff, ImagePlus, Link2, Plus, RefreshCw, Save, Send, Sparkles, X } from "lucide-vue-next";
import { canReadSilently, parsePostLinks, readClipboardImages, readClipboardText, type PostLink } from "@admin/modules/clipboard";
import BaseButton from "@shared/ui/BaseButton.vue";
import BaseSwitch from "@shared/ui/BaseSwitch.vue";
import AdminTabs from "@admin/components/AdminTabs.vue";
import AdminRankList from "@admin/components/AdminRankList.vue";
import AdminProductEditDrawer from "@admin/components/AdminProductEditDrawer.vue";
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

// Uch yo'l: havola, matn va AI (avtomatik import: holat, sozlamalar, kanallar, model)
const tabs = [
   { value: "link", label: "Havola" },
   { value: "text", label: "Matn" },
   { value: "ai", label: "AI (avtomatik)" },
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

const publishToChannel = ref(true);
const importing = ref(false);
const importDone = ref(0);
const importTotal = ref(0);
const importResults = ref<ImportResult[]>([]);

// Havolalar — kartochkalar ro'yxati; qo'lda yozilgani "linkDraft"da turadi
const links = ref<PostLink[]>([]);
const linkDraft = ref("");
const linkDraftError = ref("");

const hasLink = (link: PostLink) => links.value.some((item) => item.url.toLowerCase() === link.url.toLowerCase());

function addLinks(found: PostLink[]) {
   for (const link of found) if (!hasLink(link)) links.value.push(link);
}

function removeLink(link: PostLink) {
   links.value = links.value.filter((item) => item.url !== link.url);
}

/** Ctrl+V: havola(lar) bo'lsa — darhol kartochkaga aylanadi */
function onLinkPaste(event: ClipboardEvent) {
   const found = parsePostLinks(event.clipboardData?.getData("text/plain") ?? "");
   if (!found.length) return;
   event.preventDefault();
   addLinks(found);
   linkDraftError.value = "";
}

function commitLinkDraft() {
   const text = linkDraft.value.trim();
   if (!text) {
      linkDraftError.value = "";
      return;
   }
   const found = parsePostLinks(text);
   if (found.length) {
      addLinks(found);
      linkDraft.value = "";
      linkDraftError.value = "";
   } else {
      linkDraftError.value = "Bu post havolasi emas. Namuna: https://t.me/kanal/1234";
   }
}

/* ---------- Bufer: nima borligini oldindan ko'rsatish ---------- */

const clipLinks = ref<PostLink[]>([]);
const clipText = ref("");
const clipNote = ref("");
// Bir marta "Kerak emas" deyilgan bufer qayta taklif qilinmaydi
let dismissed = "";
let lastClip = "";

const newClipLinks = computed(() => clipLinks.value.filter((link) => !hasLink(link)));

function dismissClip() {
   dismissed = lastClip;
   clipLinks.value = [];
   clipText.value = "";
   clipNote.value = "";
}

function showClip(text: string, silent: boolean) {
   lastClip = text;
   if (silent && (!text.trim() || text === dismissed)) return;
   clipNote.value = "";
   if (tab.value === "link") {
      const found = parsePostLinks(text);
      // Jim tekshiruvda: faqat yangi havola bo'lsa taklif qilinadi
      if (silent && !found.some((link) => !hasLink(link))) return;
      clipLinks.value = found;
      if (!found.length) clipNote.value = text.trim() ? "Buferda post havolasi yo'q" : "Bufer bo'sh";
   } else if (tab.value === "text") {
      const trimmed = text.trim();
      if (silent && (trimmed.length < 10 || drafts.value.some((draft) => draft.text.trim() === trimmed))) return;
      clipText.value = trimmed;
   }
}

/** "Buferdan" tugmasi: o'qiydi va avval ko'rsatadi (darhol qo'shmaydi) */
async function peekLinks() {
   const text = await readClipboardText();
   if (text === null) {
      clipLinks.value = [];
      clipNote.value = "Brauzer buferni bermadi — havolani pastdagi maydonga bosib turib «Qo'yish» qiling yoki Ctrl+V";
      return;
   }
   showClip(text, false);
}

function acceptClip() {
   addLinks(newClipLinks.value);
   dismissClip();
}

function acceptClipText() {
   const target = drafts.value.find((draft) => !draft.text.trim()) ?? (addDraft(), drafts.value[drafts.value.length - 1]);
   target.text = clipText.value;
   target.error = "";
   dismissClip();
}

/** Ruxsat avval berilgan bo'lsa (Chrome) — sahifaga qaytganda bufer jim tekshiriladi */
async function checkClipboardSilently() {
   if (document.visibilityState !== "visible" || tab.value === "ai" || importing.value) return;
   if (!(await canReadSilently())) return;
   const text = await readClipboardText();
   if (text !== null && text !== lastClip) showClip(text, true);
}

watch(tab, () => {
   clipLinks.value = [];
   clipText.value = "";
   clipNote.value = "";
   lastClip = "";
   checkClipboardSilently();
});

onMounted(() => {
   window.addEventListener("focus", checkClipboardSilently);
   document.addEventListener("visibilitychange", checkClipboardSilently);
   checkClipboardSilently();
});
onBeforeUnmount(() => {
   window.removeEventListener("focus", checkClipboardSilently);
   document.removeEventListener("visibilitychange", checkClipboardSilently);
});

// Bittadan ketma-ket: har biri AI va rasm yuklashni kutadi; muvaffaqiyatlisi ro'yxatdan olib tashlanadi
async function importLinks() {
   commitLinkDraft();
   const queue = [...links.value];
   importing.value = true;
   importDone.value = 0;
   importTotal.value = queue.length;
   const failed: PostLink[] = [];

   for (const link of queue) {
      try {
         const { data } = await api.post("admin/bot-import-url", { url: link.url, publish: publishToChannel.value });
         importResults.value.unshift({ url: link.url, ...data });
      } catch (e: any) {
         const data = e?.response?.data ?? {};
         importResults.value.unshift({ url: link.url, status: data.status ?? "error", message: data.message || "Joylab bo'lmadi." });
         failed.push(link);
      } finally {
         importDone.value++;
      }
   }

   links.value = failed;
   importing.value = false;
   loadSettings();
}

/* ---------- Matndan qo'shish ---------- */

const MAX_IMAGES = 10;
type DraftImage = { file: File; url: string };
// Muddat: "category" — kategoriya bo'yicha, "days" — N kun, "permanent" — muddatsiz (o'chmaydi)
type Duration = "category" | "days" | "permanent";
type Draft = { id: number; text: string; images: DraftImage[]; duration: Duration; days: number; error: string };

let draftSeq = 0;
const newDraft = (): Draft => ({ id: ++draftSeq, text: "", images: [], duration: "category", days: 30, error: "" });
const drafts = ref<Draft[]>([newDraft()]);

// Telefon (93 123 45 67, +998931234567, 93-123-45-67) yoki @username — server ham shuni talab qiladi
const PHONE_RE = /(?<!\d)(?:\+?998[\s.-]*)?\(?[2-9]\d\)?[\s.-]?\d{3}[\s.-]?\d{2}[\s.-]?\d{2}(?!\d)/;
const USERNAME_RE = /@[A-Za-z][A-Za-z0-9_]{3,}/;
const hasContact = (text: string) => PHONE_RE.test(text) || USERNAME_RE.test(text);
/** Matn yozilgan, lekin xaridor muallifga yeta oladigan aloqa yo'q — e'lon joylanmaydi */
const needsContact = (draft: Draft) => draft.text.trim().length >= 10 && !hasContact(draft.text);

const readyDrafts = computed(() => drafts.value.filter((draft) => draft.text.trim().length >= 10 && hasContact(draft.text)));

function addDraft() {
   drafts.value.push(newDraft());
}

function removeDraft(draft: Draft) {
   draft.images.forEach((image) => URL.revokeObjectURL(image.url));
   drafts.value = drafts.value.filter((item) => item.id !== draft.id);
}

function removeImage(draft: Draft, index: number) {
   URL.revokeObjectURL(draft.images[index].url);
   draft.images.splice(index, 1);
}

// Telefon rasmlari va nusxalangan PNG'lar katta — yuborishdan oldin 1600px JPEG'ga kichraytiriladi
async function shrinkImage(file: File): Promise<File> {
   try {
      const bitmap = await createImageBitmap(file);
      const scale = Math.min(1, 1600 / Math.max(bitmap.width, bitmap.height));
      const canvas = document.createElement("canvas");
      canvas.width = Math.round(bitmap.width * scale);
      canvas.height = Math.round(bitmap.height * scale);
      canvas.getContext("2d")!.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
      bitmap.close();
      const blob = await new Promise<Blob | null>((resolve) => canvas.toBlob(resolve, "image/jpeg", 0.85));
      return blob ? new File([blob], "image.jpg", { type: "image/jpeg" }) : file;
   } catch {
      return file;
   }
}

async function addImages(draft: Draft, files?: FileList | File[] | null) {
   const images = [...(files ?? [])].filter((file) => file.type.startsWith("image/"));
   for (const file of images.slice(0, MAX_IMAGES - draft.images.length)) {
      const shrunk = await shrinkImage(file);
      if (draft.images.length < MAX_IMAGES) {
         draft.images.push({ file: shrunk, url: URL.createObjectURL(shrunk) });
      }
   }
}

function onPick(draft: Draft, event: Event) {
   const input = event.target as HTMLInputElement;
   addImages(draft, input.files);
   input.value = "";
}

// Ctrl+V: rasm bo'lsa — rasmlarga qo'shiladi, matn esa odatdagidek maydonga tushadi
function onPaste(draft: Draft, event: ClipboardEvent) {
   const files = [...(event.clipboardData?.files ?? [])].filter((file) => file.type.startsWith("image/"));
   if (!files.length) return;
   if (!event.clipboardData?.getData("text/plain")) event.preventDefault();
   addImages(draft, files);
}

/** "Buferdan qo'yish": matn — maydonga (bor matn o'rniga), rasm bo'lsa — rasmlarga */
async function pasteInto(draft: Draft) {
   const [text, images] = await Promise.all([readClipboardText(), readClipboardImages()]);
   if (text === null && !images.length) {
      draft.error = "Brauzer buferni bermadi — maydonni bosib turib «Qo'yish» qiling yoki Ctrl+V.";
      return;
   }
   if (text?.trim()) draft.text = text.trim();
   if (images.length) await addImages(draft, images);
   if (!text?.trim() && !images.length) draft.error = "Bufer bo'sh.";
   else draft.error = "";
   dismissClip();
}

async function importDrafts() {
   const queue = readyDrafts.value;
   importing.value = true;
   importDone.value = 0;
   importTotal.value = queue.length;

   for (const draft of queue) {
      const label = draft.text.trim().split("\n")[0].slice(0, 80);
      const body = new FormData();
      body.append("text", draft.text);
      body.append("publish", publishToChannel.value ? "1" : "0");
      body.append("duration", draft.duration);
      if (draft.duration === "days") body.append("days", String(draft.days));
      draft.images.forEach((image) => body.append("images[]", image.file));

      try {
         const { data } = await api.post("admin/bot-import-text", body, { headers: { "Content-Type": "multipart/form-data" } });
         importResults.value.unshift({ url: label, ...data });
         removeDraft(draft);
      } catch (e: any) {
         const data = e?.response?.data ?? {};
         importResults.value.unshift({ url: label, status: data.status ?? "error", message: data.message || "Joylab bo'lmadi." });
         // Server rad etgan sabab shu e'lon ostida qizil yozuv bo'lib qoladi
         draft.error = data.message || "Joylab bo'lmadi.";
      } finally {
         importDone.value++;
      }
   }

   if (!drafts.value.length) drafts.value.push(newDraft());
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
// Qo'shilgan e'lonni admin panelning o'zida tahrirlash
const editId = ref<number | null>(null);

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
