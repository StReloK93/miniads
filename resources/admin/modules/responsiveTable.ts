import type { Directive } from "vue";

/**
 * v-responsive-table: har bir katakka ustun sarlavhasini `data-label` qilib yozadi.
 * Telefonda CSS jadval qatorlarini kartaga aylantiradi va yorliqni katak oldida ko'rsatadi
 * (adminApp.css → "MOBIL JADVAL"). Qatorlar o'zgarsa (sahifalash, qidiruv) yorliqlar qayta yoziladi.
 */
function applyLabels(table: HTMLTableElement) {
   const headers = [...table.querySelectorAll("thead th")].map((th) => th.textContent?.trim() ?? "");

   table.querySelectorAll("tbody tr").forEach((row) => {
      [...row.children].forEach((cell, index) => {
         const label = cell.hasAttribute("colspan") ? "" : headers[index] ?? "";
         if (cell.getAttribute("data-label") !== label) cell.setAttribute("data-label", label);
      });
   });
}

const observers = new WeakMap<HTMLTableElement, MutationObserver>();

export const responsiveTable: Directive<HTMLTableElement> = {
   mounted(table) {
      applyLabels(table);
      const observer = new MutationObserver(() => applyLabels(table));
      observer.observe(table, { childList: true, subtree: true });
      observers.set(table, observer);
   },
   unmounted(table) {
      observers.get(table)?.disconnect();
      observers.delete(table);
   },
};
