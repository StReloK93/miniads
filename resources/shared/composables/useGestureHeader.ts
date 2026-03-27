import { ref } from "vue";

export function useGestureHeader() {
   const isCompact = ref(false);

   const startY = ref(0);
   const moved = ref(false);
   const lastDirection = ref<"up" | "down" | null>(null);
   const threshold = 8;

   const container = ref<HTMLElement | null>(null);

   const setContainer = (el: HTMLElement | null) => {
      container.value = el;
   };

   const handleTouchStart = (e: TouchEvent) => {
      startY.value = e.touches[0].clientY;
      moved.value = false;
      lastDirection.value = null;
   };

   const handleTouchMove = (e: TouchEvent) => {
      const currentY = e.touches[0].clientY;
      const diff = startY.value - currentY;

      if (Math.abs(diff) <= threshold) return;

      moved.value = true;

      // barmoq tepaga ketdi => content pastga yurdi
      if (diff > 0) {
         lastDirection.value = "up";
         isCompact.value = true;
      }
      // barmoq pastga ketdi => expand intent, lekin hozircha ochmaymiz
      else {
         lastDirection.value = "down";
      }

      startY.value = currentY;
   };

   const handleTouchEnd = () => {
      if (!moved.value) return;

      // faqat pastga gesture bo'lib, container tepaga yetgan bo'lsa expand qilamiz
      if (lastDirection.value === "down" && container.value && container.value.scrollTop <= 0) {
         isCompact.value = false;
      }
   };

   const handleWheel = (e: WheelEvent) => {
      if (e.deltaY > 0) {
         isCompact.value = true;
         return;
      }

      if (e.deltaY < 0 && container.value && container.value.scrollTop <= 0) {
         isCompact.value = false;
      }
   };

   return {
      isCompact,
      setContainer,
      handleTouchStart,
      handleTouchMove,
      handleTouchEnd,
      handleWheel,
   };
}
