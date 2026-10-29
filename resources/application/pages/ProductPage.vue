<template>
   <section class="full-page flex flex-col">
      <aside v-if="product" class="grow relative -mt-[calc(var(--safe-area-top)+var(--spacing)*4)]">
         <article ref="scrollContainer" class="absolute inset-0 overflow-y-auto no-scrollbar">
            <main class="relative">
               <swiper
                  v-if="product?.images.length"
                  class="aspect-video w-full"
                  @swiper="(instance) => (heroSwiper = instance)"
                  @slide-change="(instance) => (heroIndex = instance.activeIndex)"
               >
                  <swiper-slide v-for="(image, index) in product.images" :key="image.id">
                     <button
                        type="button"
                        class="h-full w-full cursor-zoom-in"
                        :aria-label="$t('product.photos')"
                        @click="openImagePreview(index)"
                     >
                        <ProductImageView
                           :src="`/storage/${image.src}`"
                           :crop-x="image.crop_x"
                           :crop-y="image.crop_y"
                           :crop-scale="image.crop_scale"
                           class="h-full w-full"
                           :alt="$t('product.photo')"
                        />
                     </button>
                  </swiper-slide>
               </swiper>
               <!-- Rasm soni va "kattalashtirish" belgisi -->
               <button
                  v-if="product?.images.length"
                  type="button"
                  class="absolute bottom-3 right-3 z-10 h-7 px-2.5 inline-flex items-center gap-1.5 rounded-full bg-black/45 text-white text-xs font-semibold tabular-nums backdrop-blur-md border border-white/15 cursor-pointer active:scale-95 transition"
                  :aria-label="$t('product.photos')"
                  @click="openImagePreview(heroIndex)"
               >
                  <Expand class="size-3.5" />
                  {{ heroIndex + 1 }} / {{ product.images.length }}
               </button>
               <div
                  v-else
                  class="w-full aspect-21/9 flex flex-col items-center justify-center bg-slate-50 dark:bg-zinc-800/50 border-b border-(--z-border)/30 select-none py-6"
               >
                  <div
                     :class="[categoryVisual.tile, categoryVisual.text]"
                     class="size-13 rounded-full flex items-center justify-center mb-2 shadow-xs"
                  >
                     <component :is="categoryVisual.icon" class="size-6" />
                  </div>
                  <span class="text-xs font-medium text-slate-500 dark:text-zinc-400">
                     {{ product.category?.name || $t("product.noPhoto") }}
                  </span>
               </div>
               <!-- <img

                  class="h-64 w-full bg-(--z-border) rounded-tl-[10px] rounded-tr-[10px]"
                  :src="'/images/no-image.webp'"
                  alt="No Image"
               /> -->
            </main>
            <main class="pt-5 px-4">
               <!-- Kategoriya chapda, ulashish o'ngda: rasmdan keyingi birinchi qator — ko'rinadi, lekin narxga xalal bermaydi -->
               <div class="flex items-center justify-between gap-3 mb-2">
                  <button
                     v-if="product?.category"
                     type="button"
                     @click="router.push({ name: 'category', params: { id: product.category.id } })"
                     class="min-w-0 truncate text-xs text-(--z-muted-text)"
                  >
                     <span>{{ product.category.name }}</span>
                  </button>
                  <span v-else />
                  <BaseButton
                     icon-only
                     rounded
                     size="sm"
                     severity="secondary"
                     class="-my-1 shrink-0"
                     :aria-label="isLinkCopied ? $t('product.copied') : $t('product.share')"
                     @click="shareProduct"
                  >
                     <template #icon><component :is="isLinkCopied ? Check : Share2" class="size-4" /></template>
                  </BaseButton>
               </div>

               <!--
                  O'qish tartibi "nima → qancha": sarlavha birinchi, narx ostida.
                  Ierarxiyani o'lcham emas, qalinlik beradi — ko'z baribir avval narxga tushadi.
               -->
               <h1 class="text-lg font-medium leading-snug" :class="product?.price ? 'mb-1.5' : 'mb-4'">
                  {{ product?.title }}
               </h1>

               <div
                  v-if="product?.price"
                  class="flex items-center gap-1 font-extrabold text-2xl tracking-tight tabular-nums mb-4"
                  :class="{ 'flex-row-reverse justify-end': product?.price_type.position === 'left' }"
               >
                  <span>
                     {{ formatPrice(product?.price) }}
                  </span>
                  <span>
                     {{ product?.price_type.type }}
                  </span>
               </div>

               <!--  -->
               <div class="text-(--z-muted-text) text-xs flex gap-2 items-center mb-6">
                  <span class="capitalize">
                     {{ timeAgo(product?.created_at!) }}
                  </span>
                  <span class="inline-block w-1 h-1 rounded-full bg-(--z-muted-text)"> </span>
                  <span class="flex items-center gap-1">{{ $t("common.views", { n: viewCount }, product?.views_count ?? 0) }}</span>
                  <span class="inline-block w-1 h-1 rounded-full bg-(--z-muted-text)"> </span>

                  <span class="flex items-center gap-1">
                     <MapPin class="size-3 inline" />
                     {{ product?.district?.name || $t("district.region") }}
                  </span>
               </div>
               <!--  -->

               <!--  -->
               <div v-if="product?.description">
                  <h3 class="title text-sm">{{ $t("common.description") }}</h3>
                  <div
                     class="py-1 leading-relaxed text-sm mb-6 whitespace-pre-line text-(--z-foreground) wrap-break-word select-text"
                  >
                     {{ product?.description }}
                  </div>
               </div>

               <h3 v-if="product?.parameter_values.length" class="title text-sm mb-2">{{ $t("product.details") }}</h3>
               <aside v-if="product?.parameter_values.length" class="text-sm divide-y divide-(--z-border)">
                  <div v-for="v in product.parameter_values" :key="v.id" class="flex justify-between py-2">
                     <span class="text-(--z-muted-text)">
                        {{ v.parameter.title }}
                     </span>
                     <main class="flex gap-1.5 font-medium">
                        <!-- Tanlov qiymati va birlik foydalanuvchi tilida (server: display_value, unit) -->
                        <span>
                           {{ v.display_value ?? v.value }}
                        </span>
                        <span v-if="displayUnit(v.parameter)">
                           {{ displayUnit(v.parameter) }}
                        </span>
                     </main>
                  </div>
               </aside>

               <hr class="border-(--z-border)" />

               <div class="flex items-center gap-3 my-3">
                  <div
                     class="w-10 h-10 rounded-full bg-(--z-border) flex items-center justify-center text-sm font-bold text-(--z-muted-text)"
                  >
                     {{ product.user.name[0] }}
                  </div>
                  <main class="leading-4">
                     <h3 class="font-medium">{{ product.user.name }}</h3>
                     <span v-if="chatUsername" class="text-(--z-muted-text) text-xs">@{{ chatUsername }}</span>
                     <span v-else class="text-(--z-muted-text) text-xs">{{ $t("product.contactByPhone") }}</span>
                  </main>
               </div>

               <!-- Shikoyat — kamdan-kam kerak: ko'zga tashlanmaydigan, lekin topiladigan joyda -->
               <button
                  v-if="!isOwnProduct"
                  type="button"
                  class="mb-2 inline-flex items-center gap-1.5 text-xs text-(--z-muted-text) cursor-pointer active:opacity-70"
                  @click="openReport"
               >
                  <Flag class="size-3.5" />
                  {{ $t("product.report") }}
               </button>

               <!-- Sotuvchining boshqa e'lonlari: ishonch (faol sotuvchi) va qo'shimcha xarid imkoni -->
               <section v-if="product?.seller_products?.length" class="mt-8">
                  <h3 class="font-bold text-base mb-3.5">{{ $t("product.sellerAds") }}</h3>
                  <swiper
                     :slides-per-view="1.25"
                     :space-between="12"
                     :modules="[FreeMode]"
                     :breakpoints="{
                        '480': { slidesPerView: 1.6, spaceBetween: 12 },
                        '640': { slidesPerView: 2.2, spaceBetween: 14 },
                     }"
                     class="w-full -mx-4"
                  >
                     <swiper-slide v-for="item in product.seller_products" :key="item.id" class="h-auto">
                        <ProductCard :product="item" class="h-full" />
                     </swiper-slide>
                  </swiper>
               </section>

               <!-- Shunga yaqin (O'xshash) e'lonlar - bitta qator karusel -->
               <section v-if="product?.similar_products?.length" class="mt-8 mb-6">
                  <div class="flex items-center justify-between mb-3.5">
                     <div class="flex items-center gap-2">
                        <Sparkles class="size-4 text-amber-500" />
                        <h3 class="font-bold text-base">{{ $t("product.similar") }}</h3>
                     </div>
                     <button
                        v-if="product.category_id"
                        type="button"
                        @click="router.push({ name: 'category', params: { id: product.category_id } })"
                        class="text-xs text-primary font-medium flex items-center gap-0.5 active:opacity-70 cursor-pointer"
                     >
                        {{ $t("common.all") }}
                        <ChevronRight class="size-3 inline" />
                     </button>
                  </div>

                  <swiper
                     :slides-per-view="1.25"
                     :space-between="12"
                     :modules="[FreeMode]"
                     :breakpoints="{
                        '480': { slidesPerView: 1.6, spaceBetween: 12 },
                        '640': { slidesPerView: 2.2, spaceBetween: 14 },
                     }"
                     class="w-full -mx-4"
                  >
                     <swiper-slide
                        v-for="similar in product.similar_products.slice(0, 3)"
                        :key="similar.id"
                        class="h-auto"
                     >
                        <ProductCard :product="similar" class="h-full" />
                     </swiper-slide>
                  </swiper>
               </section>
            </main>
         </article>
      </aside>
      <!-- Agar e'lon yuklanayotgan bo'lsa -->
      <aside v-else-if="isFirstLoading" class="grow">
         <main class="relative -mt-[calc(var(--safe-area-top)+var(--spacing)*4)]">
            <div class="skeleton aspect-video rounded-none!"></div>
         </main>
         <main class="py-5.5 px-4">
            <div class="skeleton h-5.5 mb-6 w-24"></div>
            <div class="skeleton h-4.5 w-full mb-5.5"></div>
            <div class="skeleton h-4 w-2/3 mb-7"></div>
            <div class="skeleton h-3 w-12 mb-3"></div>
            <div class="skeleton h-2 w-4/5 mb-3"></div>
            <div class="skeleton h-2 w-full mb-3"></div>
            <div class="skeleton h-2 w-2/3"></div>
         </main>
      </aside>

      <!-- Agar e'lon topilmasa yoki muddati tugagan bo'lsa -->
      <aside v-else class="grow flex flex-col justify-between p-6">
         <div class="flex items-center justify-between">
            <button
               type="button"
               @click="goBackOrHome"
               class="p-2 -ml-2 rounded-full text-(--z-muted-text) hover:text-(--z-foreground) active:bg-(--z-card)"
               :aria-label="$t('common.back')"
            >
               <ArrowLeft class="size-6" />
            </button>
            <span class="text-xs text-(--z-muted-text) font-medium">Mini Ads</span>
         </div>

         <div class="flex flex-col items-center text-center my-auto px-2">
            <div
               class="w-20 h-20 rounded-full bg-amber-500/10 text-amber-500 flex items-center justify-center mb-5 border border-amber-500/20 shadow-inner"
            >
               <AlertCircle class="size-10" />
            </div>
            <h2 class="text-xl font-bold mb-2">{{ $t("product.notFound") }}</h2>
            <p class="text-sm text-(--z-muted-text) max-w-xs leading-relaxed mb-8">{{ $t("product.notFoundHint") }}</p>

            <div class="w-full flex flex-col gap-3">
               <BaseButton @click="router.push({ name: 'home' })" severity="primary" class="w-full py-3">
                  <template #icon>
                     <Home class="size-4" />
                  </template>
                  {{ $t("common.home") }}
               </BaseButton>
               <BaseButton @click="router.push({ name: 'search' })" severity="secondary" class="w-full py-3">
                  <template #icon>
                     <Search class="size-4" />
                  </template>
                  {{ $t("product.searchOther") }}
               </BaseButton>
            </div>
         </div>

         <div class="py-2"></div>
      </aside>

      <aside v-if="product" class="px-4 pt-4 border-t border-(--z-border) flex gap-4">
         <template v-if="phoneNumber">
            <BaseButton
               v-if="chatUsername"
               severity="secondary"
               @click="openSellerChat(product)"
               iconOnly
               class="aspect-square"
            >
               <template #icon>
                  <MessageCircle class="size-5 inline" />
               </template>
            </BaseButton>
            <BaseButton @click="callPhone(phoneNumber)" severity="primary" class="grow">
               <template #icon>
                  <Phone class="size-4 inline" />
               </template>
               {{ $t("product.call") }}
            </BaseButton>
         </template>
         <template v-else-if="chatUsername">
            <BaseButton @click="openSellerChat(product)" severity="primary" class="grow">
               <template #icon>
                  <MessageCircle class="size-4 inline" />
               </template>
               {{ $t("product.writeTelegram") }}
            </BaseButton>
         </template>

         <BaseButton
            severity="secondary"
            @click="toggleFavorite"
            iconOnly
            class="aspect-square"
            :loading="isFavoriteButtonLoading"
         >
            <template #icon>
               <Heart
                  class="size-5 inline"
                  :class="product.is_favorite ? 'fill-white text-white' : 'text-(--z-foreground)'"
               />
            </template>
         </BaseButton>
      </aside>
      <aside v-else-if="isFirstLoading" class="px-4 pt-4 border-t border-(--z-border) flex gap-4">
         <div class="skeleton h-12 grow"></div>
         <div class="skeleton size-12"></div>
      </aside>

      <ReportDrawer v-if="product" :open="isReportOpen" :product-id="product.id" @close="isReportOpen = false" />

      <ImageLightbox
         v-if="product?.images.length"
         v-model="previewIndex"
         :images="previewImages"
         @slide="(index) => heroSwiper?.slideTo(index, 0)"
      />
   </section>
