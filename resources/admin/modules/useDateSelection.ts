import { ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import type { DateSelection } from "@admin/components/AdminDateBar.vue";
import { isValidKey, todayKey } from "@admin/modules/dates";

/**
 * Tanlangan kun URL'da (?date= yoki ?days=) saqlanadi: sahifa yangilansa, orqaga qaytilsa
 * yoki havola yuborilsa — o'sha kun ochiladi.
 */
export function useDateSelection(fallback: DateSelection = { date: todayKey(), days: null }) {
   const route = useRoute();
   const router = useRouter();

   const days = Number(route.query.days);
   const initial: DateSelection = isValidKey(route.query.date)
      ? { date: route.query.date, days: null }
      : [7, 30].includes(days)
        ? { date: null, days }
        : fallback;

   const selection = ref<DateSelection>(initial);

   watch(selection, (value) => {
      router.replace({
         query: {
            ...route.query,
            date: value.date ?? undefined,
            days: value.days ? String(value.days) : undefined,
         },
      });
   });

   /** API parametrlari */
   const params = () => (selection.value.date ? { date: selection.value.date } : { days: selection.value.days ?? 7 });

   return { selection, params };
}
