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

export function timeAgo(date: Date | string | number): string {
   const d = new Date(date);
   const now = new Date();

   // Kunlarni faqat date bo‘yicha solishtiramiz
   const startOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate());
   const startOfDate = new Date(d.getFullYear(), d.getMonth(), d.getDate());

   const dayDiff = Math.floor((startOfToday.getTime() - startOfDate.getTime()) / 86400000);

   // 🔹 BUGUN
   if (dayDiff === 0) {
      const diffMs = now.getTime() - d.getTime();
      const minutes = Math.floor(diffMs / 60000);
      const hours = Math.floor(minutes / 60);

      if (minutes < 1) return "hozir";
      if (minutes < 60) return `${minutes} minut oldin`;
      return `${hours} soat oldin`;
   }

   // 🔹 KECHA
   if (dayDiff === 1) {
      return "kecha";
   }

   // 🔹 2+ KUN
   return `${dayDiff} kun oldin`;
}