</template>

<script setup lang="ts">
import { formatPrice, formatProductPrice, timeAgo } from "@shared/modules/formatters";
import { useCategoryTheme } from "@/features/category/categoryTheme";
import { isTMA } from "@tma.js/bridge";
import ProductRepo from "@shared/entities/Product/ProductRepo";
import ProductImageView from "@shared/ui/ProductImageView.vue";
import BaseButton from "@shared/ui/BaseButton.vue";
import ProductCard from "@/features/product/ProductCard.vue";
import ImageLightbox from "@/features/product/ImageLightbox.vue";
import { FreeMode } from "swiper/modules";
import { Swiper, SwiperSlide } from "swiper/vue";
import type { Swiper as SwiperType } from "swiper";
import { useRoute, useRouter } from "vue-router";
import { useFetchDecorator } from "@shared/composables/useFetch";
import { computed, onMounted, ref, shallowRef, watch } from "vue";
import { IProduct } from "@shared/types";
import { preloadImages } from "@shared/modules/helpers";
import ReportDrawer from "@/features/product/ReportDrawer.vue";
import { useAuth } from "@shared/store/useAuth";
import { displayUnit } from "@shared/modules/productSpecs";
import { t } from "@shared/i18n";
import {
   AlertCircle,
   ArrowLeft,
   Check,
   ChevronRight,
   Expand,
   Flag,
   Heart,
   Share2,
   Home,
   MapPin,
   MessageCircle,
   Phone,
   Search,
   Sparkles,
} from "lucide-vue-next";
import FavoriteRepo from "@shared/entities/Favorite/FavoriteRepo";
import { requireAuth } from "@/features/auth/requireAuth";
import { goBack } from "@/modules/navigation";
import { track } from "@/modules/activity";
import { postEvent } from "@tma.js/bridge";

