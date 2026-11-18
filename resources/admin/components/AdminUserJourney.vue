<template>
   <!-- Foydalanuvchining ilovadagi yo'li: qaysi sahifaga kirdi, nimani ko'rdi, qayerda to'xtab qoldi -->
   <BaseDrawer :open="userId !== null" title="Ilovadagi yo'li" :description="description" @close="emit('close')">
      <div v-if="loading && !data" class="admin-loading">Yuklanmoqda…</div>
      <p v-else-if="error" class="admin-alert admin-alert-error">{{ error }}</p>

      <template v-else-if="data">
         <!-- Kirgan kunlari (xom yozuvlar oxirgi N kun saqlanadi) -->
         <nav v-if="dayOptions.length > 1" class="admin-filter-chips -mx-4 px-4 mb-3" aria-label="Kunlar">
            <button
               v-for="day in dayOptions"
               :key="day"
               type="button"
               class="admin-filter-chip pr-3!"
               :class="{ 'is-active': data.date === day }"
               @click="load(day)"
            >
               {{ dayLabel(day) }}
            </button>
         </nav>

         <div v-if="summary.length" class="journey-summary">
            <span v-for="item in summary" :key="item.label" :class="`is-${item.tone}`">
               <b>{{ item.value }}</b> {{ item.label }}
            </span>
         </div>

         <ol v-if="data.events.length" class="journey-list transition-opacity" :class="{ 'opacity-60': loading }">
            <li v-for="(event, index) in rows" :key="index" :class="`is-${event.tone}`">
               <time>{{ event.time }}</time>
               <span class="journey-dot" />
               <div class="min-w-0">
                  <p class="journey-title">
                     {{ event.title }}
                     <span v-if="event.repeat > 1" class="journey-repeat">×{{ event.repeat }}</span>
                  </p>
                  <p v-if="event.detail" class="journey-detail">
                     <a v-if="event.productId" :href="`/product/${event.productId}`" target="_blank" rel="noopener">{{ event.detail }}</a>
                     <template v-else>{{ event.detail }}</template>
                  </p>
               </div>
            </li>
         </ol>
         <p v-else class="admin-muted text-sm py-6 text-center">
            Bu kunda yozuv yo'q. Yo'l oxirgi {{ data.raw_days }} kun saqlanadi.
         </p>
      </template>
   </BaseDrawer>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import BaseDrawer from "@shared/ui/BaseDrawer.vue";
import AdminRepo, { type JourneyResponse } from "@admin/entities/AdminRepo";
import { describeEvent, type JourneyTone } from "@admin/modules/activityLabels";

const props = defineProps<{ userId: number | null; date?: string | null }>();
const emit = defineEmits<{ (e: "close"): void }>();

const data = ref<JourneyResponse | null>(null);
const loading = ref(false);
const error = ref("");

const description = computed(() => {
   const user = data.value?.user;
   if (!user) return "";
   return [user.name, user.username ? `@${user.username}` : "", user.district ?? "shahar tanlanmagan"].filter(Boolean).join(" · ");
});

/** Bugun ham doim tanlanadigan bo'lsin (hali yig'ilmagan bo'lsa ham) */
const dayOptions = computed(() => {
   const days = new Set(data.value?.dates ?? []);
   if (data.value?.date) days.add(data.value.date);
   return [...days].sort().reverse();
});

const timeFormat = new Intl.DateTimeFormat("ru-RU", { hour: "2-digit", minute: "2-digit" });

type Row = { time: string; title: string; detail: string; tone: JourneyTone; productId: number | null; repeat: number };

/** Ketma-ket bir xil hodisalar (masalan bosh sahifaga 5 marta qaytdi) bitta qatorga yig'iladi */
const rows = computed<Row[]>(() => {
   const result: Row[] = [];
   for (const event of data.value?.events ?? []) {
      const { title, detail, tone } = describeEvent(event);
      const last = result[result.length - 1];
      if (last && last.title === title && last.detail === detail) {
         last.repeat++;
         continue;
      }
      const isProduct = ["product_view", "ad_edited", "ad_activated", "ad_deactivated", "report", "contact_phone", "contact_telegram"].includes(event.e);
      result.push({
         time: event.t ? timeFormat.format(new Date(event.t)) : "—",
         title,
         detail,
         tone,
         productId: isProduct && /^\d+$/.test(event.r ?? "") ? Number(event.r) : null,
         repeat: 1,
      });
   }
   return result;
});

