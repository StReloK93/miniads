import router from "@/router";
import { useAuth } from "@shared/store/useAuth";

/**
 * Login talab qiladigan amaldan oldin chaqiriladi (sevimlilar, e'lon joylash).
 * Mehmon bo'lsa login sahifasiga yuboradi va false qaytaradi.
 */
export function requireAuth(): boolean {
   if (useAuth().user) return true;

   router.push({ name: "login", query: { redirect: router.currentRoute.value.fullPath } });
   return false;
}
