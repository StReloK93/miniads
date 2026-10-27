import { isTMA, on, postEvent } from "@tma.js/bridge";
import { useAuth } from "@shared/store/useAuth";
import ProfileRepo from "@shared/entities/Profile/ProfileRepo";

/**
 * Bot foydalanuvchiga faqat ruxsat bergandan (yoki /start bosgandan) keyin yoza oladi.
 * Ruxsat Telegram'ning o'z oynasi bilan so'raladi; allaqachon berilgan bo'lsa oyna chiqmaydi.
 */
export function useBotAccess() {
   const authStore = useAuth();

   function requestFromTelegram(): Promise<boolean> {
      return new Promise((resolve) => {
         const off = on("write_access_requested", ({ status }) => {
            off();
            resolve(status === "allowed");
         });
         postEvent("web_app_request_write_access");
      });
   }

   /** true — bot xabar yubora oladi */
   async function ensureWriteAccess(): Promise<boolean> {
      if (authStore.user?.bot_can_message) return true;
      // Brauzerda so'rab bo'lmaydi — foydalanuvchi botni o'zi ochib /start bosishi kerak
      if (!isTMA()) return false;

      const allowed = await requestFromTelegram();
      if (allowed) {
         const { data } = await ProfileRepo.writeAccess(true);
         authStore.user = data;
      }
      return allowed;
   }

   return { ensureWriteAccess };
}
