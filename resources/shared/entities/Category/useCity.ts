import { defineStore } from "pinia";
import DistrictRepo from "../District/DistrictRepo";
import { ref } from "vue";
export const useCity = defineStore("useCity", () => {
   const cities = ref();

   async function getCities() {
      const { data: citys } = await DistrictRepo.index();
      cities.value = citys;
   }

   return { cities, getCities };
});
