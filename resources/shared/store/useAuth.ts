import { defineStore } from "pinia";
import { ref, Ref } from "vue";
import router from "@/router";
import { api } from "@shared/composables/useFetch";

const TOKEN_KEY = "token";

export const useAuth = defineStore("useAuth", () => {
   const user: Ref = ref(null);
   const token: Ref = ref(null);

   function setSession(result: { type: string; token: string; user: any }) {
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

   // Token eskirgan yoki bekor qilingan bo'lsa — mehmon rejimiga o'tamiz
   api.interceptors.response.use(
      (response) => response,
      (error) => {
         if (error.response?.status === 401 && token.value) {
            clearSession();
         }
         return Promise.reject(error);
      },
   );

   async function testAuth() {
      await api.post("test-auth").then(async (result) => {
         setSession(result.data);
         router.push({ name: "home" });
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
            router.push({ name: "home" });
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