const route = useRoute();
const router = useRouter();
const scrollContainer = ref<HTMLElement | null>(null);

const { data: product, execute: executeProduct, isFirstLoading } = useFetchDecorator<IProduct>(ProductRepo.show);

// Kartadagi bilan bir xil: asosiy bo'lim belgisi (nomdan taxmin qilinmaydi)
const categoryTheme = useCategoryTheme();
const categoryVisual = computed(() => categoryTheme(product.value?.category));

function goBackOrHome() {
   goBack(router);
}

const isImagesReady = ref(false);
const previewIndex = ref<number | null>(null);
const heroSwiper = shallowRef<SwiperType | null>(null);
const heroIndex = ref(0);

// Preview original rasmni ko'rsatadi (galereyadagi crop'siz)
const previewImages = computed(() =>
   (product.value?.images ?? []).map((image) => ({ id: image.id, src: `/storage/${image.src}` })),
);

function openImagePreview(index: number) {
   previewIndex.value = index;
}

function callPhone(phone: string) {
   track("contact_phone", product.value?.id);
   if (isTMA()) {
      window.open(`tel:${phone}`);
   } else {
      window.location.href = `tel:${phone}`;
   }
}

const isBotProduct = computed(() => {
   const username = product.value?.user?.username?.toLowerCase();
   return username === "telegramassistent" || username === "telegram_bot";
});

