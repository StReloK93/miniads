<template>
   <div class="adm-shell z-ambient admin-shell">
      <!-- Kompyuter: yon panel (shisha) -->
      <aside class="adm-side z-glass" aria-label="Bo'limlar">
         <RouterLink class="adm-brand" :to="{ name: 'admin-home' }">
            <span class="adm-brand-mark">M</span>
            <span><strong>Miniads</strong><small>Admin</small></span>
         </RouterLink>
         <button type="button" class="adm-side-search" @click="openMenu(true)">
            <Search class="size-4" aria-hidden="true" />
            Qidirish…
         </button>
         <template v-for="group in groups" :key="group.title">
            <p class="adm-group-title">{{ group.title }}</p>
            <nav class="adm-side-nav">
               <RouterLink v-for="item in group.links" :key="item.name" :to="{ name: item.name }" class="adm-side-link">
                  <component :is="item.icon" class="size-4.5" aria-hidden="true" />
                  <span>{{ item.label }}</span>
               </RouterLink>
            </nav>
         </template>
         <a class="adm-side-link mt-auto" href="/">
            <ArrowLeft class="size-4.5" aria-hidden="true" />
            <span>Ilovaga qaytish</span>
         </a>
      </aside>

      <div class="adm-main">
         <header class="adm-top z-glass">
            <h1 class="adm-top-title">{{ current?.label ?? "Admin panel" }}</h1>
            <button type="button" class="adm-icon-btn" aria-label="Qidirish" @click="openMenu(true)">
               <Search class="size-5" />
            </button>
            <ThemeSwitcher variant="toggle" />
         </header>
         <main class="adm-content">
            <RouterView v-slot="{ Component, route: viewRoute }">
               <Transition name="adm-fade" mode="out-in">
                  <!-- Qidiruv o'zgarsa sahifa qayta ochiladi (menyudan "E'lonlardan qidirish" shu sahifada turganda ham) -->
                  <component :is="Component" :key="`${String(viewRoute.name)}|${viewRoute.query.search ?? ''}`" />
               </Transition>
            </RouterView>
         </main>
      </div>

      <!-- Telefon: ilovadagidek suzib turuvchi shisha panel -->
      <nav class="adm-dock z-glass" aria-label="Asosiy bo'limlar">
         <RouterLink v-for="item in dock" :key="item.name" :to="{ name: item.name }" class="adm-dock-item">
            <component :is="item.icon" class="size-5" stroke-width="2" aria-hidden="true" />
            <span>{{ item.short }}</span>
         </RouterLink>
         <button
            type="button"
            class="adm-dock-item"
            :class="{ 'is-active': menuOpen || inMenuOnly }"
            :aria-expanded="menuOpen"
            aria-controls="adm-menu"
            @click="menuOpen ? closeMenu() : openMenu(false)"
         >
            <LayoutGrid class="size-5" stroke-width="2" aria-hidden="true" />
            <span>Ko'proq</span>
         </button>
      </nav>

      <!-- Barcha bo'limlar + qidiruv: pastdan chiqadi -->
      <Transition name="adm-sheet">
         <div v-if="menuOpen" class="adm-sheet-layer">
            <div class="adm-sheet-backdrop z-overlay" aria-hidden="true" @click="closeMenu" />
            <section id="adm-menu" ref="menuPanel" class="adm-sheet z-glass-strong" role="dialog" aria-modal="true" aria-label="Bo'limlar va qidiruv">
               <div class="adm-sheet-grip" aria-hidden="true" />
               <label class="adm-sheet-search">
                  <Search class="size-4.5 shrink-0 opacity-60" aria-hidden="true" />
                  <input
                     ref="searchInput"
                     v-model="query"
                     type="search"
                     enterkeyhint="search"
                     placeholder="Bo'lim, e'lon yoki odam qidirish"
                     @keydown.enter.prevent="submitSearch"
                  />
                  <button v-if="query" type="button" class="adm-icon-btn is-small" aria-label="Tozalash" @click="query = ''">
                     <X class="size-4" />
                  </button>
               </label>

               <!-- Qidiruv: e'lon va foydalanuvchilardan to'g'ridan-to'g'ri -->
               <div v-if="query.trim()" class="adm-menu-list">
                  <button type="button" class="adm-menu-row" @click="go('admin-products', { search: query.trim() })">
                     <span class="adm-menu-icon"><FileText class="size-4.5" /></span>
                     <span class="adm-menu-text"><b>E'lonlardan qidirish</b><small>sarlavha, telefon, ID: «{{ query.trim() }}»</small></span>
                  </button>
                  <button type="button" class="adm-menu-row" @click="go('admin-users', { search: query.trim() })">
                     <span class="adm-menu-icon"><Users class="size-4.5" /></span>
                     <span class="adm-menu-text"><b>Foydalanuvchilardan qidirish</b><small>ism, @username, telefon: «{{ query.trim() }}»</small></span>
                  </button>
               </div>

               <template v-for="group in filteredGroups" :key="group.title">
                  <p class="adm-group-title">{{ group.title }}</p>
                  <nav class="adm-menu-list">
                     <RouterLink v-for="item in group.links" :key="item.name" :to="{ name: item.name }" class="adm-menu-row">
                        <span class="adm-menu-icon"><component :is="item.icon" class="size-4.5" aria-hidden="true" /></span>
                        <span class="adm-menu-text"><b>{{ item.label }}</b><small>{{ item.hint }}</small></span>
                        <ChevronRight class="size-4 opacity-40" aria-hidden="true" />
                     </RouterLink>
                  </nav>
               </template>

               <a v-if="!query.trim()" class="adm-menu-row adm-menu-back" href="/">
                  <span class="adm-menu-icon"><ArrowLeft class="size-4.5" /></span>
                  <span class="adm-menu-text"><b>Ilovaga qaytish</b></span>
               </a>
            </section>
         </div>
      </Transition>
   </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useSwipeToClose } from "@admin/modules/useSwipeToClose";
