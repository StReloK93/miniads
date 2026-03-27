import { ref } from "vue";

export function useGestureHeader() {
   const isCompact = ref(false);
   const startY = ref(0);

   const handleTouchStart = (e: TouchEvent) => {
      startY.value = e.touches[0].clientY;
   };

   const handleTouchMove = (e: TouchEvent) => {
      const currentY = e.touches[0].clientY;
      const diff = startY.value - currentY;

      if (Math.abs(diff) < 6) return;

      // user barmoqni tepaga surdi => content pastga ketadi => compact
      if (diff > 0) {
         isCompact.value = true;
      }
      // user barmoqni pastga surdi => content tepaga qaytadi => expand
      else {
         isCompact.value = false;
      }

      startY.value = currentY;
   };

   const handleWheel = (e: WheelEvent) => {
      if (Math.abs(e.deltaY) < 3) return;

      if (e.deltaY > 0) {
         isCompact.value = true;
      } else {
         isCompact.value = false;
      }
   };

   return {
      isCompact,
      handleTouchStart,
      handleTouchMove,
      handleWheel,
   };
}
