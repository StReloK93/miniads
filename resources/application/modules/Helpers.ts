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

export function formatPrice(value?: number | null): string {
   if (value === null || value === undefined) return "";
   return value.toLocaleString("ru-RU"); // 1 000 / 40 000
}
