<template>
   <!--
      Bosh sahifadagi "Vitrinalar": Telegram stories kabi doiralar qatori, tanlangan shahar bo'yicha.
      Oxirida "Hammasi" — katalog sahifasi.
   -->
   <section v-if="vitrinas.length" class="mb-5">
      <div class="flex items-center justify-between mb-2.5">
         <h3 class="font-bold text-base">{{ $t("vitrina.title") }}</h3>
         <RouterLink :to="{ name: 'vitrinas' }" class="text-xs text-(--z-primary) font-medium inline-flex items-center gap-0.5">
            {{ $t("common.all") }}
            <ChevronRight class="size-3" />
         </RouterLink>
      </div>

      <nav class="strip">
         <RouterLink
            v-for="vitrina in vitrinas"
            :key="vitrina.id"
            :to="{ name: 'vitrina', params: { slug: vitrina.slug } }"
            class="strip-item"
         >
            <span class="strip-ring" :style="{ '--tone': VITRINA_TYPES[vitrina.type]?.tone }">
               <VitrinaAvatar :vitrina="vitrina" class="size-14 text-lg" />
               <BadgeCheck v-if="vitrina.is_verified" class="strip-verified" />
            </span>
            <span class="strip-name">{{ vitrina.name }}</span>
         </RouterLink>
      </nav>
   </section>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from "vue";
import { BadgeCheck, ChevronRight } from "lucide-vue-next";
import VitrinaRepo from "@shared/entities/Vitrina/VitrinaRepo";
import { useAuth } from "@shared/store/useAuth";
import type { IVitrina } from "@shared/types";
import VitrinaAvatar from "@/features/vitrina/VitrinaAvatar.vue";
import { VITRINA_TYPES } from "@/features/vitrina/vitrinaKit";

const authStore = useAuth();
const vitrinas = ref<IVitrina[]>([]);

async function load() {
   try {
      const { data } = await VitrinaRepo.index({ city_id: authStore.user?.active_district_id });
      vitrinas.value = data;
   } catch (error) {
      console.error("Vitrinalar yuklanmadi.", error);
   }
}

// Shahar almashsa — qator ham shu shahar bo'yicha
watch(() => authStore.user?.active_district_id, load);
onMounted(load);
</script>

<style scoped>
.strip {
   display: flex;
   gap: 0.875rem;
   margin-inline: -1rem;
   padding: 0.125rem 1rem 0.25rem;
   overflow-x: auto;
   scrollbar-width: none;

   &::-webkit-scrollbar {
      display: none;
   }
}

.strip-item {
   display: flex;
   width: 4.25rem;
   flex-shrink: 0;
   flex-direction: column;
   align-items: center;
   gap: 0.375rem;
   color: var(--z-foreground);
   -webkit-tap-highlight-color: transparent;
   transition: transform var(--duration) var(--ease-soft);

   &:active {
      transform: scale(0.93);
   }
}

/* Stories kabi rangli halqa */
.strip-ring {
   position: relative;
   display: grid;
   padding: 2px;
   border-radius: 999px;
   background: linear-gradient(135deg, rgb(var(--tone)), rgb(var(--tone) / 0.35));

   & > :first-child {
      border: 2px solid var(--z-background);
   }
}

.strip-verified {
   position: absolute;
   right: -2px;
   bottom: -2px;
   width: 1.125rem;
   height: 1.125rem;
   border-radius: 999px;
   background: var(--z-background);
   color: var(--z-primary);
}

.strip-name {
   display: -webkit-box;
   width: 100%;
   -webkit-box-orient: vertical;
   -webkit-line-clamp: 2;
   overflow: hidden;
   font-size: 0.6875rem;
   font-weight: 600;
   line-height: 1.2;
   text-align: center;
}
</style>
