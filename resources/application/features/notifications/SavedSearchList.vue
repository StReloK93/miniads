<template>
   <!-- Kuzatuvlarim: bosilsa qidiruv ochiladi; o'ngda pauza va o'chirish -->
   <div>
      <ul v-if="items?.length" class="bg-(--z-card) border border-(--z-border) rounded-(--z-rounded) overflow-hidden divide-y divide-(--z-border)">
         <li v-for="item in items" :key="item.id" class="flex items-center gap-2 pl-4 pr-2 min-h-14">
            <button
               type="button"
               class="min-w-0 grow py-2.5 text-left cursor-pointer"
               :class="{ 'opacity-50': item.is_paused }"
               @click="open(item)"
            >
               <span class="block text-sm font-medium truncate">{{ item.label }}</span>
               <span class="block text-[11px] text-(--z-muted-text)">
                  {{ item.is_paused ? $t("watch.paused") : item.last_notified_at ? $t("watch.lastNotified", { time: timeAgo(item.last_notified_at) }) : $t("watch.noMatches") }}
               </span>
            </button>
            <button
               type="button"
               class="size-9 shrink-0 grid place-items-center rounded-full text-(--z-muted-text) cursor-pointer active:bg-(--z-muted)"
               :aria-label="item.is_paused ? $t('watch.resume') : $t('watch.pause')"
               :disabled="busyId === item.id"
               @click="togglePause(item)"
            >
               <component :is="item.is_paused ? BellOff : BellRing" class="size-4.5" />
            </button>
            <button
               type="button"
               class="size-9 shrink-0 grid place-items-center rounded-full text-(--z-muted-text) cursor-pointer active:bg-(--z-muted)"
               :aria-label="$t('common.delete')"
               :disabled="busyId === item.id"
               @click="removeItem(item)"
            >
               <X class="size-4.5" />
            </button>
         </li>
      </ul>

      <div v-else-if="items" class="bg-(--z-card) border border-(--z-border) rounded-(--z-rounded) overflow-hidden px-4 py-4 flex gap-3 items-start">
         <BellRing class="size-5 shrink-0 mt-0.5 text-(--z-muted-text)" />
         <p class="text-sm text-(--z-muted-text) leading-snug">{{ $t("watch.empty") }}</p>
      </div>

      <div v-else class="bg-(--z-card) border border-(--z-border) rounded-(--z-rounded) overflow-hidden h-14 skeleton" />
   </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { useRouter } from "vue-router";
import { BellOff, BellRing, X } from "lucide-vue-next";
import { timeAgo } from "@shared/modules/formatters";
import { ISavedSearch } from "@shared/types";
import { useSavedSearches } from "@/features/notifications/useSavedSearches";

const router = useRouter();
const { items, remove, setPaused } = useSavedSearches();
const busyId = ref<number | null>(null);

function open(item: ISavedSearch) {
   router.push({ name: "home", query: { saved: item.id } });
}

async function run(item: ISavedSearch, action: () => Promise<void>) {
   busyId.value = item.id;
   try {
      await action();
   } finally {
      busyId.value = null;
   }
}

const togglePause = (item: ISavedSearch) => run(item, () => setPaused(item.id, !item.is_paused));
const removeItem = (item: ISavedSearch) => run(item, () => remove(item.id));
</script>
