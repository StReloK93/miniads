import { defineStore } from "pinia";
import DistrictRepo from "./DistrictRepo";
import { IDistrict } from "@shared/types";
import { ref } from "vue";

export const useCity = defineStore("useCity", () => {
   const cities = ref<IDistrict[] | null>(null);

   async function getCities() {
      const { data } = await DistrictRepo.index();
      cities.value = data;
   }

   return { cities, getCities };
});
