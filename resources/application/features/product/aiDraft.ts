import { shallowRef } from "vue";
import type { IAiDraftResponse } from "@shared/types";

/** Shundan eski qoralama formaga qo'yilmaydi (foydalanuvchi boshqa ishga o'tib ketgan) */
const MAX_AGE_MS = 30 * 60 * 1000;

/** Formadagi rasm maydoni bilan bir xil chegara (FieldImage max) */
export const AI_DRAFT_MAX_IMAGES = 7;

interface PendingDraft {
   response: IAiDraftResponse;
   images: File[];
   /** Vitrina mahsulotidan chiqarilgan e'lon: bog'lanadigan vitrina (AI ishlatilmagan) */
   vitrinaId: number | null;
   at: number;
}

const pending = shallowRef<PendingDraft | null>(null);

/**
 * Formani oldindan to'ldiradigan qoralama: "Matn bilan e'lon" (AI) yoki vitrina mahsulotidan e'lon.
 * Tanlangan rasmlar ham shu yerda — forma ochilganda rasm maydoniga qo'yiladi.
 * Faqat xotirada: sahifa yangilansa yo'qoladi; e'lon joylangach yoki foydalanuvchi bekor qilsa tozalanadi.
 */
export function useAiDraft() {
   function fresh() {
      if (pending.value && Date.now() - pending.value.at > MAX_AGE_MS) {
         pending.value = null;
      }
      return pending.value;
   }

   return {
      current: (): IAiDraftResponse | null => fresh()?.response ?? null,
      images: (): File[] => fresh()?.images ?? [],
      vitrinaId: (): number | null => fresh()?.vitrinaId ?? null,
      set: (response: IAiDraftResponse, images: File[] = [], vitrinaId: number | null = null) =>
         (pending.value = { response, images, vitrinaId, at: Date.now() }),
      clear: () => (pending.value = null),
   };
}
