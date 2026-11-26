<template>
   <section class="admin-page">
      <AdminMessageDialog
         :open="messageTarget !== null"
         :recipient="messageTarget"
         @close="messageTarget = null"
         @sent="(text) => (notice = text)"
      />
      <AdminUserJourney :user-id="journeyUserId" @close="journeyUserId = null" />

      <BaseModal
         :open="confirmState !== null"
         :title="confirmState?.title"
         :description="confirmState?.description"
         :confirm-text="confirmState?.confirmText"
         cancel-text="Bekor qilish"
         :danger="confirmState?.danger"
         @close="confirmState = null"
         @confirm="runConfirm"
      >
         <template #icon>
            <component :is="confirmState?.danger ? Ban : ShieldCheck" class="size-5" :class="{ 'text-(--z-danger)': confirmState?.danger }" />
         </template>
         <p class="text-sm text-(--z-muted-text)">{{ confirmState?.note }}</p>
         <label v-if="confirmState?.withArchive" class="mt-3 flex items-center gap-2.5 text-sm cursor-pointer select-none">
            <input v-model="archiveProducts" type="checkbox" class="size-4 accent-(--z-danger)" />
            Hozirgi e'lonlarini ham arxivlash
         </label>
      </BaseModal>

      <header class="admin-page-heading">
         <div>
            <h1>Foydalanuvchilar</h1>
            <p class="admin-muted">Qatorni bosing — ma'lumotlar, e'lonlari, rol va bloklash.</p>
         </div>
      </header>

      <p v-if="error" class="admin-alert admin-alert-error">{{ error }}</p>
      <p v-if="notice" class="admin-alert admin-alert-success">{{ notice }}</p>

      <!-- Qaytib kirish: kunlar bo'yicha (bir kunda necha marta ochgani hisobga olinmaydi). Kartani bosish — filtr -->
      <div v-if="summary" class="admin-stats-grid">
         <button
            type="button"
            class="admin-stat-card"
            :class="{ 'is-active': visitsFilter === 'once' }"
            @click="setVisits('once')"
         >
            <span class="admin-stat-label">Bir marta kirib, qaytmagan</span>
            <strong>{{ num(summary.once) }}</strong>
            <span class="admin-stat-detail">{{ percent(summary.once) }} · faqat bitta kun kirgan</span>
         </button>
         <button
            type="button"
            class="admin-stat-card"
            :class="{ 'is-active': visitsFilter === 'returning' }"
            @click="setVisits('returning')"
         >
            <span class="admin-stat-label">Qayta-qayta kirayotgan</span>
            <strong>{{ num(summary.returning) }}</strong>
            <span class="admin-stat-detail">{{ percent(summary.returning) }} · 2+ kun · {{ num(summary.regular) }} tasi 5+ kun</span>
         </button>
         <article class="admin-stat-card">
            <span class="admin-stat-label">Shu hafta qaytganlar</span>
            <strong>{{ num(summary.returned_week) }}</strong>
            <span class="admin-stat-detail">oxirgi 7 kunda kirgan eski foydalanuvchilar</span>
         </article>
      </div>

      <!-- Kanal va bot obunasi: kartani bosish — shu guruh bo'yicha filtr -->
      <section class="admin-panel admin-subs">
         <header class="admin-subs-head">
            <div class="min-w-0">
               <h2>Kanal va bot obunasi</h2>
               <p v-if="channels.length">
                  {{ channels.map((channel) => channel.title).join(", ") }}
                  <span v-if="subscriptionCounts.unchecked"> · {{ num(subscriptionCounts.unchecked) }} nafari hali tekshirilmagan</span>
               </p>
            </div>
            <button v-if="subscription" type="button" class="admin-text-link shrink-0" @click="setSubscription(subscription)">
               Filtrni olish
            </button>
         </header>
         <div class="admin-subs-grid">
            <button
               v-for="card in subscriptionCards"
               :key="card.value"
               type="button"
               class="admin-subs-card"
               :class="[`is-${card.value}`, { 'is-active': subscription === card.value }]"
               :aria-pressed="subscription === card.value"
               @click="setSubscription(card.value)"
            >
               <span class="admin-subs-icons" aria-hidden="true">
                  <Megaphone class="size-3.5" :class="{ 'is-off': !card.channel }" />
                  <Bot class="size-3.5" :class="{ 'is-off': !card.bot }" />
               </span>
               <strong>{{ num(subscriptionCounts[card.value] ?? 0) }}</strong>
               <span class="admin-subs-label">{{ card.label }}</span>
            </button>
         </div>
      </section>

      <section class="admin-panel admin-table-panel">
         <div class="admin-toolbar">
            <label class="admin-search">
               <Search class="size-4" aria-hidden="true" />
               <input
                  v-model="searchInput"
                  type="search"
                  placeholder="Ism, username, Telegram ID yoki telefon"
                  @keyup.enter="load(1)"
               />
            </label>
            <div class="sm:w-40">
               <FieldSelect
                  v-model="roleFilter"
                  size="sm"
                  :options="[
                     { label: 'Barcha rollar', value: 'all' },
                     { label: 'Oddiy foydalanuvchi', value: 'user' },
                     { label: 'Adminlar', value: 'admin' },
                     { label: 'Bloklanganlar', value: 'blocked' },
                  ]"
                  @change="load(1)"
               />
            </div>
            <div class="sm:w-44">
               <FieldSelect
                  v-model="sort"
                  size="sm"
                  :options="[
                     { label: 'Yangi qo\'shilganlar', value: 'new' },
                     { label: 'Ko\'p kirganlar', value: 'visits' },
                     { label: 'Oxirgi faollar', value: 'active' },
                  ]"
                  @change="load(1)"
               />
            </div>
            <BaseButton severity="secondary" size="sm" @click="load(1)">
               <template #icon><Search class="size-3.5" /></template>
               Qidirish
            </BaseButton>
         </div>

         <!-- Bo'limlar: bugun kirganlar, e'lon qo'yganlar, faqat shahar tanlaganlar, hech narsa qilmaganlar -->
         <nav ref="segmentNav" class="admin-filter-chips" aria-label="Bo'limlar">
            <button
               v-for="chip in segmentChips"
               :key="chip.value"
               type="button"
               class="admin-filter-chip"
               :class="{ 'is-active': segment === chip.value }"
               :title="chip.hint"
               @click="setSegment(chip.value)"
            >
               {{ chip.label }}
               <span>{{ segmentCounts[chip.value || "all"] ?? "·" }}</span>
            </button>
         </nav>

         <div v-if="dateFilter" class="admin-filter-note">
            <button type="button" class="adm-filter-tag" @click="clearDateFilter">
               {{ relativeLabel(dateFilter) }} qo'shilganlar
               <X class="size-3.5" aria-label="Olib tashlash" />
            </button>
         </div>

         <div v-if="visitsFilter" class="admin-filter-note">
            <span class="min-w-0 truncate">
               Ko'rsatilmoqda: <b>{{ visitsFilter === "once" ? "bir marta kirganlar" : "qayta-qayta kirayotganlar" }}</b>
            </span>
            <BaseButton size="xs" severity="secondary" @click="setVisits(visitsFilter)">
               <template #icon><X class="size-3.5" /></template>
               Olib tashlash
            </BaseButton>
         </div>

         <div v-if="loading && !users.length" class="admin-loading">Foydalanuvchilar yuklanmoqda…</div>
         <ul v-else class="adm-list transition-opacity" :class="{ 'opacity-60': loading }">
            <li v-for="user in users" :key="user.id">
               <button type="button" class="adm-item" :class="{ 'is-muted': !!user.blocked_at }" @click="openId = user.id">
                  <span class="adm-item-main">
                     <span class="adm-item-title">
                        <span :title="user.name">{{ user.name }}</span>
                        <span v-if="user.role === 'admin'" class="admin-badge admin-badge-info">Admin</span>
                        <span v-if="user.blocked_at" class="admin-badge admin-badge-danger">Bloklangan</span>
                     </span>
                     <span class="adm-item-meta">
                        <span v-if="user.username">@{{ user.username }}</span>
                        <span v-if="user.active_district">{{ user.active_district.name }}</span>
                        <span v-if="user.products_count"><b>{{ user.active_products_count ?? 0 }}</b>/{{ user.products_count }} e'lon</span>
                     </span>
                     <!-- Kanal / bot: yashil — a'zo, chizilgan — yo'q, "?" — hali tekshirilmagan -->
                     <span class="admin-sub-pills">
                        <span class="admin-sub-pill" :class="channelState(user)" :title="channelTitle(user)">
                           <Megaphone class="size-3" aria-hidden="true" />
                           Kanal{{ channelState(user) === "is-unknown" ? " ?" : "" }}
                        </span>
                        <span class="admin-sub-pill" :class="user.bot_can_message ? 'is-on' : 'is-off'">
                           <Bot class="size-3" aria-hidden="true" />
                           Bot
                        </span>
                     </span>
                  </span>
                  <span class="adm-item-side">
                     <span class="inline-flex items-center gap-2">
                        <i v-if="isRecentlyActive(user.last_active_at)" class="adm-dot is-active" title="Oxirgi 24 soatda faol" />
                        {{ user.last_active_at ? timeAgo(user.last_active_at) : "Kirmagan" }}
                     </span>
                     <span>{{ visitLabel(user.visit_days) }}</span>
                  </span>
               </button>
            </li>
            <li v-if="users.length === 0" class="admin-empty-cell">Foydalanuvchi topilmadi.</li>
         </ul>

         <!-- Tafsilot: bosilgan foydalanuvchi oynada -->
         <AdminSheet
            :open="openUser !== null"
            :title="openUser?.name ?? ''"
            :subtitle="openUser ? [openUser.username ? `@${openUser.username}` : '', openUser.role === 'admin' ? 'Admin' : ''].filter(Boolean).join(' · ') : ''"
            @close="openId = null"
         >
            <template v-if="openUser">
               <p v-if="error" class="admin-alert admin-alert-error">{{ error }}</p>
               <p v-else-if="notice" class="admin-alert admin-alert-success">{{ notice }}</p>

               <dl class="admin-dl">
                  <div>
                     <dt>Botga a'zo</dt>
                     <dd>{{ openUser.bot_can_message ? "Ha — bot yoza oladi" : "Yo'q" }}</dd>
                  </div>
                  <div>
                     <dt>Kanalga a'zo</dt>
                     <dd>
                        <template v-if="!openUser.channels_checked_at && !openUser.channel_ids?.length">Hali tekshirilmagan</template>
                        <template v-else-if="openUser.channel_ids?.length">{{ channelNames(openUser) }}</template>
                        <template v-else>Yo'q</template>
                        <span v-if="openUser.channels_checked_at" class="block text-xs font-normal text-(--z-muted-text)">
                           tekshirildi {{ timeAgo(openUser.channels_checked_at) }}
                        </span>
                     </dd>
                  </div>
                  <div>
                     <dt>Shahar</dt>
                     <dd>{{ openUser.active_district?.name || "Tanlanmagan" }}</dd>
                  </div>
                  <div v-if="openUser.phone">
                     <dt>Telefon</dt>
                     <dd><a :href="`tel:${openUser.phone}`">{{ openUser.phone }}</a></dd>
                  </div>
                  <div>
                     <dt>E'lonlar (faol / jami)</dt>
                     <dd class="tabular-nums">{{ openUser.active_products_count ?? 0 }} / {{ openUser.products_count }}</dd>
                  </div>
                  <div>
                     <dt>Telegram ID</dt>
                     <dd class="tabular-nums">{{ openUser.telegram_user_id || "—" }}</dd>
                  </div>
                  <div>
                     <dt>Ro'yxatdan o'tgan</dt>
                     <dd>{{ formatDateTime(openUser.created_at) }}</dd>
                  </div>
                  <div>
                     <dt>Oxirgi faollik</dt>
                     <dd>{{ openUser.last_active_at ? formatDateTime(openUser.last_active_at) : "Kirmagan" }}</dd>
                  </div>
                  <div>
                     <dt>Ilovaga kirgan</dt>
                     <dd>
                        {{ visitLabel(openUser.visit_days) }}
                        <span v-if="openUser.last_visit_on" class="block text-xs font-normal text-(--z-muted-text)">
                           oxirgisi {{ formatDate(openUser.last_visit_on) }}
                        </span>
                     </dd>
                  </div>
                  <div v-if="openUser.blocked_at">
                     <dt>Bloklangan</dt>
                     <dd>{{ formatDateTime(openUser.blocked_at) }}</dd>
                  </div>
               </dl>
            </template>

            <template v-if="openUser" #actions>
               <BaseButton size="sm" severity="secondary" @click="journeyUserId = openUser.id">
                  <template #icon><Route class="size-3.5" /></template>
                  Ilovadagi yo'li
               </BaseButton>
               <BaseButton v-if="openUser.products_count" size="sm" severity="secondary" @click="openProducts(openUser)">
                  <template #icon><FileText class="size-3.5" /></template>
                  E'lonlari
               </BaseButton>
               <BaseButton v-if="openUser.id !== currentUserId" size="sm" severity="secondary" @click="messageTarget = openUser">
                  <template #icon><MessageSquare class="size-3.5" /></template>
                  Xabar yozish
               </BaseButton>
               <a v-if="telegramLink(openUser)" :href="telegramLink(openUser)!" target="_blank" rel="noopener">
                  <BaseButton size="sm" severity="secondary">
                     <template #icon><Send class="size-3.5" /></template>
                     Telegramda
                  </BaseButton>
               </a>
               <BaseButton
                  v-if="openUser.telegram_user_id"
                  size="sm"
                  severity="secondary"
                  :loading="checkingId === openUser.id"
                  @click="checkChannels(openUser)"
               >
                  <template #icon><RefreshCw class="size-3.5" /></template>
                  Kanalni tekshirish
               </BaseButton>

               <template v-if="openUser.id !== currentUserId">
                  <BaseButton size="sm" severity="secondary" :loading="busyId === openUser.id" @click="askRole(openUser)">
                     <template #icon><ShieldCheck class="size-3.5" /></template>
                     {{ openUser.role === "admin" ? "Adminlikdan olish" : "Admin qilish" }}
                  </BaseButton>
                  <BaseButton
                     v-if="openUser.blocked_at"
                     size="sm"
                     severity="secondary"
                     :loading="busyId === openUser.id"
                     @click="unblock(openUser)"
                  >
                     <template #icon><Unlock class="size-3.5" /></template>
                     Blokdan chiqarish
                  </BaseButton>
                  <BaseButton
                     v-else-if="openUser.role !== 'admin'"
                     size="sm"
                     severity="danger"
                     class="admin-xactions-wide"
                     @click="askBlock(openUser)"
                  >
                     <template #icon><Ban class="size-3.5" /></template>
                     Bloklash
                  </BaseButton>
               </template>
            </template>
         </AdminSheet>


         <footer v-if="pagination" class="admin-pagination">
            <span>{{ pagination.from || 0 }}–{{ pagination.to || 0 }} / {{ pagination.total }}</span>
            <div class="flex items-center gap-2">
               <BaseButton
                  size="xs"
                  severity="secondary"
                  :disabled="pagination.current_page <= 1 || loading"
                  @click="load(pagination.current_page - 1)"
               >
                  Oldingi
               </BaseButton>
               <span class="admin-page-number">{{ pagination.current_page }} / {{ pagination.last_page || 1 }}</span>
               <BaseButton
                  size="xs"
                  severity="secondary"
                  :disabled="pagination.current_page >= pagination.last_page || loading"
                  @click="load(pagination.current_page + 1)"
               >
                  Keyingi
               </BaseButton>
            </div>
         </footer>
      </section>
   </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
   Ban,
   Bot,
   FileText,
   Megaphone,
   MessageSquare,
   RefreshCw,
   Route,
   Search,
   Send,
   ShieldCheck,
   Unlock,
   X,
} from "lucide-vue-next";
import AdminMessageDialog from "@admin/components/AdminMessageDialog.vue";
import AdminUserJourney from "@admin/components/AdminUserJourney.vue";
import AdminSheet from "@admin/components/AdminSheet.vue";
import AdminRepo from "@admin/entities/AdminRepo";
import { isValidKey, relativeLabel } from "@admin/modules/dates";
import { useAuth } from "@shared/store/useAuth";
import BaseButton from "@shared/ui/BaseButton.vue";
import BaseModal from "@shared/ui/BaseModal.vue";
import FieldSelect from "@shared/ui/FieldSelect.vue";
import { timeAgo, formatDate, formatDateTime } from "@shared/modules/formatters";

