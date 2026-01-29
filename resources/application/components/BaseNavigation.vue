<template>
   <section class="px-4 py-3">
      <!-- <nav class="flex justify-between mb-4">
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
         <InputText placeholder="Qidiruv" name="search" />
      </article>
   </section>
</template>

<script setup lang="ts"></script>
