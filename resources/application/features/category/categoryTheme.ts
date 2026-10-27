import type { Component } from "vue";
import { Tag } from "lucide-vue-next";
import icons from "@/features/category/icons";
import { useCategory } from "@shared/entities/Category/useCategory";
import type { ICategory } from "@shared/types";

export interface CategoryTheme {
   icon: Component;
   /** Asosiy bo'lim nomi (Uy-joy, Avto...) */
   rootName: string | null;
   /** Ikonka/yozuv rangi */
   text: string;
   /** Rasmsiz e'lon plitkasi: yumshoq rangli gradient */
   tile: string;
}

// Kalit — asosiy bo'limning ikonkasi (categories.image). Tailwind klasslari to'liq yozilishi shart
const THEMES: Record<string, Pick<CategoryTheme, "text" | "tile">> = {
   House: {
      text: "text-emerald-600 dark:text-emerald-400",
      tile: "bg-linear-to-br from-emerald-400/30 to-teal-400/10",
   },
   MonitorSmartphone: {
      text: "text-indigo-600 dark:text-indigo-400",
      tile: "bg-linear-to-br from-indigo-400/30 to-sky-400/10",
   },
   Car: {
      text: "text-amber-600 dark:text-amber-400",
      tile: "bg-linear-to-br from-amber-400/30 to-orange-400/10",
   },
   BriefcaseBusiness: {
      text: "text-blue-600 dark:text-blue-400",
      tile: "bg-linear-to-br from-blue-400/30 to-cyan-400/10",
   },
   Wrench: {
      text: "text-violet-600 dark:text-violet-400",
      tile: "bg-linear-to-br from-violet-400/30 to-fuchsia-400/10",
   },
   Armchair: {
      text: "text-orange-600 dark:text-orange-400",
      tile: "bg-linear-to-br from-orange-400/30 to-amber-400/10",
   },
   GraduationCap: {
      text: "text-cyan-600 dark:text-cyan-400",
      tile: "bg-linear-to-br from-cyan-400/30 to-sky-400/10",
   },
   Cat: {
      text: "text-lime-600 dark:text-lime-400",
      tile: "bg-linear-to-br from-lime-400/30 to-emerald-400/10",
   },
   Shirt: {
      text: "text-pink-600 dark:text-pink-400",
      tile: "bg-linear-to-br from-pink-400/30 to-rose-400/10",
   },
   Megaphone: {
      text: "text-sky-600 dark:text-sky-400",
      tile: "bg-linear-to-br from-sky-400/30 to-indigo-400/10",
   },
   Gift: {
      text: "text-fuchsia-600 dark:text-fuchsia-400",
      tile: "bg-linear-to-br from-fuchsia-400/30 to-pink-400/10",
   },
};

const FALLBACK: Pick<CategoryTheme, "text" | "tile"> = {
   text: "text-slate-600 dark:text-slate-400",
   tile: "bg-linear-to-br from-slate-400/25 to-slate-400/5",
};

/**
 * E'lon kategoriyasining ko'rinishi asosiy bo'lim bo'yicha (category.root_id):
 * "Sotuv" yoki "Kvartira" nomidan taxmin qilish shart emas — hammasi "Uy-joy" rangida.
 */
export function useCategoryTheme() {
   const store = useCategory();

   return (category?: ICategory | null): CategoryTheme => {
      const root = (store.parentCategories as ICategory[] | undefined)?.find(
         (item) => item.id === (category?.root_id ?? category?.id),
      );
      const iconName = root?.image ?? "";

      return {
         icon: (icons as Record<string, Component>)[iconName] ?? Tag,
         rootName: root?.name ?? null,
         ...(THEMES[iconName] ?? FALLBACK),
      };
   };
}