type UserItem = {
   id: number;
   name: string;
   username: string | null;
   telegram_user_id: number | null;
   phone?: string | null;
   /** Bot unga xabar yubora oladi */
   bot_can_message?: boolean;
   /** A'zo bo'lgan kanallar (config'dagi chat id) */
   channel_ids?: string[];
   /** Kanal a'zoligi oxirgi marta qachon tekshirilgan; null — hali tekshirilmagan */
   channels_checked_at?: string | null;
   role: "user" | "admin";
   products_count: number;
   active_products_count?: number;
   active_district?: { id: number; name: string } | null;
   created_at?: string;
   last_active_at?: string | null;
   blocked_at?: string | null;
   /** Ilovaga kirgan kunlar soni (bir kunda necha marta ochgani ahamiyatsiz) */
   visit_days: number;
   last_visit_on?: string | null;
};

type VisitSummary = {
   total: number;
   once: number;
   returning: number;
   regular: number;
   returned_week: number;
};
type VisitsFilter = "" | "once" | "returning";

type PaginationMeta = {
   current_page: number;
   last_page: number;
   total: number;
   from: number | null;
   to: number | null;
};

type ConfirmState = {
   title: string;
   description: string;
   note: string;
   confirmText: string;
   danger: boolean;
   withArchive?: boolean;
   run: () => Promise<unknown>;
};

