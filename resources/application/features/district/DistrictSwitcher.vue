<template>
   <main class="flex items-center justify-between mb-4">
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
            <FieldSelect name="city" :options="cityStore.cities!" value="name" />

            <div class="mt-6 flex gap-4">
               <BaseButton @click="isOpen = false" type="button" severity="glass" class="w-full">
                  Bekor qilish
               </BaseButton>

               <BaseButton @click="emit('confirm')" :loading="loading" type="submit" class="w-full">
                  Tasdiqlash
               </BaseButton>
            </div>
         </Form>
      </BaseModal>
      <aside v-if="AuthStore.user">
         <h2 class="mb-0.5 text-(--z-muted-text) text-xs font-medium">{{ AuthStore.user?.name }}</h2>
         <div @click="isOpen = true" class="flex gap-1.5 items-center text-sm font-semibold cursor-pointer">
            <MapPin class="size-4 text-(--z-primary)" />
            <span>{{ AuthStore.user?.active_district?.name || "Barcha shaharlar" }}</span>
         </div>
      </aside>
      <aside v-else>
         <h2 class="text-base font-bold">MiniAds</h2>
      </aside>
   </main>
</template>

<script setup lang="ts">
import { MapPin } from "lucide-vue-next";
import { useAuth } from "@shared/store/useAuth";
import { Form } from "vee-validate";
import { ref, Ref } from "vue";
import { useCity } from "@shared/entities/District/useCity";
const cityStore = useCity();

const emit = defineEmits<{
   (e: "district-changed", districtId: number): void;
   (e: "confirm"): void;
}>();

const AuthStore = useAuth();
const loading = ref(false);

const initialValues = ref({
   city: AuthStore.user?.active_district_id ?? 0,
});

function changeDistrict(values: Record<string, unknown>) {
   loading.value = true;
   const district_id = values.city as number;

   AuthStore.changeDistrict(district_id).finally(() => {
      loading.value = false;
      isOpen.value = false;
      emit("district-changed", district_id);
   });
}
const isOpen: Ref<boolean> = ref(false);

function onMountedModal() {
   initialValues.value.city = AuthStore.user?.active_district_id ?? 0;
}
</script>
