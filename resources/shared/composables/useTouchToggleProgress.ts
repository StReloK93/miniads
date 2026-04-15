import { ref, onMounted, onBeforeUnmount, type Ref, watch } from "vue";
import { postEvent, isTMA } from "@tma.js/bridge";

interface Options {
   threshold?: number;
   easing?: number; // 0.05 - 0.25 oralig'i yaxshi
   autoScroll?: boolean;
}

export function useTouchToggleProgress(containerRef: Ref<HTMLElement | null>, options: Options = {}) {
   const threshold = options.threshold ?? 120;
   const easing = options.easing ?? 0.28;

   const progress = ref(0); // haqiqiy progress
   const displayProgress = ref(0); // ekranga chiqadigan yumshoq progress
   const isActive = ref(false);
   const isTouching = ref(false);

   watch(isActive, (newVal) => {
      if (!isTMA()) return;
      postEvent("web_app_trigger_haptic_feedback", {
         type: "selection_change",
      });
   });

   let startY = 0;
   let lastY = 0;
   let rafId = 0;

   const clamp = (value: number, min = 0, max = 100) => Math.min(max, Math.max(min, value));

   const animate = () => {
      const diff = progress.value - displayProgress.value;

      if (Math.abs(diff) < 0.1) {
         displayProgress.value = progress.value;
      } else {
         displayProgress.value += diff * easing;
      }

      rafId = requestAnimationFrame(animate);
   };

   const resetTouch = () => {
      isTouching.value = false;
      startY = 0;
      lastY = 0;
   };

   const onTouchStart = (e: TouchEvent) => {
      if (!e.touches.length) return;

      const y = e.touches[0].clientY;
      startY = y;
      lastY = y;
      isTouching.value = true;
   };

   const onTouchMove = (e: TouchEvent) => {
      const el = containerRef.value;
      if (!el || !isTouching.value || !e.touches.length) return;

      const currentY = e.touches[0].clientY;
      const diffFromLast = currentY - lastY;
      lastY = currentY;

      if (!isActive.value) {
         if (diffFromLast < 0) {
            const delta = Math.abs(diffFromLast);
            progress.value = clamp(progress.value + (delta / threshold) * 100);
         }

         if (diffFromLast > 0) {
            const delta = Math.abs(diffFromLast);
            progress.value = clamp(progress.value - (delta / threshold) * 100);
         }

         if (progress.value >= 100) {
            progress.value = 100;
            isActive.value = true;
         }

         return;
      }

      if (el.scrollTop > 0) {
         progress.value = 100;
         return;
      }

      if (diffFromLast > 0) {
         const delta = Math.abs(diffFromLast);
         progress.value = clamp(progress.value - (delta / threshold) * 100);
      }

      if (diffFromLast < 0) {
         const delta = Math.abs(diffFromLast);
         progress.value = clamp(progress.value + (delta / threshold) * 100);
      }

      if (progress.value <= 0) {
         progress.value = 0;
         isActive.value = false;
      }
   };

   const onTouchEnd = () => {
      resetTouch();

      if (!isActive.value) {
         progress.value = 0;
      } else {
         progress.value = 100;
      }
   };

   onMounted(() => {
      const el = containerRef.value;
      if (!el || options.autoScroll) return;

      el.addEventListener("touchstart", onTouchStart, { passive: true });
      el.addEventListener("touchmove", onTouchMove, { passive: true });
      el.addEventListener("touchend", onTouchEnd, { passive: true });
      el.addEventListener("touchcancel", onTouchEnd, { passive: true });

      rafId = requestAnimationFrame(animate);
   });

   onBeforeUnmount(() => {
      const el = containerRef.value;
      if (el) {
         el.removeEventListener("touchstart", onTouchStart);
         el.removeEventListener("touchmove", onTouchMove);
         el.removeEventListener("touchend", onTouchEnd);
         el.removeEventListener("touchcancel", onTouchEnd);
      }

      cancelAnimationFrame(rafId);
   });

   return {
      progress,
      displayProgress,
      isActive,
      isTouching,
   };
}