const route = useRoute();
const router = useRouter();
const authStore = useAuth();
const currentUserId = authStore.user?.id ?? 0;

type Segment = "" | "today" | "posters" | "city_only" | "idle" | "bot";
const segmentChips: { value: Segment; label: string; hint: string }[] = [
   { value: "", label: "Hammasi", hint: "Barcha foydalanuvchilar" },
   { value: "today", label: "Bugun kirgan", hint: "Bugun ilovani ochganlar — oxirgi kirgan birinchi" },
   { value: "posters", label: "E'lon qo'ygan", hint: "Kamida bitta e'lon joylagan (arxivdagisi ham)" },
   { value: "city_only", label: "Faqat shahar tanlagan", hint: "Shaharni tanlagan, lekin e'lon qo'ymagan" },
   { value: "idle", label: "Hech narsa qilmagan", hint: "Shahar ham tanlamagan, e'lon ham qo'ymagan" },
   { value: "bot", label: "Bot yoza oladi", hint: "Botga /start bosgan yoki yozishga ruxsat bergan — bot ularga xabar yubora oladi" },
];

type Subscription = "" | "both" | "channel_only" | "bot_only" | "none";
type ChannelInfo = { id: string; title: string; username: string | null };
const subscriptionCards: { value: Exclude<Subscription, "">; label: string; channel: boolean; bot: boolean }[] = [
   { value: "both", label: "Kanal va bot", channel: true, bot: true },
   { value: "channel_only", label: "Faqat kanal", channel: true, bot: false },
   { value: "bot_only", label: "Faqat bot", channel: false, bot: true },
   { value: "none", label: "Hech biri", channel: false, bot: false },
];
const subscriptionValues = subscriptionCards.map((card) => card.value as string);
const subscription = ref<Subscription>(
   (subscriptionValues.includes(String(route.query.subscription)) ? route.query.subscription : "") as Subscription,
);
const subscriptionCounts = ref<Record<string, number>>({});
const channels = ref<ChannelInfo[]>([]);
const checkingId = ref<number | null>(null);

