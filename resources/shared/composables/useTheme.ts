import { ref } from "vue";
import { isTMA, postEvent } from "@tma.js/bridge";

export type ThemeMode = "light" | "dark" | "system";

const THEME_KEY = "theme-mode";

export const currentTheme = ref<ThemeMode>("light");
export const isDark = ref<boolean>(false);

function resolveSystemDark(): boolean {
   return typeof window !== "undefined" && window.matchMedia("(prefers-color-scheme: dark)").matches;
}

function updateDOM(dark: boolean): void {
   if (typeof document === "undefined") return;

   const html = document.documentElement;
   if (dark) {
      html.classList.add("dark");
   } else {
      html.classList.remove("dark");
   }

   // Telegram Mini App header va fon rangini sinxronlash
   if (isTMA()) {
      try {
         const color = dark ? "#0f172a" : "#f8fafc";
         postEvent("web_app_set_header_color", { color });
         postEvent("web_app_set_background_color", { color });
      } catch (e) {
         // Agar Telegram metodi qo'llab-quvvatlanmasa e'tiborsiz qoldiramiz
      }
   }
}

export function applyTheme(mode: ThemeMode): void {
   currentTheme.value = mode;

   let dark = false;
   if (mode === "dark") {
      dark = true;
   } else if (mode === "light") {
      dark = false;
   } else if (mode === "system") {
      dark = resolveSystemDark();
   }

   isDark.value = dark;
   updateDOM(dark);

   try {
      localStorage.setItem(THEME_KEY, mode);
   } catch (e) {
      // localStorage xatolarini ushlash
   }
}

export function toggleTheme(): void {
   const next = isDark.value ? "light" : "dark";
   applyTheme(next);
}

let systemListenerAttached = false;

export function initTheme(): ThemeMode {
   let saved: ThemeMode = "light";
   try {
      const stored = localStorage.getItem(THEME_KEY) as ThemeMode | null;
      if (stored === "light" || stored === "dark" || stored === "system") {
         saved = stored;
      } else {
         // Agar oldin hech narsa saqlanmagan bo'lsa, "light" qilib saqlaymiz
         saved = "light";
      }
   } catch (e) {
      saved = "light";
   }

   applyTheme(saved);

   // Tizim o'zgarsa tinglash (faqat bir marta)
   if (!systemListenerAttached && typeof window !== "undefined") {
      window.matchMedia("(prefers-color-scheme: dark)").addEventListener("change", (e: MediaQueryListEvent) => {
         if (currentTheme.value === "system") {
            isDark.value = e.matches;
            updateDOM(e.matches);
         }
      });
      systemListenerAttached = true;
   }

   return saved;
}

export function useTheme() {
   return {
      theme: currentTheme,
      isDark,
      setTheme: applyTheme,
      toggleTheme,
   };
}
