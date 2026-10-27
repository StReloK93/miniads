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
   /** Rasmsiz e'lon plitkasi */
   tile: string;
}

/*
   Barcha bo'limlar bitta, mavzuga mos rangda: bo'limni belgining shakli ajratadi, rang emas.
   Rang-barang belgilar lentani "o'yinchoq"day ko'rsatardi va e'lon rasmlari bilan raqobatlashardi.
*/
const TEXT = "text-(--z-foreground)/65";
const TILE = "bg-linear-to-br from-(--z-muted) to-(--z-muted)/40";

/**
 * E'lon kategoriyasining belgisi asosiy bo'lim bo'yicha (category.root_id):
 * "Sotuv" yoki "Kvartira" nomidan taxmin qilish shart emas — hammasi "Uy-joy" belgisida.
 */
export function useCategoryTheme() {
   const store = useCategory();

   return (category?: ICategory | null): CategoryTheme => {
      const root = (store.parentCategories as ICategory[] | undefined)?.find(
         (item) => item.id === (category?.root_id ?? category?.id),
      );

      return {
         icon: (icons as Record<string, Component>)[root?.image ?? ""] ?? Tag,
         rootName: root?.name ?? null,
         text: TEXT,
         tile: TILE,
      };
   };
}
