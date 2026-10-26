<template>
   <Transition name="lightbox">
      <div
         v-if="modelValue !== null"
         class="fixed inset-0 z-500 select-none touch-none"
         role="dialog"
         aria-modal="true"
         aria-label="Rasmlarni ko'rish"
         @touchstart="onTouchStart"
         @touchmove="onTouchMove"
         @touchend="onTouchEnd"
         @touchcancel="onTouchEnd"
      >
         <!-- Fon: pastga surganda xiralashib boradi -->
         <div class="absolute inset-0 bg-black" :style="{ opacity: backdropOpacity }" />

         <div
            class="absolute inset-0"
            :class="{ 'transition-transform duration-200': !isDragging }"
            :style="{ transform: `translateY(${dragY}px) scale(${dragScale})` }"
         >
            <swiper
               :modules="[Zoom, Keyboard]"
               :initial-slide="modelValue"
               :zoom="{ maxRatio: 3, toggle: true }"
               :keyboard="{ enabled: true }"
               :space-between="16"
               class="h-full w-full"
               @swiper="(instance) => (swiperInstance = instance)"
               @slide-change="onSlideChange"
            >
               <swiper-slide
                  v-for="image in images"
                  :key="image.id"
                  class="flex! items-center justify-center"
                  @click="onSlideClick"
               >
                  <div class="swiper-zoom-container">
                     <img :src="image.src" class="max-h-full max-w-full object-contain" alt="E'lon rasmi" draggable="false" />
                  </div>
               </swiper-slide>
            </swiper>
         </div>

         <!-- Yuqori panel: hisoblagich va yopish -->
         <header
            class="absolute inset-x-0 top-0 z-10 flex items-center justify-between px-4 pt-[calc(var(--safe-area-top)+0.75rem)] pb-8 bg-linear-to-b from-black/60 to-transparent pointer-events-none transition-opacity"
            :class="{ 'opacity-0': isDragging }"
         >
            <span
               class="pointer-events-auto h-9 px-3.5 inline-flex items-center rounded-full bg-white/10 border border-white/15 backdrop-blur-md text-white text-sm font-semibold tabular-nums"
            >
               {{ activeIndex + 1 }} / {{ images.length }}
            </span>
            <button
               type="button"
               class="pointer-events-auto size-9 inline-flex items-center justify-center rounded-full bg-white/10 border border-white/15 backdrop-blur-md text-white cursor-pointer transition active:scale-90 hover:bg-white/20"
               aria-label="Yopish"
               @click="close"
            >
               <X class="size-5" />
            </button>
         </header>

         <!-- Pastki panel: miniatyuralar -->
         <footer
            v-if="images.length > 1"
            class="absolute inset-x-0 bottom-0 z-10 pt-8 pb-[calc(var(--safe-area-bottom)+1rem)] bg-linear-to-t from-black/60 to-transparent transition-opacity"
            :class="{ 'opacity-0': isDragging }"
         >
            <div class="overflow-x-auto no-scrollbar">
               <div class="flex gap-2 w-max mx-auto px-4 py-1">
                  <button
                     v-for="(image, index) in images"
                     :key="image.id"
                     :ref="(el) => (thumbs[index] = el as HTMLElement)"
                     type="button"
                     class="shrink-0 size-14 rounded-xl overflow-hidden cursor-pointer transition duration-200"
                     :class="index === activeIndex ? 'ring-2 ring-white opacity-100 scale-105' : 'opacity-45 hover:opacity-80'"
                     :aria-label="`${index + 1}-rasm`"
                     @click="goTo(index)"
                  >
                     <img :src="image.src" class="size-full object-cover" alt="" draggable="false" />
                  </button>
               </div>
            </div>
         </footer>
      </div>
   </Transition>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, shallowRef, watch } from "vue";
import { Swiper, SwiperSlide } from "swiper/vue";
import { Keyboard, Zoom } from "swiper/modules";
import type { Swiper as SwiperType } from "swiper";
import { X } from "lucide-vue-next";
import { pushBackHandler } from "@/modules/navigation";

const props = defineProps<{
   images: { id: number; src: string }[];
   /** Ochilgan rasm indeksi; null — yopiq */
   modelValue: number | null;
}>();

