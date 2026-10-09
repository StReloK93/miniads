<template>
   <div class="admin-shell">
      <aside class="admin-sidebar">
         <RouterLink class="admin-brand" :to="{ name: 'admin-home' }">
            <span class="admin-brand-mark">M</span>
            <span><strong>Miniads</strong><small>ADMIN PANEL</small></span>
         </RouterLink>

         <p class="admin-nav-label">BOSHQARUV</p>
         <nav class="admin-nav">
            <RouterLink v-for="item in mainLinks" :key="item.name" :to="{ name: item.name }" class="admin-nav-link">
               <component :is="item.icon" class="size-4" aria-hidden="true" />
               <span>{{ item.label }}</span>
            </RouterLink>
         </nav>
         <p class="admin-nav-label admin-nav-label-spaced">KATALOG</p>
         <nav class="admin-nav">
            <RouterLink v-for="item in catalogLinks" :key="item.name" :to="{ name: item.name }" class="admin-nav-link">
               <component :is="item.icon" class="size-4" aria-hidden="true" />
               <span>{{ item.label }}</span>
            </RouterLink>
         </nav>

         <div class="admin-sidebar-footer">
            <div class="admin-user-chip">
               <span class="admin-avatar">{{ initials }}</span>
               <span class="admin-user-label"><strong>{{ auth.user?.name || "Administrator" }}</strong><small>Administrator</small></span>
            </div>
         </div>
      </aside>

      <main class="admin-main">
         <header class="admin-topbar">
            <div>
               <span class="admin-topbar-label">MINIADS BOSHQARUV TIZIMI</span>
               <span class="admin-topbar-user">{{ auth.user?.name || "Administrator" }}</span>
            </div>
            <div class="flex items-center gap-3">
               <ThemeSwitcher variant="toggle" />
               <a class="admin-topbar-link" href="/">Saytni ochish <ArrowUpRight class="size-3.5" aria-hidden="true" /></a>
            </div>
         </header>
         <div class="admin-content"><RouterView /></div>
      </main>
   </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useAuth } from "@shared/store/useAuth";
import { ArrowUpRight, Bot, ChartPie, FileText, MapPin, SlidersHorizontal, Tags, Users, Workflow } from "lucide-vue-next";

const auth = useAuth();
const initials = computed(() =>
   (auth.user?.name || "A")
      .split(/\s+/)
      .slice(0, 2)
      .map((part: string) => part[0]?.toUpperCase())
      .join(""),
);

const mainLinks = [
   { name: "admin-home", label: "Umumiy ko'rinish", icon: ChartPie },
   { name: "admin-products", label: "E'lonlar", icon: FileText },
   { name: "admin-users", label: "Foydalanuvchilar", icon: Users },
   { name: "admin-bot", label: "Avto-e'lonlar (Bot)", icon: Bot },
];
const catalogLinks = [
   { name: "admin-categories", label: "Kategoriyalar", icon: Workflow },
   { name: "admin-parameters", label: "Parametrlar", icon: SlidersHorizontal },
   { name: "admin-districts", label: "Shaharlar", icon: MapPin },
   { name: "admin-price-types", label: "Narx turlari", icon: Tags },
];
</script>
