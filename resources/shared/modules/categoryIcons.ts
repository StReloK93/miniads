import {
   Briefcase,
   Car,
   Home,
   Smartphone,
   Wrench,
   Shirt,
   GraduationCap,
   Building2,
   ShoppingBag,
   Heart,
   Sparkles,
   Tag,
} from "lucide-vue-next";
import type { Component } from "vue";

export interface CategoryVisual {
   icon: Component;
   bgClass: string;
   iconClass: string;
}

export function getCategoryVisual(name?: string): CategoryVisual {
   if (!name) {
      return {
         icon: Tag,
         bgClass: "bg-slate-500/10 text-slate-600 dark:text-slate-400",
         iconClass: "text-slate-600 dark:text-slate-400",
      };
   }

   const lower = name.toLowerCase();

   // Ish o'rinlari va vakansiyalar
   if (lower.includes("ish") || lower.includes("vakansiya") || lower.includes("xodim") || lower.includes("ishchi")) {
      return {
         icon: Briefcase,
         bgClass: "bg-blue-500/10 text-blue-600 dark:text-blue-400",
         iconClass: "text-blue-600 dark:text-blue-400",
      };
   }

   // Avto va transport
   if (lower.includes("avto") || lower.includes("mashina") || lower.includes("transport") || lower.includes("mator")) {
      return {
         icon: Car,
         bgClass: "bg-amber-500/10 text-amber-600 dark:text-amber-400",
         iconClass: "text-amber-600 dark:text-amber-400",
      };
   }

   // Ko'chmas mulk, uy, ijara
   if (
      lower.includes("uy") ||
      lower.includes("ijara") ||
      lower.includes("kvartira") ||
      lower.includes("hovli") ||
      lower.includes("ko'chmas") ||
      lower.includes("yer")
   ) {
      return {
         icon: Home,
         bgClass: "bg-emerald-500/10 text-emerald-600 dark:text-emerald-400",
         iconClass: "text-emerald-600 dark:text-emerald-400",
      };
   }

   // Telefon, maishiy texnika, elektronika
   if (
      lower.includes("telefon") ||
      lower.includes("elektronika") ||
      lower.includes("kompyuter") ||
      lower.includes("texnika") ||
      lower.includes("smart")
   ) {
      return {
         icon: Smartphone,
         bgClass: "bg-indigo-500/10 text-indigo-600 dark:text-indigo-400",
         iconClass: "text-indigo-600 dark:text-indigo-400",
      };
   }

   // Xizmatlar va ustalar
   if (
      lower.includes("xizmat") ||
      lower.includes("ta'mir") ||
      lower.includes("usta") ||
      lower.includes("massaj") ||
      lower.includes("montaj")
   ) {
      return {
         icon: Wrench,
         bgClass: "bg-violet-500/10 text-violet-600 dark:text-violet-400",
         iconClass: "text-violet-600 dark:text-violet-400",
      };
   }

   // Kiyim-kechak va poyabzal
   if (lower.includes("kiyim") || lower.includes("poyabzal") || lower.includes("moda") || lower.includes("libos")) {
      return {
         icon: Shirt,
         bgClass: "bg-rose-500/10 text-rose-600 dark:text-rose-400",
         iconClass: "text-rose-600 dark:text-rose-400",
      };
   }

   // Ta'lim va kurslar
   if (lower.includes("ta'lim") || lower.includes("kurs") || lower.includes("repetitor") || lower.includes("o'qish")) {
      return {
         icon: GraduationCap,
         bgClass: "bg-cyan-500/10 text-cyan-600 dark:text-cyan-400",
         iconClass: "text-cyan-600 dark:text-cyan-400",
      };
   }

   // Biznes va savdo
   if (lower.includes("biznes") || lower.includes("magazin") || lower.includes("do'kon") || lower.includes("ofis")) {
      return {
         icon: Building2,
         bgClass: "bg-orange-500/10 text-orange-600 dark:text-orange-400",
         iconClass: "text-orange-600 dark:text-orange-400",
      };
   }

   // Salomatlik va go'zallik
   if (lower.includes("salomatlik") || lower.includes("go'zallik") || lower.includes("parvarish")) {
      return {
         icon: Heart,
         bgClass: "bg-pink-500/10 text-pink-600 dark:text-pink-400",
         iconClass: "text-pink-600 dark:text-pink-400",
      };
   }

   // Standart
   return {
      icon: Sparkles,
      bgClass: "bg-slate-500/10 text-slate-600 dark:text-slate-400",
      iconClass: "text-slate-600 dark:text-slate-400",
   };
}
