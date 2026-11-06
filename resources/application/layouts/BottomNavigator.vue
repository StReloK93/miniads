<template>
   <nav class="z-glass flex items-center gap-0.5 p-1 border rounded-full">
      <!-- Tablar teng kenglikda: eng uzun yozuv ("E'lonlarim") ham tor ekranga sig'adi -->
      <RouterLink
         v-for="item in items"
         :key="item.name"
         :to="{ name: item.name }"
         :class="item.iconOnly ? 'shrink-0 px-1' : 'flex-1 min-w-0'"
      >
         <!-- Faolda faqat rang o'zgaradi, qalinlik emas — yozuv kengayib "sakramaydi" -->
         <button
            v-if="!item.iconOnly"
            :class="[
               $route.name === item.name
                  ? 'bg-(--z-primary)/10 text-(--z-primary)'
                  : 'text-(--z-muted-text) hover:text-(--z-primary)',
            ]"
            class="w-full flex flex-col items-center gap-1 px-1 py-1.5 rounded-[1.375rem] text-[11px] font-semibold leading-none whitespace-nowrap transition-colors duration-150 cursor-pointer"
         >
            <span class="relative">
               <component :is="item.icon" class="size-5" stroke-width="2" />
               <!-- Administratsiyadan o'qilmagan xabar: ekranni to'smaydi, faqat belgi -->
               <span
                  v-if="item.name === 'profile' && unreadCount"
                  class="absolute -top-1 -right-2 min-w-4 h-4 px-1 rounded-full bg-rose-500 text-white text-[10px] leading-4 text-center tabular-nums"
               >
                  {{ unreadCount > 9 ? "9+" : unreadCount }}
               </span>
            </span>
            {{ $t(item.label ?? "") }}
         </button>
         <button
            v-else
            class="z-fab flex items-center justify-center size-13 rounded-full aspect-square -translate-y-4 transition-transform active:scale-90 cursor-pointer"
         >
            <component :is="item.icon" class="size-5" stroke-width="2.5" />
         </button>
      </RouterLink>
   </nav>
</template>

<script lang="ts" setup>
import { House, Plus, Megaphone, User, Heart } from "lucide-vue-next";
import { useAdminMessages } from "@/features/notifications/useAdminMessages";

const { unreadCount } = useAdminMessages();

const items = [
   { name: "home", label: "nav.home", icon: House },
   // Sotuvchi o'z e'lonlarini tez-tez ochadi; qidiruv va bo'limlar bosh sahifaning yuqorisida
   { name: "my-ads", label: "nav.myAds", icon: Megaphone },
   { name: "create-select-category", icon: Plus, iconOnly: true },
   { name: "favorites", label: "nav.favorites", icon: Heart },
   { name: "profile", label: "nav.profile", icon: User },
];
</script>
