import { shallowRef } from "vue";
import type { IAiDraftResponse } from "@shared/types";

/** Shundan eski qoralama formaga qo'yilmaydi (foydalanuvchi boshqa ishga o'tib ketgan) */
const MAX_AGE_MS = 30 * 60 * 1000;

const pending = shallowRef<{ response: IAiDraftResponse; at: number } | null>(null);

/**
 * "Matn bilan e'lon" qoralamasi: matn oynasidan forma sahifasiga o'tadi.
 * Faqat xotirada: sahifa yangilansa yo'qoladi; e'lon joylangach yoki foydalanuvchi bekor qilsa tozalanadi.
 */
export function useAiDraft() {
   function current(): IAiDraftResponse | null {
      if (pending.value && Date.now() - pending.value.at > MAX_AGE_MS) {
         pending.value = null;
      }
      return pending.value?.response ?? null;
   }

   return {
      current,
      set: (response: IAiDraftResponse) => (pending.value = { response, at: Date.now() }),
      clear: () => (pending.value = null),
   };
}
