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
            <FieldSelect name="city" :options="cityStore.cities!" value="name" />

            <div class="mt-6 flex gap-4">
               <BaseButton @click="isOpen = false" type="button" severity="glass" class="w-full">
                  Bekor qilish
               </BaseButton>

               <BaseButton @click="emit('confirm')" type="submit" class="w-full"> Tasdiqlash </BaseButton>
            </div>
         </Form>
      </BaseModal>
      <Transition>
         <button
            type="button"
            v-if="cityStore.cities?.length"
            @click="isOpen = true"
            class="text-xs text-(--z-muted-text) hover:text-(--z-foreground) inline-flex items-center gap-1 underline underline-offset-2 cursor-pointer transition-colors"
         >
            <MapPin class="inline-block size-3" />
            <span>{{ selectedCity?.name ?? "Shaharni tanlang" }}</span>
         </button>
      </Transition>
   </main>
</template>

<script setup lang="ts">
import { MapPin } from "lucide-vue-next";
import { Form } from "vee-validate";
import { computed, ref, Ref } from "vue";
import { useCity } from "@shared/entities/District/useCity";
const cityStore = useCity();
const props = defineProps<{
   selectedCityId: number | null;
}>();

const isOpen: Ref<boolean> = ref(false);

const emit = defineEmits<{
   (e: "district-changed", districtId: number): void;
   (e: "confirm"): void;
}>();

const selectedCity = computed(() => {
   const cityId = props.selectedCityId;

   const selectedDistrict = cityStore.cities?.find((d) => d.id === cityId);
   return selectedDistrict ? selectedDistrict : null;
});

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
