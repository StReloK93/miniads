<template>
   <article
      class="flex flex-col p-2 bg-(--z-card) border border-(--z-border) rounded-(--z-rounded) cursor-pointer select-none transition active:scale-[0.99]"
      @click="$router.push({ name: 'product-id', params: { id: product.id } })"
   >
      <!-- Faol bo'lmagan e'lon ma'lumoti xiralashadi; footer'dagi tugmalar esa aniq qoladi -->
      <div class="flex gap-3" :class="{ 'opacity-50': inactive }">
         <!--
            Chapda bir xil o'lchamdagi plitka: ko'z avval shu yerga tushadi (F-naqsh).
            Rasm bo'lsa — rasm; bo'lmasa (ish, xizmat, ta'lim) — bo'lim rangidagi plitka va katta belgi.
            O'lcham bir xil bo'lgani uchun lentadagi matnlar bitta chiziqda turadi.
         -->
         <div v-if="mainImage" class="relative size-28 shrink-0 rounded-[calc(var(--z-rounded)-0.375rem)] overflow-hidden">
            <ProductImageView
               :src="`/storage/${mainImage.thumb_src || mainImage.src}`"
               :crop-x="mainImage.crop_x"
               :crop-y="mainImage.crop_y"
               :crop-scale="mainImage.crop_scale"
               class="size-full"
               alt="E'lon rasmi"
            />
            <span
               v-if="imageCount > 1"
               class="absolute bottom-1.5 right-1.5 h-5 px-1.5 inline-flex items-center gap-1 rounded-full bg-black/45 text-white text-[10px] font-semibold tabular-nums backdrop-blur-md"
            >
               <Images class="size-3" />
               {{ imageCount }}
            </span>
            <!-- Bo'lim belgisi rasm burchagida: matn ustunini band qilmaydi, sarlavha tepada qoladi -->
            <span
               v-if="showCategory"
               class="absolute top-1.5 left-1.5 size-6 grid place-items-center rounded-full z-glass-strong"
               :class="theme.text"
               :title="categoryLabel ?? undefined"
            >
               <component :is="theme.icon" class="size-3.5" stroke-width="2" />
            </span>
         </div>
         <div
            v-else
            class="relative size-28 shrink-0 rounded-[calc(var(--z-rounded)-0.375rem)] grid place-items-center overflow-hidden"
            :class="[theme.tile, theme.text]"
            aria-hidden="true"
         >
            <component :is="theme.icon" class="size-9 opacity-90" stroke-width="1.5" />
            <!-- Yorug' dog': plitka tekis rang emas, shisha bo'lagi bo'lib ko'rinsin -->
            <span class="absolute -top-6 -left-6 size-16 rounded-full bg-white/40 blur-xl dark:bg-white/10" />
         </div>

         <div class="min-w-0 grow flex flex-col py-0.5">
            <!-- Sarlavha ustunning eng tepasida — rasm bilan bir chiziqda boshlanadi -->
            <h3 class="text-sm font-semibold leading-snug line-clamp-2">{{ product.title }}</h3>

            <!-- Narx — ro'yxatni ko'z bilan "skanerlash"da asosiy tayanch, shuning uchun eng yirik -->
            <p v-if="price" class="mt-1 text-[17px] leading-tight font-bold tabular-nums tracking-tight">{{ price }}</p>

            <!-- Asosiy xususiyatlar: e'lonni ochmasdan mos-mosmasligini bilish uchun -->
            <p v-if="specs.length" class="mt-1 text-xs text-(--z-foreground)/70 truncate">
               {{ specs.join(" · ") }}
            </p>

            <!-- Pastki qator: qayerda va qachon; yurakcha o'ng pastda — bosh barmoq yetadigan joyda -->
            <div class="mt-auto pt-1.5 flex items-center justify-between gap-2">
               <p class="min-w-0 inline-flex items-center gap-1 text-[11px] text-(--z-muted-text)">
                  <MapPin class="size-3 shrink-0" />
                  <span class="truncate">{{ product.district?.name || "Butun viloyat" }}</span>
                  <span class="shrink-0">· {{ timeAgo(timeValue) }}</span>
               </p>

               <!-- E'lonlarimda: sotuvchi uchun asosiy ko'rsatkich — necha marta ko'rilgan -->
               <span
                  v-if="showViews"
                  class="shrink-0 inline-flex items-center gap-1 text-[11px] font-medium text-(--z-muted-text) tabular-nums"
               >
                  <Eye class="size-3.5" />
                  {{ (product.views_count ?? 0).toLocaleString("ru-RU") }}
               </span>

               <button
                  v-if="showFavorite"
                  type="button"
                  class="size-8 -my-1.5 -mr-1 shrink-0 grid place-items-center rounded-full cursor-pointer transition active:scale-90 hover:bg-(--z-muted) disabled:opacity-50"
                  :aria-label="product.is_favorite ? 'Sevimlilardan olib tashlash' : 'Sevimlilarga qo\'shish'"
                  :disabled="isFavoriteButtonLoading"
                  @click.stop="toggleFavorite"
               >
                  <Heart
                     class="size-4.5 transition-colors"
                     :class="product.is_favorite ? 'fill-(--z-foreground) text-(--z-foreground)' : 'text-(--z-muted-text)'"
                  />
               </button>
            </div>
         </div>
      </div>

      <!-- Kartaning to'liq kengligidagi qo'shimcha qator (profilda: qolgan kunlar va amallar) -->
      <slot name="footer" />
   </article>
</template>

<script setup lang="ts">
import { Eye, Heart, Images, MapPin } from "lucide-vue-next";
import { formatProductPrice, timeAgo } from "@shared/modules/formatters";
import { productSpecs } from "@shared/modules/productSpecs";
import { useCategoryTheme } from "@/features/category/categoryTheme";
import { IProduct } from "@shared/types";
import { computed, ref } from "vue";
import FavoriteRepo from "@shared/entities/Favorite/FavoriteRepo";
import { requireAuth } from "@/features/auth/requireAuth";
import ProductImageView from "@shared/ui/ProductImageView.vue";

/**
 * Ro'yxatlardagi e'lon kartasi. Ko'z harakati tartibida:
 * rasm → kategoriya → sarlavha → narx → asosiy xususiyatlar → shahar · vaqt.
 * Slot: `footer` — karta pastiga qo'shimcha qator (profilda qolgan kunlar va amallar).
 */
const props = withDefaults(
   defineProps<{
      product: IProduct;
      showFavorite?: boolean;
      showCategory?: boolean;
      /** Ko'rishlar soni (E'lonlarim sahifasi) */
      showViews?: boolean;
      inactive?: boolean;
      timeField?: "published_at" | "created_at";
   }>(),
   {
      showFavorite: true,
      showCategory: true,
      showViews: false,
      inactive: false,
      timeField: "created_at",
   },
);

const mainImage = computed(() => props.product.images?.[0] ?? null);
const imageCount = computed(() => props.product.images?.length ?? 0);

// Narxsiz e'londa (ish, topilma, bepul) "Kelishiladi" yozmaymiz — bo'sh qator yaxshiroq
const price = computed(() => formatProductPrice(props.product.price, props.product.price_type, ""));
const specs = computed(() => productSpecs(props.product.parameter_values));

const timeValue = computed(() => {
   if (props.timeField === "published_at") {
      return props.product.published_at || props.product.created_at;
   }
   return props.product.created_at || props.product.published_at;
});

const categoryTheme = useCategoryTheme();
const theme = computed(() => categoryTheme(props.product.category));

// "Uy-joy · Kvartira": quyi kategoriya ("Sotuv", "Ijara") yolg'iz o'zi tushunarsiz bo'lishi mumkin
const categoryLabel = computed(() => {
   const leaf = props.product.category?.name;
   const root = theme.value.rootName;
   if (!root || root === leaf) return leaf ?? root;
   return leaf ? `${root} · ${leaf}` : root;
});

const isFavoriteButtonLoading = ref(false);

async function toggleFavorite() {
   if (!requireAuth()) return;
   isFavoriteButtonLoading.value = true;
   try {
      if (props.product.is_favorite) {
         await FavoriteRepo.delete(props.product.id);
      } else {
         await FavoriteRepo.store(props.product.id);
      }
      props.product.is_favorite = !props.product.is_favorite;
   } finally {
      isFavoriteButtonLoading.value = false;
   }
}
</script>
