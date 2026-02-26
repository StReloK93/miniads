<template>
   <nav class="flex justify-between p-1 z-bg-gradient backdrop-blur-sm border rounded-full border-(--z-border)">
      <RouterLink v-for="item in items" :key="item.name" :to="{ name: item.name }">
         <button
            v-if="!item.iconOnly"
            :class="{ 'bg-(--z-primary)/10 font-extrabold ': $route.name === item.name }"
            class="flex flex-col items-center gap-1 text-xs min-w-18 p-1 rounded-full"
         >
            <component :is="item.icon" class="size-5" stroke-width="2" />
            {{ item.label }}
         </button>
         <button
            v-else
            class="flex items-center justify-center size-12 rounded-full bg-(--z-primary) text-white aspect-square border border-(--z-border) shadow-md -translate-y-4"
         >
            <component :is="item.icon" class="size-5" stroke-width="2" />
         </button>
      </RouterLink>
   </nav>
</template>

<script lang="ts" setup>
import { House, Plus, Search, User, Heart } from "lucide-vue-next";

const items = [
   { name: "home", label: "Asosiy", icon: House },
   { name: "search", label: "Qidiruv", icon: Search },
   { name: "create-select-category", icon: Plus, iconOnly: true },
   { name: "favorites", label: "Sevimli", icon: Heart },
   { name: "profile", label: "Profil", icon: User },
];
</script>
