<template>
   <!-- "Matn bilan e'lon": guruhga yozgandek matn → AI formani to'ldiradi → foydalanuvchi tekshirib joylaydi -->
   <div>
      <div
         v-if="pending"
         class="flex items-center gap-2 rounded-2xl border border-(--z-primary)/40 bg-(--z-primary)/10 px-3 py-2"
      >
         <Sparkles class="size-4 shrink-0 text-(--z-primary)" />
         <p class="flex-1 min-w-0 text-xs leading-snug line-clamp-2">
            {{ $t(aiDraft.vitrinaId() ? "vitrina.publish.pending" : "aiDraft.pending", { title: pending.draft.title }) }}
         </p>
         <button
            type="button"
            class="shrink-0 rounded-full p-1 text-(--z-muted-text) active:bg-(--z-muted)"
            :aria-label="$t('common.cancel')"
            @click="discard"
         >
            <X class="size-4" />
         </button>
      </div>

      <!-- Bugungi urinishlar tugagan: oyna ochilmaydi, nima qilish kerakligi aytiladi -->
      <div
         v-else-if="remaining === 0"
         class="flex items-start gap-3 rounded-2xl border border-(--z-border) bg-(--z-card) p-3"
      >
         <span class="size-10 shrink-0 rounded-xl bg-(--z-muted) text-(--z-muted-text) flex items-center justify-center">
            <Sparkles class="size-5" />
         </span>
         <span class="flex-1 min-w-0">
            <span class="block text-sm font-bold text-(--z-muted-text)">{{ $t("aiDraft.cardTitle") }}</span>
            <span class="block text-xs text-(--z-muted-text) leading-snug">
               {{ $t("aiDraft.limitReached", { max: limit }) }}
            </span>
         </span>
      </div>

      <button
         v-else
         type="button"
         class="w-full flex items-center gap-3 rounded-2xl border border-(--z-border) bg-(--z-card) p-3 text-left transition active:scale-[0.99]"
         @click="open = true"
      >
         <span class="size-10 shrink-0 rounded-xl bg-(--z-primary)/15 text-(--z-primary) flex items-center justify-center">
            <Sparkles class="size-5" />
         </span>
         <span class="flex-1 min-w-0">
            <span class="block text-sm font-bold">{{ $t("aiDraft.cardTitle") }}</span>
            <span class="block text-xs text-(--z-muted-text) leading-snug">{{ $t("aiDraft.cardText") }}</span>
            <!-- Kunlik limit oldindan ko'rinib tursin: "Kuniga 5 marta · bugun 3 ta qoldi" -->
            <span
               v-if="remaining !== null"
               class="mt-1.5 inline-flex items-center rounded-full bg-(--z-primary)/12 px-2 py-0.5 text-[11px] font-semibold text-(--z-primary)"
            >
               {{ $t("aiDraft.quota", { max: limit, n: remaining }) }}
            </span>
         </span>
         <ChevronRight class="size-4 shrink-0 text-(--z-muted-text)" />
      </button>

      <BaseDrawer :open="open" :title="$t('aiDraft.title')" :description="$t('aiDraft.description')" @close="close">
         <div v-if="duplicate" class="flex flex-col gap-3">
            <div class="rounded-xl border border-amber-500/40 bg-amber-500/10 p-3">
               <p class="text-sm font-semibold">{{ $t("aiDraft.duplicateTitle") }}</p>
               <p class="mt-1 text-sm text-(--z-muted-text) line-clamp-2">«{{ duplicate.duplicate?.title }}»</p>
            </div>
            <BaseButton class="w-full" @click="viewExisting">
               <template #icon><Eye class="size-4" /></template>
               {{ $t("create.view") }}
            </BaseButton>
            <BaseButton class="w-full" severity="secondary" @click="useDraft(duplicate)">
               {{ $t("aiDraft.continueAnyway") }}
            </BaseButton>
         </div>

         <div v-else class="flex flex-col gap-2">
            <div class="field py-3!">
               <textarea
                  ref="textarea"
                  v-model="text"
                  rows="6"
                  :maxlength="MAX_LENGTH"
                  :placeholder="$t('common.example', { value: $t('aiDraft.placeholder') })"
                  :disabled="busy"
                  class="block w-full resize-none bg-transparent text-sm leading-[1.4] text-(--z-foreground) placeholder:text-sm placeholder:text-(--z-muted-text) focus:outline-none"
               />
            </div>
            <div class="flex justify-between gap-2 text-xs text-(--z-muted-text)">
               <span>{{ remaining !== null ? $t("aiDraft.quota", { max: limit, n: remaining }) : "" }}</span>
               <span>{{ text.length }} / {{ MAX_LENGTH }}</span>
            </div>

            <!-- Rasmlar: AI ularni o'qimaydi, faqat formaga o'tkaziladi -->
            <div class="mt-2">
               <div class="mb-1.5 flex items-baseline justify-between gap-2">
                  <span class="text-sm font-semibold">{{ $t("aiDraft.photos") }}</span>
                  <span class="text-xs text-(--z-muted-text) tabular-nums">
                     {{ photos.length }} / {{ AI_DRAFT_MAX_IMAGES }}
                  </span>
               </div>
               <div class="grid grid-cols-4 gap-1.5">
                  <div
                     v-for="(photo, index) in photos"
                     :key="photo.url"
                     class="relative aspect-square overflow-hidden rounded-xl border border-(--z-border)"
                  >
                     <img :src="photo.url" :alt="$t('imageField.selected')" class="size-full object-cover" />
                     <button
                        type="button"
                        class="absolute top-1 right-1 size-6 rounded-full bg-black/60 text-white flex items-center justify-center"
                        :aria-label="$t('imageField.remove')"
                        :disabled="busy"
                        @click="removePhoto(index)"
                     >
                        <X class="size-3.5" />
                     </button>
                  </div>
                  <button
                     v-if="photos.length < AI_DRAFT_MAX_IMAGES"
                     type="button"
                     class="aspect-square rounded-xl border border-dashed border-(--z-border) text-(--z-muted-text) flex flex-col items-center justify-center gap-1 active:bg-(--z-muted)"
                     :disabled="busy"
                     @click="photoInput?.click()"
                  >
                     <ImagePlus class="size-5" />
                     <span class="px-1 text-center text-[10px] font-medium leading-tight">{{ $t("aiDraft.addPhoto") }}</span>
                  </button>
               </div>
               <p class="mt-1.5 text-xs text-(--z-muted-text) leading-snug">{{ $t("aiDraft.photosHint") }}</p>
               <input
                  ref="photoInput"
                  type="file"
                  accept="image/jpeg,image/png,image/webp"
                  multiple
                  class="hidden"
                  @change="onPhotosPicked"
               />
            </div>
            <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
         </div>

         <template v-if="!duplicate" #footer>
            <BaseButton class="w-full" :loading="busy" :disabled="text.trim().length < MIN_LENGTH" @click="prepare">
               <template #icon><Sparkles class="size-4" /></template>
               {{ busy ? $t("aiDraft.loading") : $t("aiDraft.submit") }}
            </BaseButton>
         </template>
      </BaseDrawer>
   </div>