const users = ref<UserItem[]>([]);
const pagination = ref<PaginationMeta | null>(null);
// Menyudagi qidiruvdan yoki bosh sahifadan kelganda: ?search= va ?date= (shu kuni qo'shilganlar)
const searchInput = ref(typeof route.query.search === "string" ? route.query.search : "");
const dateFilter = ref<string | null>(isValidKey(route.query.date) ? route.query.date : null);

function clearDateFilter() {
   dateFilter.value = null;
   router.replace({ query: { ...route.query, date: undefined } });
   load(1);
}
// "all" — FieldSelect bo'sh qiymatni "Tanlang" deb ko'rsatadi
const roleFilter = ref("all");
const segment = ref<Segment>(
   (["today", "posters", "city_only", "idle", "bot"] as const).find((value) => value === route.query.segment) ?? "",
);
const segmentCounts = ref<Record<string, number>>({});
// "Ilovadagi yo'li" oynasi ochilgan foydalanuvchi
const journeyUserId = ref<number | null>(null);
const visitsFilter = ref<VisitsFilter>("");
const sort = ref("new");
const summary = ref<VisitSummary | null>(null);
const loading = ref(true);
const busyId = ref<number | null>(null);
// ?open=ID — bosh sahifadan kelganda shu yozuv darhol oynada ochiladi
const openId = ref<number | null>(Number(route.query.open) || null);
const error = ref("");
const notice = ref("");
const confirmState = ref<ConfirmState | null>(null);
const archiveProducts = ref(false);
// "Xabar yozish" oynasi ochilgan foydalanuvchi
const messageTarget = ref<UserItem | null>(null);

