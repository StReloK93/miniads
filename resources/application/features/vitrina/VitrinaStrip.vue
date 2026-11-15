<template>
   <!--
      Bosh sahifa sarlavhasida, bo'limlar tagida: Telegram stories kabi ixcham doiralar (tanlangan shahar bo'yicha).
      Lenta aylantirilganda ham ko'rinib turadi; joy kam — sarlavha qatori yo'q, nomlar bir qatorda.
      Oxirgi doira — barcha vitrinalar (katalog).
   -->
   <section v-if="vitrinas.length">
      <nav class="strip" :aria-label="$t('vitrina.title')">
         <RouterLink
            v-for="vitrina in vitrinas"
            :key="vitrina.id"
            :to="{ name: 'vitrina', params: { slug: vitrina.slug } }"
            class="strip-item"
         >
            <span class="strip-ring" :style="{ '--tone': VITRINA_TYPES[vitrina.type]?.tone }">
               <VitrinaAvatar :vitrina="vitrina" class="size-11 text-sm" />
               <VitrinaVerified v-if="vitrina.is_verified" class="strip-verified" />
            </span>
            <span class="strip-name">{{ vitrina.name }}</span>
         </RouterLink>

         <RouterLink :to="{ name: 'vitrinas' }" class="strip-item">
            <span class="strip-all">
               <LayoutGrid class="size-4.5" />
            </span>
            <span class="strip-name text-(--z-primary)">{{ $t("vitrina.title") }}</span>
         </RouterLink>
      </nav>
   </section>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from "vue";
import { LayoutGrid } from "lucide-vue-next";
import VitrinaVerified from "@/features/vitrina/VitrinaVerified.vue";
import VitrinaRepo from "@shared/entities/Vitrina/VitrinaRepo";
import { useAuth } from "@shared/store/useAuth";
import type { IVitrina } from "@shared/types";
import VitrinaAvatar from "@/features/vitrina/VitrinaAvatar.vue";
import { VITRINA_TYPES } from "@/features/vitrina/vitrinaKit";

const authStore = useAuth();
const vitrinas = ref<IVitrina[]>([]);

async function load() {
   try {
      const { data } = await VitrinaRepo.index({ city_id: authStore.user?.active_district_id, strip: true });
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
   gap: 0.625rem;
   margin-inline: -1rem;
   padding: 0 1rem 0.625rem;
   overflow-x: auto;
   scrollbar-width: none;

   &::-webkit-scrollbar {
      display: none;
   }
}

.strip-item {
   display: flex;
   width: 3.5rem;
   flex-shrink: 0;
   flex-direction: column;
   align-items: center;
   gap: 0.25rem;
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

/* "Barcha vitrinalar": halqali doira o'lchamida */
.strip-all {
   display: grid;
   width: 3rem;
   height: 3rem;
   place-items: center;
   border: 1.5px dashed var(--z-border);
   border-radius: 999px;
   background: var(--z-card);
   color: var(--z-primary);
}

.strip-verified {
   position: absolute;
   right: -2px;
   bottom: -2px;
   width: 1rem;
   height: 1rem;
   /* Avatar ustida ajralib tursin: fon rangidagi ingichka hoshiya */
   filter: drop-shadow(0 0 1.5px var(--z-background)) drop-shadow(0 0 1px var(--z-background));
}

/* Bir qator: uzun nom "…" bilan qisqaradi */
.strip-name {
   display: block;
   width: 100%;
   overflow: hidden;
   font-size: 0.625rem;
   font-weight: 600;
   line-height: 1.2;
   text-align: center;
   text-overflow: ellipsis;
   white-space: nowrap;
}
</style>
