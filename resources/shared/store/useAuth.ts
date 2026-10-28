import { defineStore } from "pinia";
import { ref, Ref } from "vue";
import { api } from "@shared/composables/useFetch";
import { IUser } from "@shared/types";

const TOKEN_KEY = "token";

interface IAuthResponse {
   type: string;
   token: string;
   user: IUser;
}

// Store faqat sessiyani boshqaradi. Login/logoutdan keyin qayerga o'tishni chaqiruvchi (sahifa yoki app.ts) hal qiladi,
// shuning uchun bu fayl ikkala ilovada (app va admin) bir xil ishlaydi.
export const useAuth = defineStore("useAuth", () => {
   const user: Ref<IUser | null> = ref(null);
   const token: Ref<string | null> = ref(null);
   // Telegram ichida: token bekor bo'lsa, shu initData bilan jimgina qayta kiramiz
   let telegramInitData: string | null = null;
   let reauth: Promise<void> | null = null;

   function setSession(result: IAuthResponse) {
      const value = `${result.type} ${result.token}`;
      localStorage.setItem(TOKEN_KEY, value);
      api.defaults.headers.common["Authorization"] = value;
      token.value = value;
      user.value = result.user;
   }

   function clearSession() {
      localStorage.removeItem(TOKEN_KEY);
      delete api.defaults.headers.common["Authorization"];
      token.value = null;
      user.value = null;
   }

   // Token eskirgan yoki bekor qilingan bo'lsa: Telegram ichida qayta kirib so'rovni takrorlaymiz,
   // bo'lmasa — mehmon rejimiga o'tamiz
   api.interceptors.response.use(
      (response) => response,
      async (error) => {
         const config = error.config;
         if (error.response?.status === 401 && token.value) {
            if (telegramInitData && config && !config._retried && !String(config.url).includes("telegram/sign-in")) {
               config._retried = true;
               try {
                  reauth ??= signInTelegram(telegramInitData).finally(() => (reauth = null));
                  await reauth;
                  config.headers.Authorization = token.value;
                  return api(config);
               } catch {
                  // initData ham eskirgan — quyida mehmon rejimiga o'tamiz
               }
            }
            clearSession();
         }
         return Promise.reject(error);
      },
   );

   async function testAuth() {
      await api.post("test-auth").then(async (result) => {
         setSession(result.data);
      });
   }

   async function changeDistrict(district_id: number) {
      const { data } = await api.post("user/change-district", { district_id });
      user.value = data;
   }

   async function signInTelegram(initDataRaw) {
      await api
         .post(
            "telegram/sign-in",
            {},
            {
               headers: { Authorization: `tma ${initDataRaw}` },
            },
         )
         .then(async (result) => {
            setSession(result.data);
            telegramInitData = initDataRaw;
         });
   }

   async function getUser() {
      const saved = localStorage.getItem(TOKEN_KEY);
      // Token yo'q — mehmon, serverga so'rov yuborish shart emas
      if (!saved) return;

      api.defaults.headers.common["Authorization"] = saved;
      token.value = saved;
      user.value = await api.get("user").then((result) => result.data);
   }

   // Brauzerdagi Telegram login. Sahifaga yo'naltirishni chaqiruvchi o'zi hal qiladi.
   async function widgetTelegramAuth(telegramUser) {
      const result = await api.post("telegram/widget-sign-in", telegramUser);
      setSession(result.data);
   }

   async function logout() {
      try {
         await api.post("logout");
      } finally {
         clearSession();
      }
   }

   return {
      user,
      token,
      testAuth,
      getUser,
      widgetTelegramAuth,
      signInTelegram,
      logout,
      changeDistrict,
   };
});
