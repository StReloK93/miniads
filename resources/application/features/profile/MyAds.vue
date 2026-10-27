<template>
   <NavigationPageDecorator :header-class="['border-b', 'border-(--z-border)']">
      <template #header>
         <!-- Bosh sahifa uslubida: qutilarsiz, yengil sarlavha + bir qator statistika -->
         <div class="flex items-start justify-between gap-3 mb-3">
            <div class="min-w-0">
               <h3 class="font-bold text-xl">{{ $t("myAds.title") }}</h3>
               <!-- Yuklanguncha "0 marta" deb yolg'on raqam ko'rsatmaymiz -->
               <p class="mt-0.5 inline-flex items-center gap-1 text-xs text-(--z-muted-text) tabular-nums">
                  <Eye class="size-3.5" />
                  <template v-if="products">{{ $t("myAds.viewed", { n: totalViews }, totalViewsCount) }}</template>
                  <span v-else class="h-3 w-24 rounded-full bg-(--z-muted) animate-pulse" />
               </p>
            </div>
            <RouterLink :to="{ name: 'create-select-category' }" :aria-label="$t('myAds.new')">
               <BaseButton icon-only rounded severity="secondary" size="sm">
                  <template #icon><Plus class="size-4.5" /></template>
               </BaseButton>
            </RouterLink>
         </div>

         <!-- Holat chiplari (bosh sahifadagi bo'lim chiplari kabi) + saralash -->
         <nav class="flex items-center gap-2 pb-3">
            <button
               v-for="(tab, index) in tabs"
               :key="tab.label"
               type="button"
               class="my-chip"
               :class="{ 'is-active': activeTab === index }"
               @click="activeTab = index"
            >
               {{ $t(tab.label) }}
               <span class="my-chip-count">{{ products ? tab.count : "·" }}</span>
            </button>

            <button
               v-if="currentList.length > 1"
               type="button"
               class="ml-auto inline-flex items-center gap-1 h-9 text-xs font-medium text-(--z-muted-text) cursor-pointer"
               @click="nextSort"
            >
               <ArrowDownUp class="size-3.5" />
               {{ sortLabel ? $t(sortLabel) : "" }}
            </button>
         </nav>
      </template>

      <template #content>
         <BaseModal
            :open="isOpen"
            :title="modalParams.title"
            :description="modalParams.description"
            :confirm-text="modalParams.confirmText"
            :cancel-text="modalParams.cancelText"
            :danger="modalParams.status === 'delete'"
            @close="closeModal"
            @confirm="modalParams.confirm"
         >
            <template #icon>
               <component
                  :is="modalParams.icon"
                  class="size-5"
                  :class="{ 'text-rose-500': modalParams.status === 'delete' }"
               />
            </template>
            <div v-if="modalParams.product">
               <h3
                  class="font-medium text-2xl text-center py-4 rounded-(--z-rounded)"
                  :class="[
                     modalParams.status === 'delete'
                        ? 'text-rose-500'
                        : modalParams.status === 'deActivate'
                        ? 'inactive'
                        : 'text-(--z-primary)',
                  ]"
               >
                  {{ modalParams.product?.title }}
               </h3>
            </div>
         </BaseModal>

         <!-- Muddati tugayotganlar: ixcham eslatma; bosilsa ular ro'yxat boshiga chiqadi -->
         <button
            v-if="activeTab === 0 && expiringCount && sort !== 'expiring'"
            type="button"
            class="mb-3 inline-flex items-center gap-1.5 h-8 pl-2.5 pr-3 rounded-full bg-amber-500/10 text-xs font-medium text-amber-700 dark:text-amber-300 cursor-pointer transition active:scale-95"
            @click="sort = 'expiring'"
         >
            <Clock class="size-3.5" />
            {{ $t("myAds.expiring", expiringCount) }}
         </button>

         <!-- Ko'p e'loni borlar uchun: nom bo'yicha qidirish -->
         <label v-if="currentList.length > 5 || searchText" class="field gap-2 mb-3">
            <Search class="size-4 shrink-0 text-(--z-muted-text)" />
            <input
               v-model="searchText"
               type="search"
               :placeholder="$t('myAds.searchPlaceholder')"
               class="w-full min-w-0 bg-transparent outline-none text-(--z-foreground) placeholder:text-(--z-muted-text)"
            />
         </label>

         <Transition mode="out-in">
            <main v-if="isLoading && !products" class="flex flex-col gap-2.5">
               <ProductCardSkeleton v-for="n in 3" :key="n" />
            </main>

            <div v-else-if="visibleList.length" :key="activeTab" class="flex flex-col gap-2.5">
               <ProfileProductCard
                  v-for="product in visibleList"
                  :key="product.id"
                  :product="product"
                  @deActivate="deActivateProduct"
                  @activate="activateProduct"
                  @delete="deleteProduct"
               />
            </div>

            <div v-else :key="`empty-${activeTab}`" class="flex flex-col justify-center items-center py-14 text-center">
               <div class="size-14 mb-3 rounded-full grid place-items-center bg-(--z-card) border border-(--z-border)">
                  <Megaphone class="size-6 text-(--z-muted-text)" />
               </div>
               <template v-if="searchText">
                  <h3 class="font-semibold mb-1">{{ $t("common.notFound") }}</h3>
                  <p class="text-sm text-(--z-muted-text)">{{ $t("myAds.noMatch", { query: searchText }) }}</p>
               </template>
               <template v-else-if="activeTab === 0">
                  <h3 class="font-semibold mb-1">{{ $t("myAds.noActive") }}</h3>
                  <p class="text-sm text-(--z-muted-text) max-w-64 mb-4">{{ $t("myAds.noActiveHint") }}</p>
                  <RouterLink :to="{ name: 'create-select-category' }">
                     <BaseButton size="sm">{{ $t("common.postAd") }}</BaseButton>
                  </RouterLink>
               </template>
               <template v-else>
                  <h3 class="font-semibold mb-1">{{ $t("myAds.noInactive") }}</h3>
                  <p class="text-sm text-(--z-muted-text) max-w-64">{{ $t("myAds.noInactiveHint") }}</p>
               </template>
            </div>
         </Transition>
      </template>
   </NavigationPageDecorator>
