import { defineStore } from "pinia";
import { ref, Ref } from "vue";
import router from "@/router";
import { api } from "@shared/composables/useFetch";
export const useAuth = defineStore("useAuth", () => {
   const user: Ref = ref(null);
   const token: Ref = ref(null);

   async function testAuth() {
      await api.post("test-auth").then(async (result) => {
         localStorage.setItem("token", `${result.data.type} ${result.data.token}`);

         api.defaults.headers.common["Authorization"] = localStorage.getItem("token");

         token.value = localStorage.getItem("token");
         user.value = result.data.user;

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
            localStorage.setItem("token", `${result.data.type} ${result.data.token}`);

            api.defaults.headers.common["Authorization"] = localStorage.getItem("token");

            token.value = localStorage.getItem("token");
            user.value = result.data.user;

            router.push({ name: "home" });
         });
   }

   async function getUser() {
      api.defaults.headers.common["Authorization"] = localStorage.getItem("token");
      user.value = await api.get("user").then((result) => result.data);
   }

   async function widgetTelegramAuth(user) {
      await api
         .post("telegram/widget-sign-in", user)
         .then(async (result) => {
            localStorage.setItem("token", `${result.data.type} ${result.data.token}`);

            api.defaults.headers.common["Authorization"] = localStorage.getItem("token");

            token.value = localStorage.getItem("token");
            user.value = result.data.user;

            router.push({ name: "home" });
         })
         .catch((err) => {
            console.error("Widget login xatosi:", err);
         });
   }

   async function logout() {
      const data = await api.get("logout");

      if (data.status == 200) {
         api.defaults.headers.common["Authorization"] = null;
         localStorage.removeItem("token");
         user.value = null;
         router.push({ name: "login" });
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
