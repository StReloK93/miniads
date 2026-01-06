import { defineStore } from "pinia";
import { ref, Ref } from "vue";
import router from "@/router";
import { api } from "@/modules/useFetch";
export const useAuthStore = defineStore("Auth", () => {
   const user: Ref = ref(null);
   const token: Ref = ref(null);
   const safe_area = ref();
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
            await getUser();
            router.push({ name: "main" });
         });
   }

   async function getUser() {
      api.defaults.headers.common["Authorization"] = localStorage.getItem("token");
      user.value = await api.get("user").then((result) => result.data);
   }

   // async function logout() {
   //    const data = await AxiosClient.get("logout");

   //    if (data.status == 200) {
   //       AxiosClient.defaults.headers.common["Authorization"] = null;
   //       localStorage.removeItem("token");
   //       user.value = null;
   //       router.push({ name: "login" });
   //    }
   // }

   return {
      user,
      token,
      safe_area,
      getUser,
      signInTelegram,
   };
});
