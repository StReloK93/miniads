<template>
   <Teleport to="body">
      <Transition name="tour">
         <!-- Pastki menyu z-100 da — tanishtiruv undan yuqorida, orqa fon to'liq yopiladi -->
         <div
            v-if="isOpen"
            class="fixed inset-0 z-1000 flex items-end justify-center bg-black/70 backdrop-blur-sm"
            @click.self="finish"
         >
            <section
               class="tour-sheet w-full sm:max-w-125 rounded-t-3xl border-t border-(--z-border) bg-(--z-background) shadow-2xl px-5 pt-3 pb-[max(1.5rem,calc(env(safe-area-inset-bottom)+1rem))]"
               role="dialog"
               aria-modal="true"
               :aria-label="$t(`help.tour.slides.${slide.id}.title`)"
            >
               <div class="mx-auto mb-4 h-1 w-10 rounded-full bg-(--z-border)"></div>

               <Transition name="slide" mode="out-in">
                  <div :key="slide.id" class="flex flex-col items-center text-center">
                     <div
                        class="mb-4 size-16 rounded-2xl bg-(--z-primary)/12 text-(--z-primary) flex items-center justify-center"
                     >
                        <component :is="slide.icon" class="size-8" />
                     </div>
                     <h3 class="text-lg font-extrabold leading-tight">{{ $t(`help.tour.slides.${slide.id}.title`) }}</h3>
                     <p class="mt-2 min-h-16 text-sm leading-relaxed text-(--z-muted-text)">
                        {{ $t(`help.tour.slides.${slide.id}.text`, { channel: channelName || "Telegram" }) }}
                     </p>
                  </div>
               </Transition>

               <div class="my-4 flex justify-center gap-1.5">
                  <button
                     v-for="(item, i) in slides"
                     :key="item.id"
                     type="button"
                     class="h-1.5 rounded-full transition-all"
                     :class="i === index ? 'w-5 bg-(--z-primary)' : 'w-1.5 bg-(--z-border)'"
                     :aria-label="`${i + 1} / ${slides.length}`"
                     @click="index = i"
                  ></button>
               </div>

               <div class="flex gap-3">
                  <BaseButton v-if="!isLast" type="button" severity="glass" class="w-full" @click="finish">
                     {{ $t("help.tour.skip") }}
                  </BaseButton>
                  <BaseButton v-else type="button" severity="glass" class="w-full" @click="openHelp">
                     {{ $t("help.more") }}
                  </BaseButton>
                  <BaseButton type="button" class="w-full" @click="next">
                     {{ isLast ? $t("help.tour.start") : $t("help.tour.next") }}
                  </BaseButton>
               </div>
            </section>
         </div>
      </Transition>
   </Teleport>
</template>

<script setup lang="ts">
import { computed, onUnmounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { BellRing, ImagePlus, Megaphone, Store } from "lucide-vue-next";
import BaseButton from "@shared/ui/BaseButton.vue";
import { pushBackHandler } from "@/modules/navigation";
import { useOnboardingTour } from "./useOnboardingTour";
import { channelName } from "./helpLinks";

const slides = [
   { id: "post", icon: Megaphone },
   { id: "photos", icon: ImagePlus },
   { id: "watch", icon: BellRing },
   { id: "vitrina", icon: Store },
] as const;

const router = useRouter();
const route = useRoute();
const { isOpen, close } = useOnboardingTour();
const index = ref(0);
const slide = computed(() => slides[index.value]);
const isLast = computed(() => index.value === slides.length - 1);

let releaseBack: (() => void) | null = null;

// Har ochilganda birinchi slayddan; Telegram "orqaga" tugmasi ilovani emas, tanishtiruvni yopadi
watch(
   isOpen,
   (open) => {
      releaseBack?.();
      releaseBack = null;
      if (open) {
         index.value = 0;
         releaseBack = pushBackHandler(finish);
      }
   },
   { immediate: true },
);
onUnmounted(() => releaseBack?.());

function finish() {
   close();
}

function next() {
   if (isLast.value) finish();
   else index.value++;
}

// Yordam sahifasidan ochilgan bo'lsa — o'sha yerda qolamiz
function openHelp() {
   finish();
   if (route.name !== "help") router.push({ name: "help" });
}
</script>

<style scoped>
.tour-enter-active,
.tour-leave-active {
   transition: opacity 0.25s ease;

   .tour-sheet {
      transition: transform 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
   }
}

.tour-enter-from,
.tour-leave-to {
   opacity: 0;

   .tour-sheet {
      transform: translateY(100%);
   }
}

.slide-enter-active,
.slide-leave-active {
   transition:
      opacity 0.18s ease,
      transform 0.18s ease;
}

.slide-enter-from {
   opacity: 0;
   transform: translateX(16px);
}

.slide-leave-to {
   opacity: 0;
   transform: translateX(-16px);
}
</style>
