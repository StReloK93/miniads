<template>
   <NavigationPageDecorator :auto-scroll="false">
      <template #header="{ isCompact, progress }">
         <div class="flex items-center justify-between">
            <h3 class="font-bold text-xl transition-all">
               <Transition mode="out-in">
                  <span v-if="isCompact"> E'lonlar</span>
                  <span v-else> Profil </span>
               </Transition>
            </h3>
            <div class="flex items-center gap-2">
               <!-- Faqat adminlarga: admin panel alohida ilova, shuning uchun oddiy havola -->
               <a
                  v-if="authStore.user?.role === 'admin'"
                  href="/admin"
                  class="h-9 pl-3 pr-3.5 inline-flex items-center gap-1.5 rounded-full border border-(--z-border) bg-(--z-card) text-sm font-medium text-(--z-foreground) transition active:scale-95 hover:bg-(--z-muted)"
               >
                  <LayoutDashboard class="size-4" />
                  Admin panel
               </a>
               <!-- Brauzerda (Telegram login) chiqish; Mini App ichida kerak emas — Telegram hisobiga bog'liq -->
               <button
                  v-if="!inTelegram"
                  type="button"
                  aria-label="Hisobdan chiqish"
                  title="Hisobdan chiqish"
                  class="size-9 inline-flex items-center justify-center rounded-full border border-(--z-border) bg-(--z-card) text-(--z-foreground) cursor-pointer transition active:scale-90 hover:bg-(--z-muted) disabled:opacity-60"
                  :disabled="isLoggingOut"
                  @click="confirmLogout"
               >
                  <LogOut class="size-4" />
               </button>
            </div>
         </div>
         <aside
            ref="header"
            :class="[!isCompact ? 'py-4' : 'pb-4']"
            class="flex flex-col items-center overflow-hidden origin-center transition-[padding]"
            :style="{
               maxHeight: 280 - (280 / 100) * progress + 'px',
               scale: 1 - (1 / 100) * progress,
               opacity: 1 - (1 / 100) * progress,
            }"
         >
            <div class="mb-2 flex">
               <img
                  v-if="user.photo_url && isImagesReady"
                  :src="user.photo_url"
                  class="rounded-full w-18 h-18 object-fill"
               />
               <span v-else-if="user.photo_url && !isImagesReady" class="rounded-full! w-18 h-18 skeleton inline-block">
               </span>
               <span
                  v-else
                  class="rounded-full w-18 h-18 bg-(--z-muted) text-(--z-primary) inline-flex justify-center items-center text-xl font-bold"
               >
                  <template v-if="initials">{{ initials }}</template>
                  <Camera v-else class="size-5" stroke-width="2" stroke="currentColor" />
               </span>
            </div>
            <h3 class="font-semibold">{{ user?.name }}</h3>
            <div v-if="user?.username" class="text-(--z-muted-text)">@{{ user?.username }}</div>
            <main v-else class="text-center">
               <div class="text-(--z-muted-text) flex items-center justify-center gap-2">
                  <Megaphone class="inline size-4 text-(--z-primary)" /> Foydalanuvchi nomi mavjud emas
               </div>
               <p class="text-(--z-muted-text) text-center text-xs mt-1">
                  E'lon qo'yganingizda sizga Telegram orqali to'g'ridan-to'g'ri xabar yozishlarini istasangiz
                  <span class="font-semibold text-(--z-primary)">Telegram</span>
                  sozlamalariga o'ting va "Foydalanuvchi nomi" maydonini to'ldiring.
               </p>
            </main>

            <div class="mt-3">
               <ThemeSwitcher variant="segmented" />
            </div>

            <!-- <main class="flex w-full divide-x divide-(--z-border)">
            <div class="text-center w-1/2">
                  <span class="font-semibold">{{ products?.length }}</span>
                  <h3 class="text-(--z-muted-text) text-sm">Faol</h3>
               </div>
               <div class="text-center w-1/2">
                  <span class="font-semibold">12</span>
                  <h3 class="text-(--z-muted-text) text-sm">O'chiq</h3>
               </div>
            <div class="text-center w-1/3">
                  <span class="font-semibold">4.5</span>
                  <h3 class="text-(--z-muted-text) text-sm">Baho</h3>
               </div>
            </main> -->

            <div class="flex gap-2 w-full mt-8">
               <h3 class="text-center text-2xl font-bold w-full overflow-hidden transition-all duration-300 delay-150">
                  E'lonlarim
               </h3>
               <!-- <BaseButton size="sm" rounded class="w-full" variant="text"> E'lonlar </BaseButton> -->
               <!-- <BaseButton size="sm" rounded class="w-full" severity="secondary"> Do'kon </BaseButton> -->
            </div>
         </aside>
         <BaseTabs
            v-model="activeTab"
            :items="[
               { label: `Faol`, status: 'active' },
               { label: `O'chiq`, status: 'expired' },
            ]"
            @change="onTabChange"
            class="w-full"
         />
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
         <Transition mode="out-in">
            <div v-if="!isLoading" class="flex flex-col gap-2.5">
               <template v-if="products?.length">
                  <ProfileProductCard
                     v-for="product in products"
                     :key="product.id"
                     :product="product"
                     @deActivate="deActivateProduct"
                     @activate="activateProduct"
                     @delete="deleteProduct"
                  />
               </template>
               <div v-else class="flex flex-col justify-center items-center h-52 text-center">
                  <h3 class="font-bold mb-2">Hech nima topilmadi</h3>
                  <p class="text-(--z-muted-text)" v-if="activeTab == 0">Sizda faol elonlar yo'q</p>
                  <p class="text-(--z-muted-text)" v-if="activeTab == 1">Sizda muddati o'tgan elonlar yo'q</p>
               </div>
            </div>
            <main v-else class="flex flex-col gap-2.5">
               <ProductCardSkeleton v-for="n in 3" />
            </main>
         </Transition>
      </template>
   </NavigationPageDecorator>
