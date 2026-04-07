<template>
   <main v-if="AuthStore.user" class="flex items-center justify-between mb-4">
      <BaseModal
         :show-buttons="false"
         :open="isOpen"
         title="Shaharni tanlang"
         description="Siz quyida tanlagan shahringizga mos e'lonlarni ko'ra olasiz."
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

               <BaseButton @click="$emit('confirm')" :loading="loading" type="submit" class="w-full">
                  Tasdiqlash
               </BaseButton>
            </div>
         </Form>
      </BaseModal>
      <aside>
         <h2 class="mb-0.5 text-(--color-text-secondary)">{{ AuthStore.user?.name }}</h2>
         <div @click="isOpen = true" class="flex gap-2 items-center text-sm">
            <MapPin class="size-4" />
            {{ AuthStore.user?.active_district?.name || "Shaharni tanlang" }}
         </div>
      </aside>
      <aside></aside>
   </main>
</template>

<script setup lang="ts">
import { MapPin } from "lucide-vue-next";

import { useFetchDecorator } from "@shared/composables/useFetch";
import DistrictRepo from "@shared/entities/District/DistrictRepo";
import { useAuth } from "@shared/store/useAuth";
import { IDistrict } from "@shared/types";
import { Form } from "vee-validate";
import { onMounted, ref, Ref } from "vue";

const { data: districts, execute: executeDistricts } = useFetchDecorator<IDistrict[]>(DistrictRepo.index);

executeDistricts();
const AuthStore = useAuth();
const loading = ref(false);

const initialValues = ref({
   city: AuthStore.user?.active_district_id || undefined,
});

function changeDistrict(values: Record<string, unknown>) {
   loading.value = true;
   const district_id = values.city as number;

   AuthStore.changeDistrict(district_id).finally(() => {
      loading.value = false;
      isOpen.value = false;
   });

   // const district = districts.value?.find((d) => d.id === district_id);
   // if (district) {
   //    AuthStore.user.active_district_id = district_id;
   //    AuthStore.user.active_district = district;
   // }
}
const isOpen: Ref<boolean> = ref(false);

function onMountedModal() {
   initialValues.value.city = AuthStore.user?.active_district_id || undefined;
}
</script>