</template>

<script setup lang="ts">
import { nextTick, onMounted, onUnmounted, ref, shallowRef, watch } from "vue";
import { useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import { ChevronRight, Eye, ImagePlus, Sparkles, X } from "lucide-vue-next";
import BaseDrawer from "@shared/ui/BaseDrawer.vue";
import BaseButton from "@shared/ui/BaseButton.vue";
import ProductRepo from "@shared/entities/Product/ProductRepo";
import type { IAiDraftResponse } from "@shared/types";
import { AI_DRAFT_MAX_IMAGES, useAiDraft } from "@/features/product/aiDraft";

// Server bilan bir xil chegaralar (ProductDraftController)
const MIN_LENGTH = 5;
const MAX_LENGTH = 1500;

const { t } = useI18n();
const router = useRouter();
const aiDraft = useAiDraft();

const open = ref(false);
const text = ref("");
const busy = ref(false);
const error = ref("");
const remaining = ref<number | null>(null);
const limit = ref(5);
const duplicate = shallowRef<IAiDraftResponse | null>(null);
// Formadan "orqaga" qaytganda: tayyor qoralama bo'lim tanlashda ham ishlatiladi
const pending = shallowRef<IAiDraftResponse | null>(aiDraft.current());
const textarea = ref<HTMLTextAreaElement | null>(null);

// Kunlik limit: karta bosilishidan oldin ko'rinsin (admin uchun null — ko'rsatilmaydi)
onMounted(async () => {
   try {
      const { data } = await ProductRepo.aiDraftQuota();
      limit.value = data.limit;
      remaining.value = data.remaining;
   } catch {
      // Ko'rsatilmasa ham bo'ladi: server baribir limitni tekshiradi
   }
});

/* ---------- Rasmlar ---------- */

const photoInput = ref<HTMLInputElement | null>(null);
const photos = shallowRef<{ file: File; url: string }[]>([]);

function onPhotosPicked(event: Event) {
   const input = event.target as HTMLInputElement;
   const files = Array.from(input.files ?? []).slice(0, AI_DRAFT_MAX_IMAGES - photos.value.length);
   photos.value = [...photos.value, ...files.map((file) => ({ file, url: URL.createObjectURL(file) }))];
   input.value = "";
}

function removePhoto(index: number) {
   URL.revokeObjectURL(photos.value[index].url);
   photos.value = photos.value.filter((_, i) => i !== index);
}

// Faqat oldindan ko'rish havolalari tozalanadi; File'larning o'zi qoralama bilan formaga o'tadi
function releasePhotos() {
   photos.value.forEach((photo) => URL.revokeObjectURL(photo.url));
   photos.value = [];
}
onUnmounted(releasePhotos);

watch(open, async (isOpen) => {
   if (!isOpen) return;
   await nextTick();
   textarea.value?.focus();
});

function close() {
   if (busy.value) return;
   open.value = false;
   duplicate.value = null;
}

async function prepare() {
   busy.value = true;
   error.value = "";
   try {
      const { data } = await ProductRepo.aiDraft(text.value.trim());
      remaining.value = data.remaining;
      if (data.duplicate) {
         duplicate.value = data;
         return;
      }
      useDraft(data);
   } catch (exception: any) {
      if (exception?.response?.status === 429) remaining.value = 0;
      error.value = exception?.response?.data?.message || t("common.error");
   } finally {
      busy.value = false;
   }
}

function useDraft(response: IAiDraftResponse) {
   aiDraft.set(
      response,
      photos.value.map((photo) => photo.file),
   );
   releasePhotos();
   pending.value = response;
   open.value = false;
   duplicate.value = null;
   text.value = "";
   router.push({
      name: "create-product",
      params: { categoryId: response.draft.category_id },
      query: { ai: "1" },
   });
}

function viewExisting() {
   const id = duplicate.value?.duplicate?.id;
   open.value = false;
   duplicate.value = null;
   if (id) router.push({ name: "product-id", params: { id: String(id) } });
}

function discard() {
   aiDraft.clear();
   pending.value = null;
}
</script>