</template>

<script setup lang="ts">
import ProductCardSkeleton from "@/features/product/ProductCardSkeleton.vue";
import ProfileProductCard from "@/features/profile/ProfileProductCard.vue";
import NavigationPageDecorator from "@/layouts/NavigationPageDecorator.vue";
import ProductRepo from "@shared/entities/Product/ProductRepo";

import BaseModal from "@shared/ui/BaseModal.vue";
import BaseButton from "@shared/ui/BaseButton.vue";

import { matchesUzbek } from "@shared/modules/uzbekText";
import { useFetchDecorator } from "@shared/composables/useFetch";
import { IProduct } from "@shared/types";
import { ArrowDownUp, Clock, Eye, EyeOff, Megaphone, Plus, Search, Trash2 } from "lucide-vue-next";
import { computed, nextTick, onMounted, ref, watch } from "vue";
import { useI18n } from "vue-i18n";

const { t } = useI18n();

// Shuncha kun qolgan e'lon "tugayapti" deb ogohlantiriladi
const EXPIRING_DAYS = 3;

const isOpen = ref(false);
const activeTab = ref(0);
const searchText = ref("");
const sort = ref<"new" | "views" | "expiring">("new");

// Bitta so'rov: barcha e'lonlar; tablar va statistika shundan hisoblanadi (tab almashganda kutish yo'q)
const { data: products, execute: fetchProducts, isLoading } = useFetchDecorator<IProduct[]>(ProductRepo.myAds);
const reload = () => fetchProducts("all");

const isActive = (product: IProduct) => (product.days?.current ?? 0) > 0;
const activeProducts = computed(() => (products.value ?? []).filter(isActive));
const expiredProducts = computed(() => (products.value ?? []).filter((p) => !isActive(p)));
const currentList = computed(() => (activeTab.value === 0 ? activeProducts.value : expiredProducts.value));

const expiringCount = computed(
   () => activeProducts.value.filter((p) => (p.days?.current ?? 0) <= EXPIRING_DAYS).length,
);

const tabs = computed(() => [
   { label: "myAds.active", count: activeProducts.value.length },
   { label: "myAds.inactive", count: expiredProducts.value.length },
]);

const totalViewsCount = computed(() => (products.value ?? []).reduce((sum, p) => sum + (p.views_count ?? 0), 0));
const totalViews = computed(() => totalViewsCount.value.toLocaleString("ru-RU"));

const sortOptions = computed(() => [
   { id: "new" as const, label: "sort.new" },
   { id: "views" as const, label: "myAds.mostViewed" },
   ...(activeTab.value === 0 ? [{ id: "expiring" as const, label: "myAds.endingSoon" }] : []),
]);

const sortLabel = computed(() => sortOptions.value.find((o) => o.id === sort.value)?.label);

