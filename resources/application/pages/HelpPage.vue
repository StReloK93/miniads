<template>
   <section class="grid grid-rows-[auto_1fr]">
      <aside class="pb-3 mb-3 border-b border-(--z-border) -mx-4 px-4">
         <div class="flex items-center gap-2">
            <PageBackButton />
            <h3 class="grow font-extrabold text-xl">{{ $t("help.title") }}</h3>
         </div>
      </aside>

      <div class="relative">
         <main ref="scroller" class="absolute inset-0 overflow-y-auto no-scrollbar pb-8 flex flex-col gap-5">
            <p class="text-sm text-(--z-muted-text) leading-snug px-1">{{ $t("help.intro") }}</p>

            <!-- Birinchi kirishdagi slaydlarni istalgan vaqtda qayta ko'rish -->
            <button type="button" class="card question" @click="tour.open()">
               <PlayCircle class="size-4.5 shrink-0 text-(--z-primary)" />
               <span class="grow text-left text-sm font-semibold">{{ $t("help.tour.replay") }}</span>
               <ChevronRight class="size-4 shrink-0 text-(--z-muted-text)" />
            </button>

            <section v-for="group in groups" :key="group.id">
               <h4 class="section-title flex items-center gap-1.5">
                  <component :is="group.icon" class="size-3.5" />
                  {{ $t(`help.groups.${group.id}`) }}
               </h4>

               <ul class="card divide-y divide-(--z-border)">
                  <li v-for="id in group.items" :key="id" :data-faq="id">
                     <button
                        type="button"
                        class="question"
                        :aria-expanded="openId === id"
                        @click="toggle(id)"
                     >
                        <span class="grow text-left text-sm font-semibold leading-snug">
                           {{ $t(`help.faq.${id}.q`) }}
                        </span>
                        <ChevronDown
                           class="size-4 shrink-0 text-(--z-muted-text) transition-transform duration-200"
                           :class="{ 'rotate-180': openId === id }"
                        />
                     </button>

                     <div class="answer" :class="{ 'is-open': openId === id }">
                        <div>
                           <p class="px-4 pb-3.5 text-sm leading-relaxed text-(--z-muted-text)">
                              {{ $t(`help.faq.${id}.a`, { channel: channelName || "Telegram" }) }}
                           </p>
                           <button
                              v-if="id === 'channel' && channelUrl"
                              type="button"
                              class="mx-4 mb-3.5 inline-flex items-center gap-1.5 text-sm font-semibold text-(--z-primary)"
                              @click="openChannel"
                           >
                              <Send class="size-4" />
                              {{ $t("help.faq.channel.open") }}
                           </button>
                        </div>
                     </div>
                  </li>
               </ul>
            </section>
         </main>
      </div>
   </section>
</template>

<script setup lang="ts">
import PageBackButton from "@/layouts/PageBackButton.vue";
import { nextTick, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import {
   BellRing,
   ChevronDown,
   ChevronRight,
   Megaphone,
   PlayCircle,
   Send,
   ShieldCheck,
   Store,
} from "lucide-vue-next";
import { channelName, channelUrl, openChannel } from "@/features/help/helpLinks";
import { useOnboardingTour } from "@/features/help/useOnboardingTour";

const tour = useOnboardingTour();
const route = useRoute();

// Savollar tartibi; matnlar help.faq.<id>.q / .a da (uz/ru/en)
const groups = [
   { id: "ads", icon: Megaphone, items: ["post", "ai", "duration", "restore", "sold", "channel", "photos"] },
   { id: "watch", icon: BellRing, items: ["watch", "favorites", "notifications"] },
   { id: "vitrina", icon: Store, items: ["vitrinaWhat", "vitrinaVsAd", "vitrinaCreate"] },
   { id: "safety", icon: ShieldCheck, items: ["report"] },
] as const;

const openId = ref<string | null>(null);
const scroller = ref<HTMLElement | null>(null);

function toggle(id: string) {
   openId.value = openId.value === id ? null : id;
}

// /help?q=duration — boshqa joydan ("Batafsil") kelganda kerakli savol ochiq turadi
onMounted(async () => {
   const q = route.query.q;
   if (typeof q !== "string" || !groups.some((g) => (g.items as readonly string[]).includes(q))) return;

   openId.value = q;
   await nextTick();
   scroller.value?.querySelector(`[data-faq="${q}"]`)?.scrollIntoView({ block: "center" });
});
</script>

<style scoped>
.section-title {
   margin: 0 0 0.5rem 0.25rem;
   color: var(--z-muted-text);
   font-size: 0.6875rem;
   font-weight: 700;
   letter-spacing: 0.08em;
   text-transform: uppercase;
}

.card {
   overflow: hidden;
   border: 1px solid var(--z-glass-border);
   border-radius: var(--z-rounded);
   background: var(--z-card);
   box-shadow: var(--z-glass-edge, none);
}

.question {
   display: flex;
   width: 100%;
   min-height: 3.25rem;
   align-items: center;
   gap: 0.75rem;
   padding: 0.75rem 1rem;
   cursor: pointer;

   &:active {
      background: var(--z-muted);
   }
}

/* Balandlikni silliq ochish: grid 0fr → 1fr */
.answer {
   display: grid;
   grid-template-rows: 0fr;
   transition: grid-template-rows 0.22s ease;

   > div {
      overflow: hidden;
   }

   &.is-open {
      grid-template-rows: 1fr;
   }
}
</style>
