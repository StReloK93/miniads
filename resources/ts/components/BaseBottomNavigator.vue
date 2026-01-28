<template>
   <nav class="flex justify-between px-3 pt-2 pb-safe-bottom bg-surface-50 dark:bg-surface-900">
      <RouterLink :to="{ name: 'home' }">
         <Button
            icon="pi pi-home"
            :severity="$route.name == 'home' ? 'primary' : 'secondary'"
            label="Bosh Sahifa"
            iconPos="top"
            size="small"
            variant="text"
         />
      </RouterLink>
      <RouterLink :to="{ name: 'favorites' }">
         <Button
            icon="pi pi-heart"
            :severity="$route.name == 'favorites' ? 'primary' : 'secondary'"
            label="Sevimlilar"
            iconPos="top"
            size="small"
            variant="text"
         />
      </RouterLink>
      <Button
         @click="visible = true"
         icon="pi pi-plus-circle"
         label="E'lon berish"
         severity="secondary"
         iconPos="top"
         size="small"
         variant="text"
      />
      <Button
         @click="openUserSettings = true"
         icon="pi pi-user"
         label="Profil"
         severity="secondary"
         iconPos="top"
         size="small"
         variant="text"
      />
      <BottomDrawer v-model="openUserSettings">
         <main class="p-4">
            <ThemeSwitcher />
         </main>
      </BottomDrawer>
      <BottomDrawer v-model="visible" fullScreen>
         <CreateProductForm @close="visible = false" />
      </BottomDrawer>
   </nav>
</template>
<script lang="ts" setup>
import BottomDrawer from "./ui/BottomDrawer.vue";
import CreateProductForm from "@/entities/Product/CreateProductForm.vue";
import { ref } from "vue";
const visible = ref(false);
const openUserSettings = ref(false);
import ThemeSwitcher from "./ThemeSwitcher.vue";
</script>
