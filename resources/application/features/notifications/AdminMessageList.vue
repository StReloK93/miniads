<template>
   <!-- Profil: administratsiyadan kelgan xabarlar. Yangilari ajralib turadi, ko'rilgach o'qilgan bo'ladi -->
   <section v-if="messages.length">
      <h4 class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-(--z-muted-text) mb-2 px-1">
         {{ $t("adminMessage.title") }}
         <span v-if="newIds.size" class="px-1.5 rounded-full bg-rose-500 text-white text-[10px] leading-4 normal-case">
            {{ $t("adminMessage.new", { n: newIds.size }) }}
         </span>
      </h4>

      <ul class="flex flex-col gap-2">
         <li
            v-for="message in visible"
            :key="message.id"
            class="rounded-(--z-rounded) border bg-(--z-card) px-4 py-3"
            :class="message.type === 'warning' ? 'border-amber-500/40' : 'border-(--z-border)'"
         >
            <div class="flex items-center gap-1.5 text-xs text-(--z-muted-text) mb-1">
               <component
                  :is="message.type === 'warning' ? TriangleAlert : MessageSquare"
                  class="size-3.5 shrink-0"
                  :class="{ 'text-amber-500': message.type === 'warning' }"
               />
               <span class="truncate">
                  {{ message.type === "warning" ? $t("adminMessage.warning") : $t("adminMessage.info") }}
                  · {{ timeAgo(message.created_at) }}
               </span>
               <span v-if="newIds.has(message.id)" class="ml-auto size-2 shrink-0 rounded-full bg-rose-500" />
            </div>
            <p class="text-sm whitespace-pre-line wrap-break-word text-(--z-foreground)">{{ message.body }}</p>
            <RouterLink
               v-if="message.product"
               :to="{ name: 'product-id', params: { id: message.product.id } }"
               class="mt-1.5 inline-flex max-w-full text-xs font-semibold text-(--z-primary)"
            >
               <span class="truncate">{{ $t("adminMessage.aboutAd", { title: message.product.title }) }}</span>&nbsp;→
            </RouterLink>
         </li>
      </ul>

      <button
         v-if="messages.length > LIMIT && !showAll"
         type="button"
         class="mt-2 px-1 text-xs font-medium text-(--z-primary) cursor-pointer"
         @click="showAll = true"
      >
         {{ $t("adminMessage.showAll", { n: messages.length }) }}
      </button>
   </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { MessageSquare, TriangleAlert } from "lucide-vue-next";
import { timeAgo } from "@shared/modules/formatters";
import { useAdminMessages } from "@/features/notifications/useAdminMessages";

const LIMIT = 3;
const { messages, load, markAllRead } = useAdminMessages();
const showAll = ref(false);
// Shu ochilishda "yangi" deb ko'rsatiladiganlar (o'qildi deb belgilangandan keyin ham ajralib tursin)
const newIds = ref(new Set<number>());

const visible = computed(() => (showAll.value ? messages.value : messages.value.slice(0, LIMIT)));

function rememberNewAndMarkRead() {
   const unread = messages.value.filter((message) => !message.read_at);
   if (!unread.length) return;
   newIds.value = new Set([...newIds.value, ...unread.map((message) => message.id)]);
   markAllRead();
}

watch(messages, rememberNewAndMarkRead);
onMounted(async () => {
   rememberNewAndMarkRead();
   // Profil ochilganda yangi xabar kelgan bo'lishi mumkin
   await load();
});
</script>