// Kanaldan olingan e'londa raqam bo'lmasa, "telefon" o'rnida muallifning @nick'i turadi
const contactNick = computed(() => {
   const phone = product.value?.phone?.trim() ?? "";
   return phone.startsWith("@") ? phone.slice(1) : null;
});
const phoneNumber = computed(() => (product.value?.phone && !contactNick.value ? product.value.phone : null));
// Kimga yozish: e'londagi @nick, bo'lmasa e'lon egasining username'i (bot akkauntiga emas)
const chatUsername = computed(
   () => contactNick.value || (!isBotProduct.value ? product.value?.user?.username?.trim().replace(/^@/, "") : null) || null,
);

function openSellerChat(product: IProduct) {
   const username = chatUsername.value;

   if (!username) return;
   track("contact_telegram", product.id);

   const text = t("product.chatGreeting", { title: product.title });

   const pathFull = `${username}?text=${encodeURIComponent(text)}`;

   if (!isTMA()) {
      // Brauzerda: Telegram ilovasi yoki web.telegram orqali ochiladi
      window.open(`https://t.me/${pathFull}`, "_blank", "noopener");
      return;
   }

   postEvent("web_app_open_tg_link", {
      path_full: pathFull,
   });
}

const viewCount = computed(() => {
   const count = product.value?.views_count ?? 0;

   if (count >= 1_000_000) {
      const value = Math.floor(count / 100_000) / 10; // 1.2M
      return `${Number.isInteger(value) ? value.toFixed(0) : value}M`;
   }

   if (count >= 1_000) {
      const value = Math.floor(count / 100) / 10; // 1.5K
      return `${Number.isInteger(value) ? value.toFixed(0) : value}K`;
   }

   return count;
});