function isRecentlyActive(dateString: string | null | undefined): boolean {
   if (!dateString) return false;
   return Date.now() - new Date(dateString).getTime() < 1000 * 60 * 60 * 24;
}

const num = (value: number) => value.toLocaleString("ru-RU");

function percent(value: number) {
   const total = summary.value?.total || 0;
   return total ? `${Math.round((value / total) * 100)}%` : "0%";
}

function visitLabel(days: number) {
   if (!days) return "Ma'lumot yo'q";
   return days === 1 ? "1 kun" : `${days} kun`;
}


function telegramLink(user: UserItem) {
   if (user.username) return `https://t.me/${user.username}`;
   return user.telegram_user_id ? `tg://user?id=${user.telegram_user_id}` : null;
}

async function load(page = 1) {
   loading.value = true;
   error.value = "";
   try {
      const params: Record<string, any> = { page };
      if (searchInput.value.trim()) params.search = searchInput.value.trim();
      if (roleFilter.value !== "all") params.role = roleFilter.value;
      if (segment.value) params.segment = segment.value;
      if (visitsFilter.value) params.visits = visitsFilter.value;
      if (sort.value !== "new") params.sort = sort.value;
      if (subscription.value) params.subscription = subscription.value;
      if (dateFilter.value) params.date = dateFilter.value;

      const { data } = await AdminRepo.users(params);
      users.value = data.data;
      channels.value = data.channels ?? [];
      subscriptionCounts.value = data.subscription_counts ?? {};
      summary.value = data.visit_summary ?? null;
      segmentCounts.value = data.segment_counts ?? {};
      pagination.value = {
         current_page: data.current_page,
         last_page: data.last_page,
         total: data.total,
         from: data.from,
         to: data.to,
      };
   } catch (exception) {
      console.error("Foydalanuvchilarni yuklab bo'lmadi.", exception);
      error.value = "Foydalanuvchilarni yuklab bo'lmadi. Qaytadan urinib ko'ring.";
   } finally {
      loading.value = false;
   }
}

