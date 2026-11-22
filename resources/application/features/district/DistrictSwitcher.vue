<template>
   <main class="flex items-center justify-between mb-3">
      <BaseModal
         :show-buttons="false"
         :open="isOpen"
         :title="$t('district.choose')"
         :description="$t('district.hint')"
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
                  {{ $t("common.cancel") }}
               </BaseButton>

               <BaseButton @click="emit('confirm')" :loading="loading" type="submit" class="w-full">
                  {{ $t("common.confirm") }}
               </BaseButton>
            </div>
         </Form>
      </BaseModal>
      <aside v-if="AuthStore.user" class="relative">
         <h2 class="mb-0.5 text-(--z-muted-text) text-xs font-medium">{{ AuthStore.user?.name }}</h2>
         <div @click="isOpen = true" class="flex gap-1.5 items-center text-sm font-semibold cursor-pointer">
            <span class="relative flex">
               <!-- Shahar tanlanmagan: belgi "nafas oladi" — bosish mumkinligini ko'rsatadi -->
               <span v-if="showNudge" class="absolute inset-0 rounded-full bg-(--z-primary)/40 animate-ping"></span>
               <MapPin class="relative size-4 text-(--z-primary)" />
            </span>
            <span>{{ AuthStore.user?.active_district?.name || $t("district.all") }}</span>
            <ChevronDown class="size-3.5 text-(--z-muted-text)" />
         </div>

         <!-- Shahar hali tanlanmagan: nima uchun kerakligini qisqa tushuntiradi (yopsa — boshqa chiqmaydi) -->
         <Transition name="nudge">
            <div
               v-if="showNudge"
               class="city-nudge absolute left-0 top-full mt-2 z-30 w-[min(17rem,calc(100vw-5rem))] flex items-start gap-2 rounded-xl bg-(--z-primary) text-white text-xs font-medium leading-snug py-2 pl-3 pr-1.5 shadow-lg cursor-pointer"
               @click="isOpen = true"
            >
               <span class="grow">{{ $t("district.nudge") }}</span>
               <button
                  type="button"
                  class="shrink-0 -my-0.5 p-0.5 rounded-full active:bg-white/20 cursor-pointer"
                  :aria-label="$t('district.nudgeDismiss')"
                  @click.stop="dismissNudge"
               >
                  <X class="size-3.5" />
               </button>
            </div>
         </Transition>
      </aside>
      <aside v-else>
         <h2 class="text-base font-bold">MiniAds</h2>
      </aside>

      <!-- "Qanday ishlaydi?" — har doim ko'rinib turadi, joy egallamaydi -->
      <RouterLink
         :to="{ name: 'help' }"
         class="size-8 shrink-0 rounded-full border border-(--z-border) bg-(--z-card) flex items-center justify-center text-(--z-muted-text) active:bg-(--z-muted)"
         :aria-label="$t('help.title')"
         :title="$t('help.title')"
      >
         <CircleHelp class="size-4.5" />
      </RouterLink>
   </main>
</template>

<script setup lang="ts">
import { ChevronDown, CircleHelp, MapPin, X } from "lucide-vue-next";
import { useAuth } from "@shared/store/useAuth";
import { Form } from "vee-validate";
import { computed, ref, Ref } from "vue";
import { useOnboardingTour } from "@/features/help/useOnboardingTour";
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
   // "Barcha shaharlar"ni ataylab tanlagan bo'lsa ham — eslatma vazifasini bajardi
   dismissNudge();
   const district_id = values.city as number;

   AuthStore.changeDistrict(district_id).finally(() => {
      loading.value = false;
      isOpen.value = false;
      emit("district-changed", district_id);
   });
}
const isOpen: Ref<boolean> = ref(false);

/** Shahar tanlash eslatmasi: shahar tanlanmaguncha, yopilmaguncha va tanishtiruv ochiq bo'lmaganda */
const NUDGE_KEY = "city-nudge-dismissed";
const nudgeDismissed = ref(readDismissed());
const { isOpen: isTourOpen } = useOnboardingTour();
const showNudge = computed(
   () => Boolean(AuthStore.user) && !AuthStore.user?.active_district_id && !nudgeDismissed.value && !isTourOpen.value && !isOpen.value,
);

function readDismissed(): boolean {
   try {
      return localStorage.getItem(NUDGE_KEY) === "1";
   } catch {
      return false;
   }
}

function dismissNudge() {
   nudgeDismissed.value = true;
   try {
      localStorage.setItem(NUDGE_KEY, "1");
   } catch {
      // Xotira yopiq (maxfiy oyna) — shu seans davomida yopiq turadi
   }
}

function onMountedModal() {
   initialValues.value.city = AuthStore.user?.active_district_id ?? 0;
}
</script>

<style scoped>
/* Shahar nomiga qaragan kichik uchburchak */
.city-nudge::before {
   content: "";
   position: absolute;
   top: -0.3rem;
   left: 0.6rem;
   width: 0.65rem;
   height: 0.65rem;
   background: inherit;
   border-radius: 0.125rem;
   transform: rotate(45deg);
}

.nudge-enter-active,
.nudge-leave-active {
   transition:
      opacity 0.2s ease,
      transform 0.2s ease;
}
.nudge-enter-from,
.nudge-leave-to {
   opacity: 0;
   transform: translateY(-4px);
}
</style>
