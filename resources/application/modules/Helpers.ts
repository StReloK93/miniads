export { formatPrice, timeAgo, formatDate } from "@shared/modules/formatters";

export const preloadImages = (urls: string[]) => {
   const promises = urls.map((url) => {
      return new Promise((resolve) => {
         const img = new Image();
         img.src = url;
         img.onload = resolve; // Rasm muvaffaqiyatli yuklansa
         img.onerror = resolve; // Xatolik bo'lsa ham loading to'xtab qolmasligi uchun
      });
   });
   return Promise.all(promises);
};

export function buildBreadcrumb(category) {
   const items = <any>[];

   let current = category;

   while (current) {
      items.push({
         id: current.id,
         name: current.name,
      });

      current = current.parent;
   }

   return items.reverse(); // yuqoridan pastga
}

export function centerElement(target: HTMLElement, parentScroll: HTMLElement) {
   if (!target) return;
   const rect = target.getBoundingClientRect();
   const offset = rect.top - window.innerHeight / 2 + rect.height / 2;

   setTimeout(() => {
      parentScroll.scrollBy({
         top: offset,
         behavior: "smooth",
      });
   }, 400);
}
