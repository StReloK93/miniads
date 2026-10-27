import { createApp } from "vue";
import { createPinia } from "pinia";
import { isTMA, retrieveRawInitData, retrieveLaunchParams, postEvent } from "@tma.js/bridge";
import App from "@/App.vue";
import router from "@/router";
import { setupTMAUI } from "@/modules/InitApp";
import { useAuth } from "@shared/store/useAuth";
import { initTheme } from "@shared/composables/useTheme";
import { applyLocale, detectLocale, i18n } from "@shared/i18n";
import "@shared/css/ui.scss";

initTheme();

// Til: tanlangani → Telegram tili (tizim) → brauzer. Birinchi so'rovdan oldin — server ham shu tilda javob bersin
let telegramLanguage: string | undefined;
try {
   telegramLanguage = isTMA() ? retrieveLaunchParams().tgWebAppData?.user?.language_code : undefined;
} catch {
   telegramLanguage = undefined;
}
applyLocale(detectLocale(telegramLanguage));

const app = createApp(App);
app.use(createPinia());
app.use(i18n);
const authStore = useAuth();

const initApp = async () => {
   let userData: ReturnType<typeof retrieveLaunchParams> | null = null;
   let startRoute: Parameters<typeof router.push>[0] | null = null;
   // Bot xabaridan admin panelga (startapp=admin_reported): admin — alohida ilova, kirishdan keyin to'liq o'tiladi
   let startUrl: string | null = null;

   try {
      const tma = isTMA();

      if (tma) {
         postEvent("web_app_request_fullscreen");
         setupTMAUI();
         const initData = retrieveRawInitData();
         const launchParams = retrieveLaunchParams();
         userData = launchParams;
         const startParam = launchParams.tgWebAppStartParam;
         const productStartParam = /^product_(\d+)$/.exec(startParam ?? "");
         const savedSearchParam = /^ss_(\d+)$/.exec(startParam ?? "");
         if (productStartParam) {
            startRoute = { name: "product-id", params: { id: productStartParam[1] } };
         } else if (savedSearchParam) {
            startRoute = { name: "home", query: { saved: savedSearchParam[1] } };
         } else if (startParam === "create") {
            startRoute = { name: "create-select-category" };
         } else if (startParam === "my_ads") {
            startRoute = { name: "my-ads" };
         } else if (startParam === "admin_reported") {
            startUrl = "/admin/products?status=reported";
         }

         try {
            await authStore.signInTelegram(initData);
            router.push({ name: "home" });
         } catch (error) {
            console.error("Telegram Mini App authentication failed.", error);
         }
      } else {
         try {
            await authStore.getUser();
         } catch (error) {
            console.error("Unable to restore the signed-in user.", error);
         }

         const isLocalhost = ["localhost", "127.0.0.1", "::1"].includes(window.location.hostname);

         if (isLocalhost && !authStore.user) {
            try {
               await authStore.testAuth();
               router.push({ name: "home" });
            } catch (error) {
               console.error("Development authentication failed.", error);
            }
         }
      }
   } catch (error) {
      console.error("Application startup initialization failed.", error);
   } finally {
      app.use(router).provide("userData", userData).mount("#app");

      try {
         await router.isReady();

         // Bot havolasi (startapp=product_123 / create): bosh sahifa ustiga ochamiz (replace emas),
         // shunda "orqaga" bosilganda foydalanuvchi bosh sahifaga qaytadi
         if (startUrl && authStore.user?.role === "admin") {
            window.location.href = startUrl;
         } else if (startRoute) {
            await router.push(startRoute);
         }
      } catch (error) {
         console.error("Application initial navigation failed.", error);
      }
   }
};

initApp();
