<template>
   <div class="admin-shell" :class="{ 'is-menu-open': menuOpen }">
      <!-- Telefonda menyu ochilganda orqa fon; bosilsa yopiladi -->
      <div v-if="menuOpen" class="admin-backdrop" aria-hidden="true" @click="menuOpen = false" />

      <aside id="admin-menu" class="admin-sidebar">
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
            <a class="admin-nav-link" href="/">
               <ArrowLeft class="size-4" aria-hidden="true" />
               <span>Ilovaga qaytish</span>
            </a>
            <div class="admin-user-chip">
               <span class="admin-avatar">{{ initials }}</span>
               <span class="admin-user-label"><strong>{{ auth.user?.name || "Administrator" }}</strong><small>Administrator</small></span>
            </div>
         </div>
      </aside>

      <main class="admin-main">
         <header class="admin-topbar">
            <div class="flex items-center gap-3 min-w-0">
               <button
                  type="button"
                  class="admin-menu-button"
                  aria-controls="admin-menu"
                  :aria-expanded="menuOpen"
                  aria-label="Menyuni ochish"
                  @click="menuOpen = true"
               >
                  <Menu class="size-4.5" />
               </button>
               <span class="admin-topbar-label truncate">{{ currentTitle }}</span>
            </div>
            <div class="flex items-center gap-3">
               <ThemeSwitcher variant="toggle" />
               <a class="admin-topbar-link" href="/">Saytni ochish <ArrowUpRight class="size-3.5" aria-hidden="true" /></a>
            </div>
         </header>
         <div class="admin-content"><RouterView /></div>
      </main>

      <!-- Telefonda: eng ko'p ishlatiladigan bo'limlar bitta bosishda; qolganlari "Menyu"da -->
      <nav class="admin-tabbar" aria-label="Asosiy bo'limlar">
         <RouterLink v-for="item in tabLinks" :key="item.name" :to="{ name: item.name }" class="admin-tab">
            <component :is="item.icon" class="size-5" aria-hidden="true" />
            <span>{{ item.short }}</span>
         </RouterLink>
         <button
            type="button"
            class="admin-tab"
            :class="{ 'is-active': menuOpen || isInMenuOnly }"
            aria-controls="admin-menu"
            :aria-expanded="menuOpen"
            @click="menuOpen = true"
         >
            <Menu class="size-5" aria-hidden="true" />
            <span>Menyu</span>
         </button>
      </nav>
   </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRoute } from "vue-router";
import { useAuth } from "@shared/store/useAuth";
import {
   Activity,
   ArrowLeft,
   ArrowUpRight,
   Bot,
   ChartPie,
   FileText,
   MapPin,
   Menu,
   SearchCheck,
   Send,
   ShieldAlert,
   SlidersHorizontal,
   Tags,
   Users,
   Workflow,
} from "lucide-vue-next";

const auth = useAuth();
const route = useRoute();
const menuOpen = ref(false);

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
   { name: "admin-moderation", label: "Moderatsiya", icon: ShieldAlert },
   { name: "admin-activity", label: "Faollik", icon: Activity },
   { name: "admin-search-stats", label: "Qidiruvlar", icon: SearchCheck },
   { name: "admin-channel-post", label: "Kanalga post", icon: Send },
   { name: "admin-bot", label: "Avto-e'lonlar (Bot)", icon: Bot },
];
const catalogLinks = [
   { name: "admin-categories", label: "Kategoriyalar", icon: Workflow },
   { name: "admin-parameters", label: "Parametrlar", icon: SlidersHorizontal },
   { name: "admin-districts", label: "Shaharlar", icon: MapPin },
   { name: "admin-price-types", label: "Narx turlari", icon: Tags },
];

// Pastki panel (telefon): qisqa nomlar bilan
const tabLinks = [
   { name: "admin-home", short: "Asosiy", icon: ChartPie },
   { name: "admin-products", short: "E'lonlar", icon: FileText },
   { name: "admin-channel-post", short: "Post", icon: Send },
   { name: "admin-users", short: "Odamlar", icon: Users },
];
// Joriy sahifa pastki panelda bo'lmasa (kategoriyalar, bot...) — "Menyu" faol ko'rinadi
const isInMenuOnly = computed(() => !tabLinks.some((item) => item.name === route.name));

// Yuqori panelda joriy bo'lim nomi (telefonda qaysi sahifadaligingiz ko'rinib turadi)
const currentTitle = computed(
   () => [...mainLinks, ...catalogLinks].find((item) => item.name === route.name)?.label ?? "Admin panel",
);

// Sahifa almashganda menyu yopiladi
watch(() => route.fullPath, () => (menuOpen.value = false));

function onKeydown(event: KeyboardEvent) {
   if (event.key === "Escape") menuOpen.value = false;
}
onMounted(() => window.addEventListener("keydown", onKeydown));
onBeforeUnmount(() => window.removeEventListener("keydown", onKeydown));
</script>
