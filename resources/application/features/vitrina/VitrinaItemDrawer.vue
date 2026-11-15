<template>
   <BaseDrawer :open="!!item" :title="item?.title ?? ''" @close="emit('close')">
      <div v-if="item" class="flex flex-col gap-4">
         <!-- Rasmlar: yonga suriladi, bosilsa to'liq ekranda -->
         <div v-if="images.length" class="photos">
            <button
               v-for="(src, index) in images"
               :key="src"
               type="button"
               class="photo"
               :class="{ 'is-single': images.length === 1 }"
               @click="previewIndex = index"
            >
               <img :src="src" :alt="item.title" class="size-full object-cover" />
            </button>
         </div>

         <div>
            <p v-if="price" class="text-xl font-extrabold tracking-tight tabular-nums">{{ price }}</p>
            <p v-if="!item.is_available" class="mt-1 text-sm text-(--z-muted-text)">{{ $t("vitrina.unavailable") }}</p>
         </div>

         <p v-if="item.description" class="text-sm leading-relaxed whitespace-pre-line select-text">{{ item.description }}</p>
      </div>

      <template v-if="item && (telegram || phone)" #footer>
         <div class="flex gap-3">
            <BaseButton v-if="phone" :severity="telegram ? 'secondary' : 'primary'" :icon-only="!!telegram" class="shrink-0" :class="{ 'grow': !telegram }" @click="callPhone(phone)">
               <template #icon><Phone class="size-4" /></template>
               <template v-if="!telegram">{{ $t("product.call") }}</template>
            </BaseButton>
            <BaseButton v-if="telegram" class="grow" @click="ask">
               <template #icon><MessageCircle class="size-4" /></template>
               {{ $t("vitrina.ask") }}
            </BaseButton>
         </div>
      </template>
   </BaseDrawer>

   <ImageLightbox v-if="images.length" v-model="previewIndex" :images="lightboxImages" />
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { MessageCircle, Phone } from "lucide-vue-next";
import { t } from "@shared/i18n";
import BaseDrawer from "@shared/ui/BaseDrawer.vue";
import BaseButton from "@shared/ui/BaseButton.vue";
import ImageLightbox from "@/features/product/ImageLightbox.vue";
import type { IVitrinaItem } from "@shared/types";
import { callPhone, itemPrice, openTelegram, storageUrl } from "@/features/vitrina/vitrinaKit";

const props = defineProps<{ item: IVitrinaItem | null; telegram?: string | null; phone?: string | null }>();
const emit = defineEmits<{ (e: "close"): void }>();

const previewIndex = ref<number | null>(null);
const images = computed(() => (props.item?.images ?? []).map((path) => storageUrl(path)!));
const lightboxImages = computed(() => images.value.map((src, index) => ({ id: index, src })));
const price = computed(() => (props.item ? itemPrice(props.item) : ""));

watch(
   () => props.item,
   () => (previewIndex.value = null),
);

/** Sotuvchiga tayyor xabar bilan: qaysi mahsulot haqida so'rayotgani darhol ko'rinadi */
function ask() {
   if (!props.item || !props.telegram) return;
   openTelegram(props.telegram, t("vitrina.askText", { title: props.item.title }));
}
</script>

<style scoped>
.photos {
   display: flex;
   gap: 0.5rem;
   margin-inline: -1rem;
   padding-inline: 1rem;
   overflow-x: auto;
   scroll-snap-type: x mandatory;
   scrollbar-width: none;

   &::-webkit-scrollbar {
      display: none;
   }
}

.photo {
   width: 80%;
   flex-shrink: 0;
   aspect-ratio: 4 / 3;
   overflow: hidden;
   border-radius: var(--z-rounded);
   background: var(--z-muted);
   scroll-snap-align: center;
   cursor: zoom-in;

   &.is-single {
      width: 100%;
   }
}
</style>
