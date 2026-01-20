<template>
   <section class="px-4 py-3">
      <!-- <nav class="flex justify-between mb-4">
         <BaseSelectLocation />
         <div class="flex gap-3">
            <OverlayBadge severity="info">
               <Button
                  @click="$router.push({ name: 'admin-categories' })"
                  icon="pi pi-bell"
                  severity="secondary"
                  size="small"
                  rounded
                  aria-label="Star"
               />
            </OverlayBadge>
         </div>
      </nav> -->
      <article>
         <IconField>
            <InputText
               fluid
               placeholder="Qidiruv"
               name="search"
               size="small"
               class="bg-surface-50! border-surface-100! dark:bg-surface-900! dark:border-surface-800!"
            />
            <InputIcon class="pi pi-search" />
         </IconField>
      </article>
   </section>
</template>

<script setup lang="ts">
import BaseSelectLocation from "./BaseSelectLocation.vue";
</script>
