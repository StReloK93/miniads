import { ref } from "vue";

/** Bir marta ko'rsatiladi; matn o'zgarsa kalit versiyasini oshiring */
const STORAGE_KEY = "onboarding-seen:v2";

// Bitta umumiy holat: tanishtiruv App darajasida turadi, istalgan sahifa uni ocha oladi
const isOpen = ref(false);

function wasSeen(): boolean {
   try {
      return localStorage.getItem(STORAGE_KEY) === "1";
   } catch {
      return false;
   }
}

function markSeen() {
   try {
      localStorage.setItem(STORAGE_KEY, "1");
   } catch {
      // Xotira yopiq bo'lsa (maxfiy oyna) — keyingi safar yana ko'rsatiladi, xolos
   }
}

export function useOnboardingTour() {
   return {
      isOpen,
      /** Yordam sahifasidan: istalgan vaqtda qayta ko'rish */
      open: () => (isOpen.value = true),
      /** Bosh sahifada: faqat hali ko'rmagan foydalanuvchiga */
      openIfNew: () => {
         if (!wasSeen()) isOpen.value = true;
      },
      close: () => {
         markSeen();
         isOpen.value = false;
      },
   };
}
