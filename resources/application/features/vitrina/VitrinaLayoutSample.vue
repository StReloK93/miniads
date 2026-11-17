<template>
   <!--
      Ko'rinish misoli — matnsiz skelet: muqova, logo, nom chiziqlari va mahsulotlar
      (plitkalar yoki ro'yxat). Rangi — vitrina turi (--tone) ning yengil tuslari; faqat tushuntirish uchun.
   -->
   <div class="sample" aria-hidden="true">
      <div class="cover tone-dots" />
      <div class="px-3">
         <div class="avatar" />
         <div class="bar bar-strong mt-2 h-2.5 w-2/5" />
         <div class="bar mt-1.5 h-1.5 w-1/4" />
         <div class="info">
            <div class="bar h-1.5 w-3/5" />
            <div class="bar h-1.5 w-2/5" />
         </div>

         <div v-if="layout === 'grid'" class="tiles">
            <div v-for="n in 4" :key="n" class="tile tone-dots">
               <div class="tile-panel">
                  <div class="bar h-1.5 w-3/5" />
                  <div class="bar bar-strong h-2 w-2/5" />
               </div>
            </div>
         </div>
         <div v-else class="rows">
            <div v-for="n in 5" :key="n" class="row">
               <div class="thumb" />
               <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                  <div class="bar bar-strong h-2 w-3/5" />
                  <div class="bar h-1.5 w-2/5" />
               </div>
               <div class="bar bar-strong h-2 w-10 shrink-0" />
            </div>
         </div>
      </div>
   </div>
</template>

<script setup lang="ts">
defineProps<{ layout: "grid" | "list" }>();
</script>

<style scoped>
.sample {
   overflow: hidden;
   border: 1px solid var(--z-border);
   border-radius: var(--z-rounded);
   background: var(--z-background);
   padding-bottom: 0.75rem;
}

/* Vitrinadagi rasmsiz muqova va plitkalar kabi: yengil rang va so'nib boruvchi nuqtali naqsh (gradient emas) */
.cover {
   position: relative;
   height: 4.5rem;
   background: rgb(var(--tone) / 0.1);
}

.avatar {
   position: relative;
   width: 2.75rem;
   height: 2.75rem;
   margin-top: -1.375rem;
   border: 3px solid var(--z-background);
   border-radius: 999px;
   background: rgb(var(--tone) / 0.22);
}

.bar {
   border-radius: 999px;
   background: rgb(var(--tone) / 0.16);
}

.bar-strong {
   background: rgb(var(--tone) / 0.32);
}

.info {
   display: flex;
   flex-direction: column;
   gap: 0.5rem;
   margin-top: 0.75rem;
   border: 1px solid var(--z-border);
   border-radius: 0.625rem;
   background: var(--z-card);
   padding: 0.625rem;
}

.tiles {
   display: grid;
   grid-template-columns: 1fr 1fr;
   gap: 0.5rem;
   margin-top: 0.75rem;
}

.tile {
   position: relative;
   aspect-ratio: 4 / 5;
   overflow: hidden;
   border-radius: 0.625rem;
   background: rgb(var(--tone) / 0.08);
}

.tile-panel {
   position: absolute;
   right: 0.25rem;
   bottom: 0.25rem;
   left: 0.25rem;
   display: flex;
   flex-direction: column;
   gap: 0.3125rem;
   border-radius: 0.4375rem;
   background: var(--z-card);
   padding: 0.4375rem 0.5rem;
}

.rows {
   display: flex;
   flex-direction: column;
   margin-top: 0.5rem;
}

.row {
   display: flex;
   align-items: center;
   gap: 0.625rem;
   border-bottom: 1px solid var(--z-border);
   padding: 0.5rem 0;

   &:last-child {
      border-bottom: none;
   }
}

.thumb {
   width: 2.25rem;
   height: 2.25rem;
   flex-shrink: 0;
   border-radius: 0.5rem;
   background: rgb(var(--tone) / 0.12);
}
</style>
