<template>
   <section class="admin-page">
      <header class="admin-page-heading">
         <div>
            <h1>Avto-e'lonlar Boti</h1>
            <p class="admin-muted">Ochiq Telegram kanallardan e'lonlarni skrap qilib platformaga joylashni avtomatlashtirish.</p>
         </div>
         <div class="flex items-center gap-2">
            <BaseButton
               severity="secondary"
               size="sm"
               :disabled="runningBatch"
               @click="runBatchNow"
            >
               <template #icon>
                  <RefreshCw class="size-4" :class="{ 'animate-spin': runningBatch }" />
               </template>
               {{ runningBatch ? "Yuklanmoqda..." : "Hozir 1 sikl ishga tushirish" }}
            </BaseButton>
            <BaseButton
               size="sm"
               :disabled="saving"
               @click="saveSettings"
            >
               <template #icon><Save class="size-4" /></template>
               {{ saving ? "Saqlanmoqda..." : "Saqlash" }}
            </BaseButton>
         </div>
      </header>

      <!-- Alert messages -->
      <p v-if="message" class="admin-alert admin-alert-success">{{ message }}</p>
      <p v-if="error" class="admin-alert admin-alert-error">{{ error }}</p>

      <!-- Quick Metrics Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
         <!-- Status card -->
         <div class="admin-panel p-4 flex flex-col justify-between">
            <div class="flex items-center justify-between">
               <span class="text-xs font-semibold text-(--z-muted-text) uppercase tracking-wider">Bot Holati</span>
               <span
                  class="size-3 rounded-full"
                  :class="form.is_enabled ? 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]' : 'bg-rose-500'"
               />
            </div>
            <div class="mt-3 flex items-baseline justify-between">
               <span class="text-2xl font-bold" :class="form.is_enabled ? 'text-emerald-500' : 'text-rose-500'">
                  {{ form.is_enabled ? "FAOL (ON)" : "TO'XTATILGAN (OFF)" }}
               </span>
            </div>
            <p class="text-xs text-(--z-muted-text) mt-1">
               {{ form.is_enabled ? "Cron har soatda yangi e'lonlar qo'shadi" : "Avto-import to'xtatilgan" }}
            </p>
         </div>

         <!-- Today progress card -->
         <div class="admin-panel p-4 flex flex-col justify-between">
            <div class="flex items-center justify-between">
               <span class="text-xs font-semibold text-(--z-muted-text) uppercase tracking-wider">Bugungi Import</span>
               <Calendar class="size-4 text-(--z-muted-text)" />
            </div>
            <div class="mt-3 flex items-baseline gap-1">
               <span class="text-2xl font-bold text-(--z-foreground)">{{ stats.today_imported_count }}</span>
               <span class="text-sm font-medium text-(--z-muted-text)">/ {{ form.daily_limit }} ta</span>
            </div>
            <!-- Progress bar -->
            <div class="w-full bg-(--z-muted) h-1.5 rounded-full mt-2 overflow-hidden">
               <div
                  class="bg-(--z-primary) h-full rounded-full transition-all duration-500"
                  :style="{ width: `${Math.min(100, Math.round((stats.today_imported_count / (form.daily_limit || 1)) * 100))}%` }"
               />
            </div>
         </div>

         <!-- Total Bot Ads -->
         <div class="admin-panel p-4 flex flex-col justify-between">
            <div class="flex items-center justify-between">
               <span class="text-xs font-semibold text-(--z-muted-text) uppercase tracking-wider">Jami Bot E'lonlari</span>
               <Bot class="size-4 text-(--z-muted-text)" />
            </div>
            <div class="mt-3 flex items-baseline gap-1">
               <span class="text-2xl font-bold text-(--z-foreground)">{{ stats.total_bot_products }}</span>
               <span class="text-xs text-(--z-muted-text)">(odamlar: {{ stats.total_user_products }})</span>
            </div>
            <p class="text-xs text-(--z-muted-text) mt-1">Platformadagi barcha bot e'lonlari</p>
         </div>

         <!-- Last Run -->
         <div class="admin-panel p-4 flex flex-col justify-between">
            <div class="flex items-center justify-between">
               <span class="text-xs font-semibold text-(--z-muted-text) uppercase tracking-wider">Oxirgi Ishga Tushish</span>
               <Clock class="size-4 text-(--z-muted-text)" />
            </div>
            <div class="mt-3">
               <span class="text-lg font-semibold text-(--z-foreground)">{{ stats.last_run_at }}</span>
            </div>
            <p class="text-xs text-(--z-muted-text) mt-1">Avtomatik Cron orqali tekshirildi</p>
         </div>
      </div>

      <!-- Main Form Settings -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
         <!-- Left Column (Main Settings) -->
         <div class="lg:col-span-2 flex flex-col gap-6">
            <!-- 1. Bot Activation & Limits -->
            <div class="admin-panel p-5 flex flex-col gap-5">
               <div class="flex items-center justify-between pb-3 border-b border-(--z-border)">
                  <div>
                     <h3 class="font-bold text-base text-(--z-foreground)">Asosiy Sozlamalar</h3>
                     <p class="text-xs text-(--z-muted-text)">Botni yoqish va import me'yorlarini belgilang</p>
                  </div>
                  <!-- Big Toggle Switch -->
                  <label class="relative inline-flex items-center cursor-pointer">
                     <input v-model="form.is_enabled" type="checkbox" class="sr-only peer" />
                     <div class="w-13 h-7 bg-(--z-muted) peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[3px] after:left-[4px] after:bg-white after:rounded-full after:h-5.5 after:w-5.5 after:transition-all peer-checked:bg-emerald-500"></div>
                  </label>
               </div>

               <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                     <label class="block text-xs font-semibold text-(--z-foreground) mb-1.5">
                        Har bir siklda import soni (soatiga)
                     </label>
                     <input
                        v-model.number="form.hourly_limit"
                        type="number"
                        min="1"
                        max="50"
                        class="admin-input w-full"
                        placeholder="Masalan: 7"
                     />
                     <p class="text-[11px] text-(--z-muted-text) mt-1">
                        Cron har 1 soatda ishlaganda nechta e'lon qo'shilishi (tavsiya: 5–10 ta).
                     </p>
                  </div>

                  <div>
                     <label class="block text-xs font-semibold text-(--z-foreground) mb-1.5">
                        Kunlik maksimal chegara (limit)
                     </label>
                     <input
                        v-model.number="form.daily_limit"
                        type="number"
                        min="10"
                        max="1000"
                        class="admin-input w-full"
                        placeholder="Masalan: 150"
                     />
                     <p class="text-[11px] text-(--z-muted-text) mt-1">
                        Kuniga shu songa yetgach bot to'xtaydi va ertaga yana davom etadi.
                     </p>
                  </div>
               </div>
            </div>

            <!-- 2. Source Channels -->
            <div class="admin-panel p-5 flex flex-col gap-4">
               <div class="pb-3 border-b border-(--z-border)">
                  <h3 class="font-bold text-base text-(--z-foreground)">Manba Telegram Kanallar</h3>
                  <p class="text-xs text-(--z-muted-text)">E'lonlar skrap qilib olinadigan ochiq kanallar ro'yxati</p>
               </div>

               <div>
                  <label class="block text-xs font-semibold text-(--z-foreground) mb-1.5">
                     Kanal username'lari (har bir qatorda bittadan)
                  </label>
                  <textarea
                     v-model="form.channels"
                     rows="5"
                     class="admin-input w-full font-mono text-xs"
                     placeholder="@navoiy_bozor&#10;@karmana_bozor&#10;@zar_bozor"
                  ></textarea>
                  <p class="text-[11px] text-(--z-muted-text) mt-1">
                     Kanal ommaviy (public) bo'lishi shart. Username'ni <code>@kanal</code> yoki <code>t.me/kanal</code> ko'rinishida kiritish mumkin.
                  </p>
               </div>
            </div>

            <!-- 3. Telegram Channel Publishing (Kanalga chiqarish va Tungi rejim) -->
            <div class="admin-panel p-5 flex flex-col gap-4">
               <div class="flex items-center justify-between pb-3 border-b border-(--z-border)">
                  <div>
                     <h3 class="font-bold text-base text-(--z-foreground)">O'zimizning Telegram Kanalga Joylash</h3>
                     <p class="text-xs text-(--z-muted-text)">Bot qo'ygan e'lonlarning eng sarasi kanalga chiqishi</p>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer">
                     <input v-model="form.telegram_post_enabled" type="checkbox" class="sr-only peer" />
                     <div class="w-11 h-6 bg-(--z-muted) peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-(--z-primary)"></div>
                  </label>
               </div>

               <div v-if="form.telegram_post_enabled" class="flex flex-col gap-4">
                  <div class="p-3 rounded-lg bg-(--z-muted)/40 border border-(--z-border) text-xs text-(--z-muted-text) leading-relaxed">
                     💡 <strong>Qoida:</strong> Kanalga har bir soatlik siklda <strong>faqat 1 ta</strong> eng sifatli (rasmi va narxi bor) e'lon yuboriladi. Haqiqiy foydalanuvchilar e'loni esa navbatsiz, doim darhol kanalga boradi.
                  </div>

                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                     <div>
                        <label class="block text-xs font-semibold text-(--z-foreground) mb-1.5">
                           Kunduzgi faoliyat: Boshlanish soati
                        </label>
                        <input
                           v-model.number="form.telegram_start_hour"
                           type="number"
                           min="0"
                           max="23"
                           class="admin-input w-full"
                           placeholder="8"
                        />
                        <p class="text-[11px] text-(--z-muted-text) mt-1">
                           Ertalab soat nechidan kanalga yuborish boshlansin (masalan: 8).
                        </p>
                     </div>

                     <div>
                        <label class="block text-xs font-semibold text-(--z-foreground) mb-1.5">
                           Tungi uxlash rejimi: Tugash soati
                        </label>
                        <input
                           v-model.number="form.telegram_end_hour"
                           type="number"
                           min="0"
                           max="23"
                           class="admin-input w-full"
                           placeholder="23"
                        />
                        <p class="text-[11px] text-(--z-muted-text) mt-1">
                           Kechqurun soat nechidan keyin kanalga post yuborish to'xtasin (masalan: 23).
                        </p>
                     </div>
                  </div>
               </div>
            </div>
         </div>

         <!-- Right Column (AI & System Info) -->
         <div class="flex flex-col gap-6">
            <!-- AI Configuration -->
            <div class="admin-panel p-5 flex flex-col gap-4">
               <div class="pb-3 border-b border-(--z-border)">
                  <h3 class="font-bold text-base text-(--z-foreground)">AI Tahlil Moduli</h3>
                  <p class="text-xs text-(--z-muted-text)">Matndan narx, telefon va kategoriyani ajratish</p>
               </div>

               <div>
                  <label class="block text-xs font-semibold text-(--z-foreground) mb-1.5">
                     AI Provayder
                  </label>
                  <FieldSelect
                     v-model="form.ai_provider"
                     :options="[
                        { label: 'Google Gemini 1.5 Flash (Bepul / Tavsiya)', value: 'gemini' },
                        { label: 'OpenAI GPT-4o-mini', value: 'openai' },
                        { label: 'Qoidali Regex Parser (AI siz, bepul)', value: 'regex' },
                     ]"
                  />
               </div>

               <div v-if="form.ai_provider !== 'regex'">
                  <label class="block text-xs font-semibold text-(--z-foreground) mb-1.5">
                     API Kalit (API Key)
                  </label>
                  <input
                     v-model="form.ai_api_key"
                     type="password"
                     class="admin-input w-full font-mono text-xs"
                     placeholder="AI_API_KEY..."
                  />
                  <p class="text-[11px] text-(--z-muted-text) mt-1">
                     Kiritmasangiz, <code>.env</code> faylidagi <code>GEMINI_API_KEY</code> yoki <code>OPENAI_API_KEY</code> ishlatiladi.
                  </p>
               </div>

               <div class="p-3 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-xs text-emerald-600 dark:text-emerald-400">
                  ✨ <strong>Google Gemini Flash:</strong> Kuniga 1500 tagacha so'rov mutlaqo bepul taqdim etiladi.
               </div>
            </div>

            <!-- How it works card -->
            <div class="admin-panel p-5 flex flex-col gap-3">
               <h3 class="font-bold text-base text-(--z-foreground)">Qanday ishlaydi?</h3>
               <ul class="text-xs text-(--z-muted-text) flex flex-col gap-2 list-disc list-inside leading-relaxed">
                  <li><strong>Cron:</strong> Serverdagi cron har 1 soatda avtomatik ushbu botni ishga tushiradi.</li>
                  <li><strong>Filtr:</strong> Telefon raqami yoki Telegram kontakti bo'lmagan xabarlar tashlab yuboriladi.</li>
                  <li><strong>Dublikat:</strong> Bir xil e'lonlar xesh orqali tekshirilib, qayta qo'yilmaydi.</li>
                  <li><strong>Suratlar:</strong> Rasmlar avtomatik yuklanib, ixcham WebP formatida saqlanadi.</li>
               </ul>
            </div>
         </div>
      </div>
   </section>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { Bot, Calendar, Clock, RefreshCw, Save } from "lucide-vue-next";
