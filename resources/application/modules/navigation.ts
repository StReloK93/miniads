import type { RouteLocationRaw, Router } from "vue-router";

/**
 * Ilova ichida orqaga qaytadigan sahifa bormi.
 * window.history.length ishonchsiz (Telegram WebView ilovadan oldingi yozuvlarni ham sanaydi),
 * vue-router esa har bir yozuvga oldingi manzilni `state.back` qilib yozadi.
 */
export function canGoBack(): boolean {
   return Boolean(window.history.state?.back);
}

/**
 * Orqaga qaytadi; tarix bo'lmasa (masalan bot havolasi orqali to'g'ridan-to'g'ri e'lon ochilgan) — bosh sahifaga.
 */
export function goBack(router: Router, fallback: RouteLocationRaw = { name: "home" }) {
   if (canGoBack()) {
      router.back();
   } else {
      router.replace(fallback);
   }
}

/**
 * E'lon joylash bosqichlari. E'lon joylangandan keyin ular tarixdan olib tashlanadi,
 * shunda "orqaga" bosilganda to'ldirilgan forma qayta ochilmaydi.
 */
const CREATE_FLOW_ROUTES = new Set(["create-select-city", "create-select-category", "create-product"]);

/** Bosqichlarning birinchisi tarixning qaysi o'rnida turibdi (vue-router `state.position`) */
let createFlowStart: number | null = null;

function isCreateFlowRoute(name: unknown): boolean {
   return CREATE_FLOW_ROUTES.has(String(name));
}

export function trackCreateFlow(router: Router) {
   router.afterEach((to, from, failure) => {
      if (failure) return;
      if (!isCreateFlowRoute(to.name)) {
         createFlowStart = null;
         return;
      }
      // Bosqichlarga tashqaridan kirildi — boshlanish nuqtasi shu yozuv
      if (createFlowStart === null || !isCreateFlowRoute(from.name)) {
         const position = window.history.state?.position;
         createFlowStart = typeof position === "number" ? position : null;
      }
   });
}

/** Tarixda `delta` qadam yuradi va navigatsiya tugashini kutadi */
function historyGo(router: Router, delta: number): Promise<void> {
   return new Promise((resolve) => {
      let done = false;
      const finish = () => {
         if (done) return;
         done = true;
         removeHook();
         resolve();
      };
      const removeHook = router.afterEach(finish);
      // WebView popstate yubormasa ham osilib qolmaslik uchun
      setTimeout(finish, 1000);
      router.go(delta);
   });
}

/**
 * E'lon joylash bosqichlaridan chiqadi: ularni tarixdan olib tashlab, `target`ga o'tadi.
 * Natija: [bosh sahifa, e'lon] — e'londan "orqaga" bosilsa forma emas, kirishdan oldingi sahifa ochiladi.
 */
export async function leaveCreateFlow(router: Router, target: RouteLocationRaw) {
   const start = createFlowStart;
   const position = window.history.state?.position;
   if (start === null || typeof position !== "number" || position < start) {
      await router.push(target);
      return;
   }
   createFlowStart = null;

   // Bosqichlardan oldingi yozuvga qaytamiz; ilova to'g'ridan-to'g'ri forma bilan ochilgan bo'lsa — birinchi bosqichga
   const landing = Math.max(start - 1, 0);
   if (position > landing) await historyGo(router, landing - position);

   if (start === 0) {
      await router.replace(target);
   } else {
      await router.push(target);
   }
}

/**
 * Ochiq oynalar (rasm preview, modal) uchun "orqaga" ushlagichlari.
 * Telegram'ning orqaga tugmasi avval eng oxirgi ochilgan oynani yopadi, sahifani emas.
 */
const backHandlers: (() => void)[] = [];

export function pushBackHandler(handler: () => void): () => void {
   backHandlers.push(handler);
   return () => {
      const index = backHandlers.lastIndexOf(handler);
      if (index >= 0) backHandlers.splice(index, 1);
   };
}

/** Ushlagich bo'lsa uni bajaradi va true qaytaradi */
export function runBackHandler(): boolean {
   const handler = backHandlers[backHandlers.length - 1];
   if (!handler) return false;
   handler();
   return true;
}
