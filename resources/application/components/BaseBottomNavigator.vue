<template>
   <nav class="flex justify-between p-1 bg-(--z-card)/90 backdrop-blur-md border rounded-full border-(--z-border) shadow-md">
      <RouterLink v-for="item in items" :key="item.name" :to="{ name: item.name }">
         <button
            v-if="!item.iconOnly"
            :class="[
               $route.name === item.name
                  ? 'bg-(--z-primary)/10 text-(--z-primary) font-bold'
                  : 'text-(--z-muted-text) hover:text-(--z-primary)',
            ]"
            class="flex flex-col items-center gap-1 text-xs min-w-18 p-1.5 rounded-full transition-all duration-150 cursor-pointer"
         >
            <component :is="item.icon" class="size-5" stroke-width="2" />
            {{ item.label }}
         </button>
         <button
            v-else
            class="flex items-center justify-center size-12 rounded-full bg-(--z-primary) text-(--z-primary-foreground) aspect-square border border-(--z-border) shadow-md -translate-y-4 transition-transform active:scale-90 cursor-pointer"
         >
            <component :is="item.icon" class="size-5" stroke-width="2.5" />
         </button>
      </RouterLink>
   </nav>
</template>

<script lang="ts" setup>
import { House, Plus, Search, User, Heart } from "lucide-vue-next";

const items = [
   { name: "home", label: "Asosiy", icon: House },
   { name: "search", label: "Qidiruv", icon: Search },
   { name: "create-select-city", icon: Plus, iconOnly: true },
   { name: "favorites", label: "Sevimli", icon: Heart },
   { name: "profile", label: "Profil", icon: User },
];
</script>