const authStore = useAuth();
const isOwnProduct = computed(() => !!product.value && authStore.user?.id === product.value.user_id);

/* ---------- Ulashish va shikoyat ---------- */

// Telegram'da ochiladigan havola: bot e'lonni to'g'ridan-to'g'ri mini app'da ochadi (app.ts → startapp=product_ID)
const TELEGRAM_APP_URL = import.meta.env.VITE_TELEGRAM_APP_URL || "https://t.me/NavoiyElonBot";
const isLinkCopied = ref(false);
const isReportOpen = ref(false);

async function shareProduct() {
   if (!product.value) return;
   const url = `${TELEGRAM_APP_URL}?startapp=product_${product.value.id}`;
   const price = formatProductPrice(product.value.price, product.value.price_type, "");
   const text = price ? `${product.value.title} — ${price}` : product.value.title;

   // Telegram ichida — tanish "kimga yuborish" oynasi
   if (isTMA()) {
      postEvent("web_app_open_tg_link", {
         path_full: `/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(text)}`,
      });
      return;
   }

   try {
      if (navigator.share) {
         await navigator.share({ title: product.value.title, text, url });
         return;
      }
      await navigator.clipboard.writeText(`${text}\n${url}`);
      isLinkCopied.value = true;
      setTimeout(() => (isLinkCopied.value = false), 2000);
   } catch {
      // Foydalanuvchi ulashish oynasini yopdi — xato emas
   }
}

function openReport() {
   if (!requireAuth()) return;
   isReportOpen.value = true;
}

const isFavoriteButtonLoading = ref(false);

async function toggleFavorite() {
   if (!product.value || !requireAuth()) return;
   isFavoriteButtonLoading.value = true;

   if (product.value.is_favorite) {
      await FavoriteRepo.delete(product.value.id).finally(() => {
         isFavoriteButtonLoading.value = false;
      });
   } else {
      await FavoriteRepo.store(product.value.id).finally(() => {
         isFavoriteButtonLoading.value = false;
      });
   }
   product.value.is_favorite = !product.value.is_favorite;
}

async function loadProduct(id: string | number | string[]) {
   try {
      isImagesReady.value = false;
      await executeProduct(id);
      if (product.value?.images?.length) {
         const imageUrls = product.value.images.flatMap((img) => [`/storage/${img.src}`]);
         await preloadImages(imageUrls);
      }

      setTimeout(() => {
         isImagesReady.value = true;
      }, 350);
   } catch (error) {
      console.warn("E'lon topilmadi yoki yuklanmadi:", error);
   }
}

watch(
   () => route.params.id,
   (newId) => {
      if (newId) {
         loadProduct(newId);
         scrollContainer.value?.scrollTo({ top: 0, behavior: "smooth" });
      }
   },
);

onMounted(async () => {
   if (route.params.id) {
      await loadProduct(route.params.id);
   }
});
</script>
