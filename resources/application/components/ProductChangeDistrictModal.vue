<template>
   <main>
      <BaseModal
         :show-buttons="false"
         :open="isOpen"
         title="Shaharni tanlang"
         description="Asosiy sahifada sizga faqat tanlangan shahar bo'yicha e'lonlar ko'rsatiladi."
         confirm-text="Saqlash"
         cancel-text="Yopish"
         :danger="true"
         @close="isOpen = false"
      >
         <template #icon>
            <MapPin class="size-4" />
         </template>
         <Form @submit="changeDistrict" :initial-values="initialValues" @vue:unmounted="onMountedModal">
            <FieldSelect name="city" :options="districts!" value="name" />

            <div class="mt-6 flex gap-4">
               <BaseButton @click="isOpen = false" type="button" severity="glass" class="w-full">
                  Bekor qilish
               </BaseButton>

               <BaseButton @click="emit('confirm')" type="submit" class="w-full"> Tasdiqlash </BaseButton>
            </div>
         </Form>
      </BaseModal>
      <Transition>
         <h3
            v-if="props.selectedCityId !== null && districts"
            @click="isOpen = true"
            class="text-xs text-(--z-muted-text) inline-flex items-center underline"
         >
            <MapPin class="inline-block size-3 mr-1" /> {{ selectedCity?.name }}
         </h3>
      </Transition>
   </main>
</template>

<script setup lang="ts">
import { MapPin } from "lucide-vue-next";
import { useFetchDecorator } from "@shared/composables/useFetch";
import DistrictRepo from "@shared/entities/District/DistrictRepo";
import { IDistrict } from "@shared/types";
import { Form } from "vee-validate";
import { computed, ref, Ref } from "vue";

const props = defineProps<{
   selectedCityId: number | null;
}>();

const isOpen: Ref<boolean> = ref(false);

const { data: districts, execute: executeDistricts } = useFetchDecorator<IDistrict[]>(DistrictRepo.index);

const emit = defineEmits<{
   (e: "district-changed", districtId: number): void;
   (e: "confirm"): void;
}>();

const selectedCity = computed(() => {
   const cityId = props.selectedCityId;

   const selectedDistrict = districts.value?.find((d) => d.id === cityId);
   return selectedDistrict ? selectedDistrict : null;
});

executeDistricts();

const initialValues = ref<Record<string, unknown>>({
   city: props.selectedCityId,
});

function changeDistrict(values: Record<string, unknown>) {
   const district_id = values.city as number;

   emit("district-changed", district_id);
   isOpen.value = false;
}

function onMountedModal() {
   initialValues.value.city = props.selectedCityId;
}
</script>
