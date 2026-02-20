<template>
   <nav
      class="flex justify-around p-1 z-bg-gradient backdrop-blur-sm border rounded-(--z-rounded) border-(--z-color-border)"
   >
      <RouterLink v-for="item in items" :key="item.name" :to="{ name: item.name }">
         <BaseButton
            :severity="$route.name === item.name ? 'primary' : 'secondary'"
            variant="text"
            class="flex-col gap-1! text-xs min-w-18 p-1! font-black!"
         >
            <template #icon>
               <component :is="item.icon" class="size-4" stroke-width="3" />
            </template>
            {{ item.label }}
         </BaseButton>
      </RouterLink>
   </nav>
</template>

<script lang="ts" setup>
import { House, Plus, Search, User } from "lucide-vue-next";

const items = [
   { name: "home", label: "Asosiy", icon: House },
   { name: "search", label: "Qidiruv", icon: Search },
   { name: "create-select-category", label: "E'lon berish", icon: Plus },
   { name: "profile", label: "Profil", icon: User },
];
</script>