</template>

<script setup lang="ts">
import ProductCardSkeleton from "@/features/product/ProductCardSkeleton.vue";
import ProfileProductCard from "@/features/profile/ProfileProductCard.vue";
import ProductRepo from "@shared/entities/Product/ProductRepo";
import { preloadImages } from "@shared/modules/helpers";
import { computed, inject, nextTick, onMounted, ref } from "vue";
import NavigationPageDecorator from "@/layouts/NavigationPageDecorator.vue";
import { isTMA } from "@tma.js/bridge";
import { Camera, EyeOff, Eye, LayoutDashboard, LogOut, Megaphone, Trash2 } from "lucide-vue-next";
import { useAuth } from "@shared/store/useAuth";
import { useFetchDecorator } from "@shared/composables/useFetch";
import { IProduct } from "@shared/types";
const isImagesReady = ref(false);
const userData: any = inject("userData");
const isOpen = ref(false);
const header = ref<HTMLElement>();

const activeTab = ref(0);
function onTabChange(status) {
   fetchProducts(status.item.status);
}

var headerHeight = ref(1000);

const modalParams = ref({
   status: "deActivate" as "deActivate" | "activate" | "delete" | "logout",
   title: "",
   description: "",
   showButtons: false,
   icon: Eye,
   confirmText: "Saqlash",
   cancelText: "Yopish",
   product: null as IProduct | null,
   confirm: async () => {},
});

function deActivateProduct(product: IProduct) {
   modalParams.value = {
      status: "deActivate",
      title: "E'lonni o'chirish",
      description: "E'loningiz o'chirilganlar ro'yxatiga o'tadi va uni qayta faollashtirish mumkin",
      showButtons: true,
      icon: EyeOff,
      confirmText: "O'chirish",
      product: product,
      cancelText: "Bekor qilish",

      confirm: async () => {
         await ProductRepo.deactivate(product.id);
         await fetchProducts("active");
         isOpen.value = false;
      },
   };
   nextTick();
   isOpen.value = true;
}

function activateProduct(product: IProduct) {
   modalParams.value = {
      status: "activate",
      title: "E'lonni faollashtirish",
      description: "E'loningizni faollashtirmoqchimisiz?",
      showButtons: true,
      icon: Eye,
      product: product,
      confirmText: "Faollashtirish",
      cancelText: "Bekor qilish",
      confirm: async () => {
         await ProductRepo.activate(product.id);
         await fetchProducts("expired");
         isOpen.value = false;
      },
   };
   nextTick();
   isOpen.value = true;
}

function deleteProduct(product: IProduct) {
   modalParams.value = {
      status: "delete",
      title: "E'lonni butunlay o'chirish",
      description: "E'lon butunlay o'chiriladi va uning barcha rasmlari o'chiriladi. Ushbu amalni ortga qaytarib bo'lmaydi. Rozimisiz?",
      showButtons: true,
      icon: Trash2,
      product: product,
      confirmText: "Butunlay o'chirish",
      cancelText: "Bekor qilish",
      confirm: async () => {
         await ProductRepo.delete(product.id);
         await fetchProducts(activeTab.value === 0 ? "active" : "expired");
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
      confirmText: "Saqlash",
      cancelText: "Yopish",
      confirm: async () => {},
   };
}

function closeModal() {
   isOpen.value = false;
   setTimeout(() => resetModalParams(), 500);
}

const { data: products, execute: fetchProducts, isLoading } = useFetchDecorator<IProduct[]>(ProductRepo.myAds);

const authStore = useAuth();
const inTelegram = isTMA();

// Ism va username bazadagi foydalanuvchidan; rasm faqat Mini App'da (Telegram beradi)
const user = computed(() => ({
   name: authStore.user?.name,
   username: authStore.user?.username,
   photo_url: inTelegram ? userData?.tgWebAppData?.user?.photo_url : null,
}));

const initials = computed(() =>
   (user.value.name || "")
      .split(" ")
      .filter(Boolean)
      .slice(0, 2)
      .map((part: string) => part[0].toUpperCase())
      .join(""),
);

const isLoggingOut = ref(false);

function confirmLogout() {
   modalParams.value = {
      status: "logout",
      title: "Hisobdan chiqish",
      description: "Rostdan ham chiqmoqchimisiz? Qayta kirish uchun Telegram orqali login qilishingiz kerak bo'ladi.",
      showButtons: true,
      icon: LogOut,
      product: null,
      confirmText: "Chiqish",
      cancelText: "Bekor qilish",
      confirm: async () => {
         isLoggingOut.value = true;
         try {
            await authStore.logout();
         } finally {
            isLoggingOut.value = false;
            isOpen.value = false;
         }
      },
   };
   isOpen.value = true;
}

onMounted(async () => {
   await fetchProducts("active");

   const images = <string[]>[];
   if (user.value.photo_url) {
      images.push(user.value.photo_url);
   }
   await preloadImages(images);

   setTimeout(() => {
      isImagesReady.value = true;
   }, 1000);
   headerHeight.value = header.value?.clientHeight ?? 0;
});
</script>
