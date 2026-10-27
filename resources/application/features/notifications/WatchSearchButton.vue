<template>
   <!--
      Qidiruvni kuzatish: mos yangi e'lon chiqsa bot xabar yuboradi.
      compact — natijalar ustidagi kichik tugma; cta — "topilmadi" holatida asosiy taklif.
   -->
   <div :class="variant === 'cta' ? 'flex flex-col items-center gap-2' : 'contents'">
      <BaseButton
         v-if="variant === 'cta'"
         :severity="saved ? 'secondary' : 'primary'"
         size="sm"
         :loading="busy"
         @mousedown.prevent
         @click="toggle"
      >
         <template #icon><component :is="saved ? BellRing : Bell" class="size-4" /></template>
         {{ saved ? "Kuzatilmoqda" : "Paydo bo'lsa xabar berish" }}
      </BaseButton>

      <button
         v-else
         type="button"
         class="watch-pill"
         :class="{ 'is-on': saved }"
         :disabled="busy"
         @mousedown.prevent
         @click="toggle"
      >
         <component :is="saved ? BellRing : Bell" class="size-3.5" />
         {{ saved ? "Kuzatilmoqda" : "Kuzatish" }}
      </button>

      <p v-if="hint" class="text-xs text-(--z-muted-text)" :class="variant === 'cta' ? 'text-center max-w-64' : 'basis-full'">
         {{ hint }}
         <a v-if="botLink" :href="botLink" target="_blank" rel="noopener" class="font-semibold text-(--z-foreground) underline">Botni ochish</a>
      </p>
   </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { Bell, BellRing } from "lucide-vue-next";
import { isTMA } from "@tma.js/bridge";
import BaseButton from "@shared/ui/BaseButton.vue";
import { useAuth } from "@shared/store/useAuth";
import { requireAuth } from "@/features/auth/requireAuth";
import { useBotAccess } from "@/features/notifications/useBotAccess";
import { useSavedSearches } from "@/features/notifications/useSavedSearches";
import type { ListFilters } from "@/features/filters/listFilters";

const props = withDefaults(defineProps<{ query: string; filters: ListFilters; variant?: "compact" | "cta" }>(), {
   variant: "compact",
});

const authStore = useAuth();
const savedSearches = useSavedSearches();
const { ensureWriteAccess } = useBotAccess();

const busy = ref(false);
const hint = ref("");
const botLink = ref<string | null>(null);

const saved = computed(() => (savedSearches.items.value ? savedSearches.find(props.query, props.filters) : null));

async function toggle() {
   if (!requireAuth()) return;
   busy.value = true;
   hint.value = "";
   botLink.value = null;

   try {
      await savedSearches.load();
      if (saved.value) {
         await savedSearches.remove(saved.value.id);
         return;
      }

      // Avval ruxsat: bot yoza olmasa kuzatuvdan foyda yo'q
      const allowed = await ensureWriteAccess();
      if (!allowed && isTMA()) {
         hint.value = "Xabar yuborishimiz uchun botga ruxsat bering.";
         return;
      }

      await savedSearches.add(props.query, props.filters);
      hint.value = "Mos e'lon chiqsa, bot xabar yuboradi.";
      if (!allowed) {
         // Brauzerda: ruxsatni faqat botning o'zida /start bilan berish mumkin
         hint.value = "Saqlandi. Xabar olish uchun botni ishga tushiring.";
         botLink.value = (import.meta.env.VITE_TELEGRAM_APP_URL || "https://t.me/NavoiyElonBot").replace(/\?.*$/, "") + "?start=notify";
      }
   } catch (error: any) {
      hint.value = error?.response?.data?.message || "Saqlab bo'lmadi. Qayta urinib ko'ring.";
   } finally {
      busy.value = false;
   }
}

onMounted(() => {
   if (authStore.user) savedSearches.load().catch(() => {});
});
</script>

<style scoped>
.watch-pill {
   display: inline-flex;
   height: 1.75rem;
   align-items: center;
   gap: 0.375rem;
   border: 1px solid var(--z-glass-border);
   border-radius: 999px;
   background: var(--z-card);
   box-shadow: var(--z-glass-edge, none);
   padding: 0 0.75rem;
   color: var(--z-foreground);
   font-size: 0.75rem;
   font-weight: 600;
   cursor: pointer;
   transition: transform var(--duration) var(--ease-soft);

   &:active {
      transform: scale(0.95);
   }

   &.is-on {
      border-color: transparent;
      background: var(--z-primary);
      box-shadow: none;
      color: var(--z-primary-foreground);
   }

   &:disabled {
      opacity: 0.6;
   }
}
</style>