import {
   Activity,
   ArrowLeft,
   Bot,
   CalendarDays,
   ChevronRight,
   FileText,
   LayoutGrid,
   MapPin,
   SearchCheck,
   Search,
   Send,
   ShieldAlert,
   SlidersHorizontal,
   Store,
   Tags,
   Users,
   Workflow,
   X,
} from "lucide-vue-next";

type NavLink = { name: string; label: string; short?: string; hint: string; icon: any };

const groups: { title: string; links: NavLink[] }[] = [
   {
      title: "Har kuni",
      links: [
         { name: "admin-home", label: "Bugun", hint: "Kun bo'yicha hisobot: istalgan sanani tanlang", icon: CalendarDays },
         { name: "admin-products", label: "E'lonlar", hint: "Qidirish, tahrirlash, to'xtatish, shikoyatlar", icon: FileText },
         { name: "admin-users", label: "Foydalanuvchilar", short: "Odamlar", hint: "Kanal/bot a'zoligi, bloklash, xabar yozish", icon: Users },
         { name: "admin-channel-post", label: "Post yuborish", short: "Post", hint: "Kanalga va bot obunachilariga xabar", icon: Send },
      ],
   },
   {
      title: "Tahlil",
      links: [
         { name: "admin-activity", label: "Faollik", hint: "Kim nima ko'rdi, qayerdan keldi", icon: Activity },
         { name: "admin-search-stats", label: "Qidiruvlar", hint: "Nimani qidirishdi, nima topilmadi", icon: SearchCheck },
      ],
   },
   {
      title: "Boshqaruv",
      links: [
         { name: "admin-moderation", label: "Moderatsiya", hint: "Taqiqlangan so'zlar", icon: ShieldAlert },
         { name: "admin-vitrinas", label: "Vitrinalar", hint: "Biznes sahifalarni tasdiqlash", icon: Store },
         { name: "admin-bot", label: "Avto-e'lonlar", hint: "Kanallardan e'lon olib kelish (AI)", icon: Bot },
      ],
   },
   {
      title: "Katalog",
      links: [
         { name: "admin-categories", label: "Kategoriyalar", hint: "Bo'limlar va ichki bo'limlar", icon: Workflow },
         { name: "admin-parameters", label: "Parametrlar", hint: "E'lon formasidagi maydonlar", icon: SlidersHorizontal },
         { name: "admin-districts", label: "Shaharlar", hint: "Hududlar ro'yxati", icon: MapPin },
         { name: "admin-price-types", label: "Narx turlari", hint: "so'm, $, kelishiladi…", icon: Tags },
      ],
   },
];
const allLinks = groups.flatMap((group) => group.links);
const dock = groups[0].links.map((link) => ({ ...link, short: link.short ?? link.label }));

const route = useRoute();
const router = useRouter();
const current = computed(() => allLinks.find((item) => item.name === route.name));
const inMenuOnly = computed(() => !dock.some((item) => item.name === route.name));

const menuOpen = ref(false);
const query = ref("");
const searchInput = ref<HTMLInputElement | null>(null);

const filteredGroups = computed(() => {
   const text = query.value.trim().toLowerCase();
   if (!text) return groups;
   return groups
      .map((group) => ({
         ...group,
         links: group.links.filter((link) => `${link.label} ${link.hint}`.toLowerCase().includes(text)),
      }))
      .filter((group) => group.links.length);
});

async function openMenu(focusSearch: boolean) {
   query.value = "";
   menuOpen.value = true;
   if (focusSearch) {
      await nextTick();
      searchInput.value?.focus();
   }
}

function closeMenu() {
   menuOpen.value = false;
}

// Telefonda menyuni pastga surib yopish
const menuPanel = ref<HTMLElement | null>(null);
useSwipeToClose(menuPanel, closeMenu);

function go(name: string, queryParams: Record<string, string>) {
   router.push({ name, query: queryParams });
}

/** Enter: bitta bo'lim mos kelsa — o'sha bo'lim, aks holda e'lonlardan qidiruv */
function submitSearch() {
   const text = query.value.trim();
   if (!text) return;
   const matches = filteredGroups.value.flatMap((group) => group.links);
   if (matches.length === 1) router.push({ name: matches[0].name });
   else go("admin-products", { search: text });
}

watch(() => route.fullPath, closeMenu);
watch(menuOpen, (open) => document.documentElement.classList.toggle("adm-lock", open));

function onKeydown(event: KeyboardEvent) {
   if (event.key === "Escape") closeMenu();
   // Kompyuterda: "/" — qidiruv
   if (event.key === "/" && !menuOpen.value && !(event.target as HTMLElement)?.closest("input, textarea, [contenteditable]")) {
      event.preventDefault();
      openMenu(true);
   }
}
onMounted(() => window.addEventListener("keydown", onKeydown));
onBeforeUnmount(() => {
   window.removeEventListener("keydown", onKeydown);
   document.documentElement.classList.remove("adm-lock");
});
</script>
