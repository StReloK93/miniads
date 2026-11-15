<template>
   <NavigationPageDecorator :header-class="['border-b', 'border-(--z-border)']">
      <template #header>
         <div class="flex items-center gap-2 mb-1">
            <button type="button" class="-ml-1.5 p-1.5 rounded-full active:bg-(--z-muted)" :aria-label="$t('common.back')" @click="goBack(router)">
               <ArrowLeft class="size-5" />
            </button>
            <h3 class="font-bold text-xl">{{ $t("vitrina.title") }}</h3>
         </div>
         <p class="mb-3 text-sm text-(--z-muted-text)">
            {{ cityName ? $t("vitrina.inCity", { city: cityName }) : $t("vitrina.subtitle") }}
         </p>
         <!-- Turi bo'yicha: do'konlar, menyular, ustalar... -->
         <nav class="type-chips pb-3">
            <button type="button" class="type-chip" :class="{ 'is-active': !type }" @click="type = null">
               {{ $t("common.all") }}
            </button>
            <button
               v-for="key in VITRINA_TYPE_KEYS"
               :key="key"
               type="button"
               class="type-chip"
               :class="{ 'is-active': type === key }"
               @click="type = key"
            >
               <component :is="VITRINA_TYPES[key].icon" class="size-3.5" />
               {{ $t(`vitrina.typesPlural.${key}`) }}
            </button>
         </nav>
      </template>

      <template #content>
         <div v-if="isLoading && !vitrinas.length" class="flex flex-col gap-2.5">
            <div v-for="n in 4" :key="n" class="skeleton h-22"></div>
         </div>

         <div v-else-if="vitrinas.length" class="flex flex-col gap-2.5" :class="{ 'opacity-60': isLoading }">
            <RouterLink
               v-for="vitrina in vitrinas"
               :key="vitrina.id"
               :to="{ name: 'vitrina', params: { slug: vitrina.slug } }"
               class="vitrina-card"
            >
               <VitrinaAvatar :vitrina="vitrina" class="size-14 text-lg" />
               <span class="min-w-0 grow">
                  <span class="flex items-center gap-1 font-semibold text-[0.9375rem] leading-tight">
                     <span class="truncate">{{ vitrina.name }}</span>
                     <VitrinaVerified v-if="vitrina.is_verified" class="size-4" />
                  </span>
                  <span class="block mt-0.5 text-xs text-(--z-muted-text) truncate">
                     {{ $t(`vitrina.types.${vitrina.type}`) }} · {{ vitrina.district?.name || $t("district.region") }}
                     <template v-if="vitrina.items_count"> · {{ $t("vitrina.itemsCount", vitrina.items_count) }}</template>
                  </span>
                  <span v-if="vitrina.description" class="block mt-1 text-xs leading-snug line-clamp-2">{{ vitrina.description }}</span>
               </span>
               <ChevronRight class="size-4 shrink-0 text-(--z-muted-text)" />
            </RouterLink>
         </div>

         <div v-else class="py-14 flex flex-col items-center text-center">
            <Store class="size-10 text-(--z-muted-text) mb-3" />
            <p class="text-sm font-medium">{{ $t("vitrina.empty") }}</p>
         </div>

         <!-- O'z vitrinangizni oching: biznes egalari uchun taklif -->
         <RouterLink :to="{ name: 'my-vitrina' }" class="cta mt-5">
            <span class="cta-icon"><Sparkles class="size-5" /></span>
            <span class="grow">
               <span class="block text-sm font-semibold">{{ $t("vitrina.ctaTitle") }}</span>
               <span class="block text-xs text-(--z-muted-text) leading-snug">{{ $t("vitrina.ctaText") }}</span>
            </span>
            <ChevronRight class="size-4 text-(--z-muted-text)" />
         </RouterLink>
      </template>
   </NavigationPageDecorator>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { useRouter } from "vue-router";
import { ArrowLeft, ChevronRight, Sparkles, Store } from "lucide-vue-next";
import NavigationPageDecorator from "@/layouts/NavigationPageDecorator.vue";
import VitrinaRepo from "@shared/entities/Vitrina/VitrinaRepo";
import { useAuth } from "@shared/store/useAuth";
import type { IVitrina, VitrinaType } from "@shared/types";
import { goBack } from "@/modules/navigation";
import VitrinaAvatar from "@/features/vitrina/VitrinaAvatar.vue";
import VitrinaVerified from "@/features/vitrina/VitrinaVerified.vue";
import { VITRINA_TYPES, VITRINA_TYPE_KEYS } from "@/features/vitrina/vitrinaKit";

const router = useRouter();
const authStore = useAuth();

const vitrinas = ref<IVitrina[]>([]);
const isLoading = ref(false);
const type = ref<VitrinaType | null>(null);
const cityName = computed(() => authStore.user?.active_district?.name ?? null);

let lastRequest = 0;
async function load() {
   const requestId = ++lastRequest;
   isLoading.value = true;
   try {
      const { data } = await VitrinaRepo.index({ city_id: authStore.user?.active_district_id, type: type.value });
      if (requestId === lastRequest) vitrinas.value = data;
   } catch (error) {
      console.error("Vitrinalar yuklanmadi.", error);
   } finally {
      if (requestId === lastRequest) isLoading.value = false;
   }
}

watch(type, load);
onMounted(load);
</script>

<style scoped>
.type-chips {
   display: flex;
   gap: 0.5rem;
   margin-inline: -1rem;
   padding-inline: 1rem;
   overflow-x: auto;
   scrollbar-width: none;

   &::-webkit-scrollbar {
      display: none;
   }
}

.type-chip {
   display: inline-flex;
   height: 2.125rem;
   flex-shrink: 0;
   align-items: center;
   gap: 0.375rem;
   border: 1px solid var(--z-border);
   border-radius: 999px;
   background: var(--z-card);
   padding: 0 0.875rem;
   font-size: 0.8125rem;
   font-weight: 500;
   white-space: nowrap;
   cursor: pointer;

   &.is-active {
      border-color: var(--z-primary);
      background: var(--z-primary);
      color: var(--z-primary-foreground);
   }
}

.vitrina-card,
.cta {
   display: flex;
   align-items: center;
   gap: 0.75rem;
   border: 1px solid var(--z-glass-border);
   border-radius: var(--z-rounded);
   background: var(--z-card);
   box-shadow: var(--z-glass-edge, none);
   padding: 0.75rem;
   color: var(--z-foreground);
   transition: transform var(--duration) var(--ease-soft);

   &:active {
      transform: scale(0.98);
   }
}

.cta {
   border-style: dashed;
}

.cta-icon {
   display: grid;
   width: 2.5rem;
   height: 2.5rem;
   flex-shrink: 0;
   place-items: center;
   border-radius: 999px;
   background: color-mix(in srgb, var(--z-primary) 14%, transparent);
   color: var(--z-primary);
}
</style>