import BaseButton from "@shared/ui/BaseButton.vue";
import FieldSelect from "@shared/ui/FieldSelect.vue";
import { api } from "@shared/composables/useFetch";

interface IBotStats {
   total_bot_products: number;
   total_user_products: number;
   today_imported_count: number;
   daily_limit: number;
   hourly_limit: number;
   last_run_at: string;
}

interface IBotSetting {
   is_enabled: boolean;
   hourly_limit: number;
   daily_limit: number;
   channels: string;
   telegram_post_enabled: boolean;
   telegram_start_hour: number;
   telegram_end_hour: number;
   ai_provider: string;
   ai_api_key: string | null;
}

const form = ref<IBotSetting>({
   is_enabled: false,
   hourly_limit: 7,
   daily_limit: 150,
   channels: "@navoiy_bozor\n@karmana_bozor",
   telegram_post_enabled: true,
   telegram_start_hour: 8,
   telegram_end_hour: 23,
   ai_provider: "gemini",
   ai_api_key: "",
});

const stats = ref<IBotStats>({
   total_bot_products: 0,
   total_user_products: 0,
   today_imported_count: 0,
   daily_limit: 150,
   hourly_limit: 7,
   last_run_at: "—",
});

const saving = ref(false);
const runningBatch = ref(false);
const message = ref<string | null>(null);
const error = ref<string | null>(null);