function setSegment(value: Segment) {
   if (segment.value === value) return;
   segment.value = value;
   openId.value = null;
   // Havola saqlanadi: bosh sahifadan "Bugun kirganlar"ga to'g'ridan-to'g'ri o'tish mumkin
   router.replace({ query: { ...route.query, segment: value || undefined } });
   load(1);
}

/** Obuna kartasi: qayta bosilsa — filtr olib tashlanadi */
function setSubscription(value: Exclude<Subscription, "">) {
   subscription.value = subscription.value === value ? "" : value;
   openId.value = null;
   router.replace({ query: { ...route.query, subscription: subscription.value || undefined } });
   load(1);
}

function channelState(user: UserItem) {
   if (user.channel_ids?.length) return "is-on";
   return user.channels_checked_at ? "is-off" : "is-unknown";
}

function channelNames(user: UserItem) {
   return (user.channel_ids ?? [])
      .map((id) => channels.value.find((channel) => channel.id === id)?.title ?? id)
      .join(", ");
}

function channelTitle(user: UserItem) {
   const state = channelState(user);
   if (state === "is-on") return `Kanalga a'zo: ${channelNames(user)}`;
   return state === "is-off" ? "Kanalga a'zo emas" : "Kanal a'zoligi hali tekshirilmagan";
}

