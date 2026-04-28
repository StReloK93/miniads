<template>
   <NavigationPageDecorator :auto-scroll="false">
      <template #header="{ isCompact, progress }">
         <h3 class="font-bold text-xl transition-all">
            <Transition mode="out-in">
               <span v-if="isCompact"> E'lonlar</span>
               <span v-else> Profil </span>
            </Transition>
         </h3>
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
               <span v-else class="rounded-full w-18 h-18 bg-slate-100 inline-flex justify-center items-center">
                  <Camera class="size-5" stroke-width="2" stroke="currentColor" />
               </span>
            </div>
            <!-- {{ userData.tgWebAppData.user.photo_url }} -->
            <h3 class="font-semibold">{{ user?.first_name }} {{ user?.last_name }}</h3>
            <div v-if="user?.username" class="text-(--z-muted-text)">@{{ user?.username }}</div>
            <main v-else class="text-center">
               <div class="text-(--z-muted-text) flex items-center justify-center gap-2">
                  <Megaphone class="inline size-4 text-(--z-primary)" /> Foydalanuvchi nomi majvud emas
               </div>
               <p class="text-(--z-muted-text) text-center text-xs mt-1">
                  E'lon qoyganizda sizga telegramdan to'g'ridan to'g'ri habar yuborishlarini istasangiz
                  <span class="font-semibold text-(--z-primary)">Telegram</span>
                  sozlamalariga o'ting va "Foydalanuvchi nomi" maydonini to'ldiring.
               </p>
            </main>

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
                  E'lonlar
               </h3>
               <!-- <BaseButton size="sm" rounded class="w-full" variant="text"> E'lonlar </BaseButton> -->
               <!-- <BaseButton size="sm" rounded class="w-full" severity="secondary"> Do'kon </BaseButton> -->
            </div>
         </aside>
         <BaseTabs
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
            @close="closeModal"
            @confirm="modalParams.confirm"
         >
            <template #icon>
               <component :is="modalParams.icon" class="size-4" />
            </template>
            <div>
               <h3
                  class="font-medium text-2xl text-center py-4 rounded-(--z-rounded) text-(--z-primary)"
                  :class="{ inactive: modalParams.status === 'deActivate' }"
               >
                  {{ modalParams.product?.title }}
               </h3>
            </div>
         </BaseModal>
         <Transition mode="out-in">
            <div v-if="!isLoading" class="flex flex-col gap-4">
               <ProfileProductCard
                  v-for="product in products"
                  :key="product.id"
                  :product="product"
                  @deActivate="deActivateProduct"
                  @activate="activateProduct"
               />
            </div>
            <main v-else class="flex flex-col gap-4">
               <BaseSkeletonCard v-for="n in 3" />
            </main>
         </Transition>
      </template>
   </NavigationPageDecorator>
</template>

<script setup lang="ts">
import BaseSkeletonCard from "@/components/BaseSkeletonCard.vue";
import ProfileProductCard from "@/components/ProfileProductCard.vue";
import ProductRepo from "@shared/entities/Product/ProductRepo";
import { preloadImages } from "@/modules/Helpers";
import { computed, inject, nextTick, onMounted, ref } from "vue";
import NavigationPageDecorator from "@/components/NavigationPageDecorator.vue";
import { isTMA } from "@tma.js/bridge";
import { Camera, EyeOff, Eye, Megaphone } from "lucide-vue-next";
import { useFetchDecorator } from "@shared/composables/useFetch";
import { IProduct } from "@shared/types";
const isImagesReady = ref(false);
const userData: any = inject("userData");
const isOpen = ref(false);
const header = ref<HTMLElement>();
function onTabChange(status) {
   fetchProducts(status.item.status);
}

var headerHeight = ref(1000);

const modalParams = ref({
   status: "deActivate" as "deActivate" | "activate",
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

const user = computed(() => {
   if (isTMA()) {
      return userData?.tgWebAppData.user;
   } else {
      return {
         photo_url: null,
         first_name: "Bumin",
         last_name: "Xoqon",
         username: "Ruzzifer",
      };
   }
});

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