async function loadSettings() {
   try {
      const response = await api.get("admin/bot-settings");
      if (response.data) {
         if (response.data.setting) {
            form.value = {
               ...form.value,
               ...response.data.setting,
               channels: response.data.setting.channels || "",
               ai_api_key: response.data.setting.ai_api_key || "",
            };
         }
         if (response.data.stats) {
            stats.value = response.data.stats;
         }
      }
   } catch (e: any) {
      error.value = e?.response?.data?.message || "Bot sozlamalarini yuklashda xatolik.";
   }
}

async function saveSettings() {
   saving.value = true;
   message.value = null;
   error.value = null;

   try {
      const response = await api.post("admin/bot-settings", form.value);
      message.value = response.data?.message || "Sozlamalar saqlandi.";
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
      const response = await api.post("admin/bot-settings/run-now");
      message.value = response.data?.message || "Sikl muvaffaqiyatli bajarildi!";
      await loadSettings();
   } catch (e: any) {
      error.value = e?.response?.data?.message || "Siklni ishga tushirishda xatolik.";
   } finally {
      runningBatch.value = false;
   }
}

onMounted(() => {
   loadSettings();
});
</script>

<style scoped>
.admin-input {
   border-radius: var(--z-rounded);
   border: 1px solid var(--z-border);
   background-color: var(--z-field-background);
   color: var(--z-foreground);
   padding: 0.5rem 0.75rem;
   font-size: 0.875rem;
   transition: all 0.15s ease;
}
.admin-input:focus {
   outline: none;
   border-color: var(--z-primary);
   box-shadow: 0 0 0 2px color-mix(in srgb, var(--z-primary) 20%, transparent);
}
</style>