// Kichik tugma: har bosishda keyingi tartib (select ochib o'tirmaslik uchun)
function nextSort() {
   const options = sortOptions.value;
   const index = options.findIndex((o) => o.id === sort.value);
   sort.value = options[(index + 1) % options.length].id;
}

// O'chiq tabda "tugashi yaqin" ma'nosiz
watch(activeTab, (tab) => {
   if (tab === 1 && sort.value === "expiring") sort.value = "new";
});

const visibleList = computed(() => {
   const list = currentList.value.filter((p) => matchesUzbek(searchText.value, p.title));

   if (sort.value === "views") return [...list].sort((a, b) => (b.views_count ?? 0) - (a.views_count ?? 0));
   if (sort.value === "expiring") return [...list].sort((a, b) => (a.days?.current ?? 0) - (b.days?.current ?? 0));
   return list;
});

const modalParams = ref({
   status: "deActivate" as "deActivate" | "activate" | "delete",
   title: "",
   description: "",
   showButtons: false,
   icon: Eye,
   confirmText: t("common.save"),
   cancelText: t("common.close"),
   product: null as IProduct | null,
   confirm: async () => {},
});

function deActivateProduct(product: IProduct) {
   modalParams.value = {
      status: "deActivate",
      title: t("myAds.deactivateTitle"),
      description: t("myAds.deactivateText"),
      showButtons: true,
      icon: EyeOff,
      confirmText: t("myAds.deactivate"),
      product: product,
      cancelText: t("common.cancel"),

      confirm: async () => {
         await ProductRepo.deactivate(product.id);
         await reload();
         isOpen.value = false;
      },
   };
   nextTick();
   isOpen.value = true;
}

function activateProduct(product: IProduct) {
   modalParams.value = {
      status: "activate",
      title: t("myAds.activateTitle"),
      description: t("myAds.activateText"),
      showButtons: true,
      icon: Eye,
      product: product,
      confirmText: t("myAds.activate"),
      cancelText: t("common.cancel"),
      confirm: async () => {
         await ProductRepo.activate(product.id);
         await reload();
         isOpen.value = false;
      },
   };
   nextTick();
   isOpen.value = true;
}

function deleteProduct(product: IProduct) {
   modalParams.value = {
      status: "delete",
      title: t("myAds.deleteTitle"),
      description: t("myAds.deleteText"),
      showButtons: true,
      icon: Trash2,
      product: product,
      confirmText: t("myAds.deleteForever"),
      cancelText: t("common.cancel"),
      confirm: async () => {
         await ProductRepo.delete(product.id);
         await reload();
         isOpen.value = false;
      },
   };
   nextTick();
   isOpen.value = true;
}

function resetModalParams() {
   modalParams.value = {
      status: "deActivate",
      title: "",
      description: "",
      icon: EyeOff,
      product: null,
      showButtons: false,
      confirmText: t("common.save"),
      cancelText: t("common.close"),
      confirm: async () => {},
   };
}

function closeModal() {
   isOpen.value = false;
   setTimeout(() => resetModalParams(), 500);
}

onMounted(reload);
</script>

<style scoped>
/* Bosh sahifadagi bo'lim chiplari bilan bir xil shakl; faoli — to'q */
.my-chip {
   display: inline-flex;
   height: 2.25rem;
   align-items: center;
   gap: 0.375rem;
   border: 1px solid var(--z-glass-border);
   border-radius: 999px;
   background: var(--z-card);
   box-shadow: var(--z-glass-edge, none);
   padding: 0 0.5rem 0 0.875rem;
   color: var(--z-foreground);
   font-size: 0.8125rem;
   font-weight: 500;
   cursor: pointer;
   transition:
      background-color var(--duration) var(--ease-soft),
      color var(--duration) var(--ease-soft),
      transform var(--duration) var(--ease-soft);

   &:active {
      transform: scale(0.96);
   }

   /* Faolda shisha qirrasi (ustki oq chiziq) yo'q: to'q fonda "qora-oq-qora" chiziq bo'lib ko'rinardi */
   &.is-active {
      border-color: transparent;
      background: var(--z-primary);
      box-shadow: 0 4px 12px -6px color-mix(in srgb, var(--z-primary) 60%, transparent);
      color: var(--z-primary-foreground);
   }
}

.my-chip-count {
   display: grid;
   min-width: 1.375rem;
   height: 1.375rem;
   place-items: center;
   border-radius: 999px;
   background: color-mix(in srgb, currentColor 12%, transparent);
   padding: 0 0.35rem;
   font-size: 0.6875rem;
   font-weight: 700;
   font-variant-numeric: tabular-nums;
}
</style>