const emit = defineEmits<{
   (e: "update:modelValue", value: number | null): void;
   /** Ko'rilayotgan rasm o'zgarganda — sahifadagi galereyani sinxronlash uchun */
   (e: "slide", index: number): void;
}>();

const swiperInstance = shallowRef<SwiperType | null>(null);
const activeIndex = ref(0);
const thumbs: HTMLElement[] = [];

function close() {
   emit("update:modelValue", null);
}

function goTo(index: number) {
   swiperInstance.value?.slideTo(index);
}

function onSlideChange(instance: SwiperType) {
   activeIndex.value = instance.activeIndex;
   emit("slide", instance.activeIndex);
   thumbs[instance.activeIndex]?.scrollIntoView({ behavior: "smooth", inline: "center", block: "nearest" });
}

// Rasmdan tashqarini bosish — yopadi. Oddiy (native) click ishlatiladi: u touchend'dan keyin keladi,
// shuning uchun overlay yopilgach ostidagi elementga "arvoh bosish" o'tib ketmaydi.
function onSlideClick(event: MouseEvent) {
   if ((event.target as HTMLElement).tagName === "IMG") return;
   close();
}

// --- Pastga surib yopish ---
const CLOSE_DISTANCE = 120;
let startX = 0;
let startY = 0;
let direction: "vertical" | "horizontal" | null = null;
const dragY = ref(0);
const isDragging = ref(false);

const backdropOpacity = computed(() => Math.max(0.3, 1 - Math.abs(dragY.value) / 400));
const dragScale = computed(() => Math.max(0.85, 1 - Math.abs(dragY.value) / 1500));

function isZoomed() {
   return (swiperInstance.value?.zoom?.scale ?? 1) > 1;
}

function onTouchStart(event: TouchEvent) {
   if (event.touches.length !== 1 || isZoomed()) return;
   startX = event.touches[0].clientX;
   startY = event.touches[0].clientY;
   direction = null;
}

function onTouchMove(event: TouchEvent) {
   if (event.touches.length !== 1 || isZoomed()) return;
   const dx = event.touches[0].clientX - startX;
   const dy = event.touches[0].clientY - startY;

   if (!direction && Math.hypot(dx, dy) > 10) {
      direction = Math.abs(dy) > Math.abs(dx) ? "vertical" : "horizontal";
   }

   if (direction === "vertical") {
      isDragging.value = true;
      dragY.value = dy;
   }
}

function onTouchEnd() {
   if (direction === "vertical" && Math.abs(dragY.value) > CLOSE_DISTANCE) {
      close();
   }
   direction = null;
   isDragging.value = false;
   dragY.value = 0;
}

// --- Klaviatura: Esc yopadi (strelkalarni Swiper'ning Keyboard moduli boshqaradi) ---
function onKeydown(event: KeyboardEvent) {
   if (event.key === "Escape") close();
}

// Telegram'ning "orqaga" tugmasi ochiq preview'ni yopadi (sahifadan chiqib ketmaydi)
let releaseBackHandler: (() => void) | null = null;

function detach() {
   window.removeEventListener("keydown", onKeydown);
   releaseBackHandler?.();
   releaseBackHandler = null;
}

watch(
   () => props.modelValue,
   async (value) => {
      if (value === null) {
         detach();
         return;
      }
      activeIndex.value = value;
      if (!releaseBackHandler) {
         window.addEventListener("keydown", onKeydown);
         releaseBackHandler = pushBackHandler(close);
      }
      await nextTick();
      thumbs[value]?.scrollIntoView({ inline: "center", block: "nearest" });
   },
   { immediate: true },
);

onBeforeUnmount(detach);
</script>

<style scoped>
.lightbox-enter-active,
.lightbox-leave-active {
   transition: opacity 0.22s ease;
}
.lightbox-enter-active :deep(.swiper),
.lightbox-leave-active :deep(.swiper) {
   transition: transform 0.22s ease;
}
.lightbox-enter-from,
.lightbox-leave-to {
   opacity: 0;
}
.lightbox-enter-from :deep(.swiper),
.lightbox-leave-to :deep(.swiper) {
   transform: scale(0.94);
}
</style>