const summary = computed(() => {
   const events = data.value?.events ?? [];
   const count = (...names: string[]) => events.filter((event) => names.includes(event.e)).length;
   const items: { label: string; value: number; tone: JourneyTone }[] = [
      { label: "ochdi", value: count("app_open"), tone: "muted" },
      { label: "e'lon ko'rdi", value: count("product_view"), tone: "default" },
      { label: "murojaat", value: count("contact_phone", "contact_telegram"), tone: "success" },
      { label: "joyladi", value: count("ad_created"), tone: "success" },
      { label: "xato", value: count("ad_failed", "ai_draft_failed", "client_error"), tone: "danger" },
   ];
   return items.filter((item) => item.value > 0);
});

function dayLabel(date: string) {
   const today = new Date().toISOString().slice(0, 10);
   if (date === today) return "Bugun";
   return new Date(date).toLocaleDateString("ru-RU", { day: "2-digit", month: "2-digit" });
}

async function load(date?: string | null) {
   if (props.userId === null) return;
   loading.value = true;
   error.value = "";
   try {
      const response = await AdminRepo.userJourney(props.userId, date || undefined);
      data.value = response.data;
   } catch (exception) {
      console.error("Foydalanuvchi yo'li yuklanmadi.", exception);
      error.value = "Ma'lumotni yuklab bo'lmadi.";
   } finally {
      loading.value = false;
   }
}

watch(
   () => props.userId,
   (id) => {
      data.value = null;
      if (id !== null) load(props.date);
   },
);
</script>

<style scoped>
.journey-summary {
   display: flex;
   flex-wrap: wrap;
   gap: 0.375rem;
   margin-bottom: 1rem;
   font-size: 0.75rem;
   color: var(--z-muted-text);

   span {
      border: 1px solid var(--z-border);
      border-radius: 999px;
      padding: 0.2rem 0.6rem;
   }

   b {
      color: var(--z-foreground);
      font-variant-numeric: tabular-nums;
   }

   .is-danger b {
      color: var(--z-danger);
   }
}

.journey-list {
   display: grid;
   gap: 0;

   li {
      position: relative;
      display: grid;
      grid-template-columns: 2.75rem 0.75rem minmax(0, 1fr);
      gap: 0.5rem;
      padding-bottom: 0.875rem;
   }

   /* Vertikal chiziq nuqtalarni bog'laydi */
   li:not(:last-child)::after {
      content: "";
      position: absolute;
      top: 0.9rem;
      bottom: 0;
      left: calc(2.75rem + 0.5rem + 0.375rem - 0.5px);
      width: 1px;
      background: var(--z-border);
   }

   time {
      padding-top: 0.05rem;
      font-size: 0.75rem;
      color: var(--z-muted-text);
      font-variant-numeric: tabular-nums;
      text-align: right;
   }
}

.journey-dot {
   margin-top: 0.3rem;
   width: 0.5rem;
   height: 0.5rem;
   justify-self: center;
   border-radius: 999px;
   background: var(--z-muted-text);
   opacity: 0.5;

   .is-success & {
      background: rgb(16 185 129);
      opacity: 0.8;
   }

   .is-danger & {
      background: var(--z-danger);
      opacity: 0.9;
   }
}

.journey-title {
   font-size: 0.8125rem;
   font-weight: 500;
   line-height: 1.3;

   .is-muted & {
      color: var(--z-muted-text);
      font-weight: 400;
   }

   .is-danger & {
      color: var(--z-danger);
   }
}

.journey-repeat {
   margin-left: 0.25rem;
   font-size: 0.6875rem;
   font-weight: 600;
   color: var(--z-muted-text);
}

.journey-detail {
   margin-top: 0.125rem;
   font-size: 0.75rem;
   color: var(--z-muted-text);
   overflow-wrap: anywhere;

   a {
      color: inherit;
      text-decoration: underline;
      text-decoration-color: var(--z-border);
      text-underline-offset: 2px;
   }
}
</style>