async function checkChannels(user: UserItem) {
   checkingId.value = user.id;
   error.value = "";
   notice.value = "";
   try {
      const { data } = await AdminRepo.checkUserChannels(user.id);
      user.channel_ids = data.channel_ids;
      user.channels_checked_at = data.channels_checked_at;
      notice.value = data.message;
   } catch (exception: any) {
      error.value = exception?.response?.data?.message || "Kanal a'zoligini tekshirib bo'lmadi.";
   } finally {
      checkingId.value = null;
   }
}

/** Karta qayta bosilsa — filtr olib tashlanadi */
function setVisits(value: Exclude<VisitsFilter, "">) {
   visitsFilter.value = visitsFilter.value === value ? "" : value;
   openId.value = null;
   load(1);
}

// Oynadagi foydalanuvchi: ro'yxat yangilansa (rol, blok) yangi holat ko'rinadi
const openUser = computed(() => users.value.find((user) => user.id === openId.value) ?? null);
watch(openId, () => {
   notice.value = "";
});

function openProducts(user: UserItem) {
   router.push({ name: "admin-products", query: { user_id: user.id, user_name: user.name } });
}

async function act(user: UserItem, action: () => Promise<{ data?: { message?: string } }>, failure: string) {
   busyId.value = user.id;
   error.value = "";
   notice.value = "";
   try {
      const response = await action();
      notice.value = response?.data?.message || "Saqlandi.";
      await load(pagination.value?.current_page || 1);
   } catch (exception: any) {
      console.error(failure, exception);
      error.value = exception?.response?.data?.message || failure;
   } finally {
      busyId.value = null;
   }
}

function askRole(user: UserItem) {
   const makeAdmin = user.role !== "admin";
   confirmState.value = {
      title: makeAdmin ? "Admin qilish" : "Adminlikdan olish",
      description: user.name,
      note: makeAdmin
         ? "Bu foydalanuvchi admin panelga kira oladi: e'lonlar, foydalanuvchilar va sozlamalarni boshqaradi."
         : "Foydalanuvchi admin paneldan foydalana olmay qoladi.",
      confirmText: makeAdmin ? "Admin qilish" : "Huquqni olish",
      danger: false,
      run: () => act(user, () => AdminRepo.updateUserRole(user.id, makeAdmin ? "admin" : "user"), "Rolni o'zgartirib bo'lmadi."),
   };
}

function askBlock(user: UserItem) {
   archiveProducts.value = false;
   confirmState.value = {
      title: "Foydalanuvchini bloklash",
      description: user.name,
      note: "Bloklangan foydalanuvchi e'lonlarni ko'ra oladi, lekin yangi e'lon joylay olmaydi va eskilarini tahrirlay olmaydi.",
      confirmText: "Bloklash",
      danger: true,
      withArchive: user.products_count > 0,
      run: () => act(user, () => AdminRepo.blockUser(user.id, archiveProducts.value), "Bloklab bo'lmadi."),
   };
}

function unblock(user: UserItem) {
   return act(user, () => AdminRepo.unblockUser(user.id), "Blokdan chiqarib bo'lmadi.");
}

function runConfirm() {
   const state = confirmState.value;
   confirmState.value = null;
   state?.run();
}

const segmentNav = ref<HTMLElement | null>(null);

onMounted(async () => {
   await load(1);
   // Havoladan kelgan bo'lim (masalan "Faqat shahar tanlagan") telefonda ekran tashqarisida qolmasin
   segmentNav.value?.querySelector<HTMLElement>(".is-active")?.scrollIntoView({ block: "nearest", inline: "center" });
});
</script>
