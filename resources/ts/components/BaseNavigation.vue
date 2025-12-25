<template>
   <section>
      <nav class="flex justify-between mb-3">
         <div class="flex items-center gap-3">
            <Button icon="pi pi-map-marker" severity="secondary" size="small" rounded aria-label="Star" />
            <span class="text-sm text-surface-500">Uchquduq</span>
         </div>
         <div class="flex gap-3">
            <OverlayBadge severity="info">
               <Button icon="pi pi-bell" severity="secondary" size="small" rounded aria-label="Star" />
            </OverlayBadge>
            <Avatar image="https://primefaces.org/cdn/primevue/images/organization/walter.jpg" shape="circle" />
         </div>
      </nav>
      <article>
         <IconField>
            <InputText fluid placeholder="Qidiruv" size="small" />
            <InputIcon class="pi pi-search" />
         </IconField>
      </article>
   </section>
</template>

<script setup lang="ts"></script>
