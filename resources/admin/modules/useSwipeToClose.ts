import { onBeforeUnmount, watch, type Ref } from "vue";

/**
 * Pastdan chiqadigan oynani barmoq bilan pastga surib yopish (telefon).
 * - Oyna barmoq ortidan siljiydi, orqa fon asta xiralashadi.
 * - 110px dan ko'p tortilsa yoki tez siltansa — yopiladi, aks holda joyiga qaytadi.
 * - Ichki ro'yxat aylantirilgan bo'lsa (tepada emas) — oddiy aylantirish ishlaydi, oyna yopilmaydi.
 * - Matn maydonlarida ishlamaydi; kompyuterda (960px+) o'chiq.
 */
export function useSwipeToClose(
   panel: Ref<HTMLElement | null>,
   close: () => void,
   /** Oynaning aylanadigan qismi (bo'lmasa — oynaning o'zi) */
   scroller?: () => HTMLElement | null,
) {
   const CLOSE_DISTANCE = 110;
   const CLOSE_VELOCITY = 0.6; // px/ms

   let startY = 0;
   let startTime = 0;
   let offset = 0;
   let armed = false;
   let dragging = false;

   const backdrop = () => panel.value?.previousElementSibling as HTMLElement | null;

   function onStart(event: TouchEvent) {
      if (window.innerWidth >= 960 || event.touches.length !== 1) return;
      const target = event.target as HTMLElement;
      const scroll = scroller?.() ?? panel.value;
      armed = !target.closest("input, textarea, select, [contenteditable]") && (!scroll || scroll.scrollTop <= 0);
      dragging = false;
      offset = 0;
      startY = event.touches[0].clientY;
      startTime = Date.now();
   }

   function onMove(event: TouchEvent) {
      const el = panel.value;
      if (!armed || !el) return;
      const delta = event.touches[0].clientY - startY;

      if (!dragging) {
         // Yuqoriga — bu aylantirish, surish emas
         if (delta < -4) {
            armed = false;
            return;
         }
         if (delta < 8) return;
         dragging = true;
         el.style.transition = "none";
      }

      offset = Math.max(0, delta - 8);
      event.preventDefault();
      el.style.transform = `translateY(${offset}px)`;
      const shade = backdrop();
      if (shade) shade.style.opacity = String(Math.max(0, 1 - offset / el.offsetHeight));
   }

   function onEnd() {
      const el = panel.value;
      if (!dragging || !el) {
         armed = false;
         return;
      }
      dragging = false;
      armed = false;

      const velocity = offset / Math.max(1, Date.now() - startTime);
      el.style.transition = "transform 0.2s cubic-bezier(0.32, 0.72, 0, 1)";
      const shade = backdrop();
      if (shade) shade.style.transition = "opacity 0.2s ease";

      if (offset > CLOSE_DISTANCE || velocity > CLOSE_VELOCITY) {
         // Oxirigacha tushirib, keyin yopamiz (oyna v-if bilan o'chadi — uslublarni tozalash shart emas)
         el.style.transform = "translateY(100%)";
         if (shade) shade.style.opacity = "0";
         setTimeout(close, 160);
      } else {
         el.style.transform = "";
         if (shade) shade.style.opacity = "";
         setTimeout(() => {
            el.style.transition = "";
            if (shade) shade.style.transition = "";
         }, 220);
      }
   }

   function attach(el: HTMLElement) {
      el.addEventListener("touchstart", onStart, { passive: true });
      // preventDefault kerak: surish paytida sahifa/ro'yxat aylanmasin
      el.addEventListener("touchmove", onMove, { passive: false });
      el.addEventListener("touchend", onEnd);
      el.addEventListener("touchcancel", onEnd);
   }

   function detach(el: HTMLElement) {
      el.removeEventListener("touchstart", onStart);
      el.removeEventListener("touchmove", onMove);
      el.removeEventListener("touchend", onEnd);
      el.removeEventListener("touchcancel", onEnd);
   }

   watch(panel, (el, previous) => {
      if (previous) detach(previous);
      if (el) attach(el);
   });

   onBeforeUnmount(() => {
      if (panel.value) detach(panel.value);
   });
}
