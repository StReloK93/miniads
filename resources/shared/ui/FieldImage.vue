<template>
   <Field :name="props.name" v-slot="{ field }" class="flex flex-col gap-1">
      <main class="relative">
         <span class="text-xs text-gray-500 absolute -top-5 right-1.5">
            {{ imagesSource.length }} / {{ props.max }}
         </span>
         <div :class="{ 'grid gap-1 grid-cols-6': attrs.multiple }">
            <main
               v-for="(image, index) in imagesSource"
               :key="image.id ?? image.url"
               :class="[index === 0 ? 'col-span-6' : 'col-span-3']"
               class="relative overflow-hidden rounded-(--z-rounded) aspect-video"
            >
               <ProductImageView
                  :src="image.url"
                  :crop-x="image.crop_x"
                  :crop-y="image.crop_y"
                  :crop-scale="image.crop_scale"
                  class="h-full w-full"
                  alt="Tanlangan e'lon rasmi"
               />
               <div class="absolute! bottom-1 right-1 z-10 flex gap-2">
                  <BaseButton
                     @click="deleteImage(index, field)"
                     rounded
                     severity="danger"
                     icon-only
                     size="sm"
                     aria-label="Rasmni o'chirish"
                  >
                     <template #icon>
                        <Trash class="size-5" />
                     </template>
                  </BaseButton>
                  <BaseButton
                     @click="openCropEditor(image, field)"
                     rounded
                     severity="glass"
                     
                     icon-only
                     size="sm"
                     aria-label="Rasmni kesish"
                  >
                     <template #icon>
                        <Crop class="size-5 " />
                     </template>
                  </BaseButton>
               </div>
            </main>

            <label
               v-if="canAddMore"
               :class="[imagesSource.length > 0 ? 'aspect-video col-span-3' : 'col-span-6 aspect-video']"
               class="cursor-pointer flex justify-center items-center rounded-(--z-rounded) bg-(--z-primary)/5 border border-(--z-primary) border-dashed"
            >
               <input
                  ref="inputFile"
                  type="file"
                  accept="image/jpeg,image/png,image/webp"
                  v-bind="$attrs"
                  @change="(event) => onNativeFileChange(event, field)"
                  class="hidden"
                  @vue:mounted="inputMounted(field)"
               />
               <div class="size-10 bg-(--z-field-background) rounded-full flex justify-center items-center">
                  <Camera class="size-5" />
               </div>
            </label>
         </div>
      </main>

      <BaseModal
         :open="activeImage !== null"
         :show-buttons="false"
         title="Rasmni kesish"
         @close="closeCropEditor"
      >
         <template #icon>
            <Crop class="size-4" />
         </template>

         <div v-if="activeImage" class="flex flex-col gap-3.5 select-none [-webkit-touch-callout:none]" @contextmenu.prevent @dragstart.prevent>
            <!-- 1. Real Natija (Live Preview) -->
            <div class="flex flex-col gap-1.5">
               <div class="flex items-center justify-between px-0.5">
                  <span class="text-xs font-semibold text-(--z-foreground) flex items-center gap-1.5">
                     <Eye class="size-3.5 text-(--z-primary)" />
                     Real natija 
                  </span>
               </div>
               <div class="relative aspect-video w-full overflow-hidden rounded-(--z-rounded) border border-(--z-border) shadow-md bg-black/60">
                  <ProductImageView
                     :src="activeImage.originalUrl"
                     :crop-x="activeImage.crop_x"
                     :crop-y="activeImage.crop_y"
                     :crop-scale="activeImage.crop_scale"
                     class="h-full w-full pointer-events-none select-none"
                     alt="Real natija ko'rinishi"
                  />
               </div>
            </div>

            <!-- 2. Kesish maydoni (Crop stage with 4-way nudge buttons and scale slider) -->
            <div class="flex flex-col gap-2">
               <div class="flex items-center justify-between px-0.5">
                  <span class="text-xs font-semibold text-(--z-foreground) flex items-center gap-1.5">
                     <Crop class="size-3.5 text-(--z-primary)" />
                     Kesish ramkasi
                  </span>
               </div>

               <!-- 4 tomonlama yo'nalish tugmalari va o'rtadagi rasm maydoni -->
               <div class="flex flex-col items-center gap-1 select-none">
                  <!-- Tepaga siljitish tugmasi -->
                  <button
                     type="button"
                     class="flex items-center justify-center size-7 sm:size-8 rounded-lg border border-(--z-border) bg-(--z-card) text-(--z-foreground) hover:bg-(--z-muted) active:scale-95 transition-all shadow-xs touch-none cursor-pointer"
                     title="Tepaga 1px siljitish"
                     @pointerdown="startNudge(0, -1)"
                     @pointerup="stopNudge"
                     @pointerleave="stopNudge"
                     @pointercancel="stopNudge"
                     @contextmenu.prevent
                  >
                     <ChevronUp class="size-4" />
                  </button>

                  <!-- Chap tugma + Crop Maydoni + O'ng tugma -->
                  <div class="flex items-center gap-1.5 sm:gap-2 w-full">
                     <button
                        type="button"
                        class="shrink-0 flex items-center justify-center size-7 sm:size-8 rounded-lg border border-(--z-border) bg-(--z-card) text-(--z-foreground) hover:bg-(--z-muted) active:scale-95 transition-all shadow-xs touch-none cursor-pointer"
                        title="Chapga 1px siljitish"
                        @pointerdown="startNudge(-1, 0)"
                        @pointerup="stopNudge"
                        @pointerleave="stopNudge"
                        @pointercancel="stopNudge"
                        @contextmenu.prevent
                     >
                        <ChevronLeft class="size-4" />
                     </button>

                     <div
                        ref="cropStage"
                        class="grow relative aspect-video touch-none overflow-hidden rounded-(--z-rounded) bg-black/90 shadow-inner select-none [-webkit-touch-callout:none]"
                        @pointermove="moveCrop"
                        @pointerup="stopCropDrag"
                        @pointercancel="stopCropDrag"
                        @contextmenu.prevent
                        @dragstart.prevent
                     >
                        <!-- Blurred background for portrait images in crop stage -->
                        <img
                           v-if="isPortraitActive"
                           :src="activeImage.originalUrl"
                           class="absolute inset-0 h-full w-full object-cover scale-105 blur-[6px] opacity-75 brightness-85 pointer-events-none select-none [-webkit-touch-callout:none]"
                           alt=""
                           aria-hidden="true"
                           draggable="false"
                           @contextmenu.prevent
                        />
                        <img
                           :key="activeImage.url"
                           ref="cropImage"
                           :src="activeImage.originalUrl"
                           class="absolute max-w-none select-none pointer-events-none [-webkit-touch-callout:none]"
                           :class="{ 'shadow-2xl': isPortraitActive }"
                           :style="editorImageStyle"
                           alt="Kesish ko'rinishi"
                           draggable="false"
                           @load="measureCropStage"
                           @contextmenu.prevent
                        />
                        <div
                           v-if="cropRectStyle"
                           class="absolute cursor-move border border-white shadow-[0_0_0_9999px_rgba(0,0,0,0.55)]"
                           :style="cropRectStyle"
                           @pointerdown.stop="startCropDrag($event, 'move')"
                        >
                           <!-- Rule-of-thirds grid -->
                           <div class="pointer-events-none absolute inset-0 grid grid-cols-3 grid-rows-3">
                              <div class="border-r border-b border-white/40" />
                              <div class="border-r border-b border-white/40" />
                              <div class="border-b border-white/40" />
                              <div class="border-r border-b border-white/40" />
                              <div class="border-r border-b border-white/40" />
                              <div class="border-b border-white/40" />
                              <div class="border-r border-b border-white/40" />
                              <div class="border-r border-b border-white/40" />
                              <div />
                           </div>
                           <span
                              v-for="handle in cropHandles"
                              :key="handle"
                              class="absolute z-1 block touch-none"
                              :class="getHandleClass(handle)"
                              @pointerdown.stop="startCropDrag($event, handle)"
                           />
                        </div>
                     </div>

                     <button
                        type="button"
                        class="shrink-0 flex items-center justify-center size-7 sm:size-8 rounded-lg border border-(--z-border) bg-(--z-card) text-(--z-foreground) hover:bg-(--z-muted) active:scale-95 transition-all shadow-xs touch-none cursor-pointer"
                        title="O'ngga 1px siljitish"
                        @pointerdown="startNudge(1, 0)"
                        @pointerup="stopNudge"
                        @pointerleave="stopNudge"
                        @pointercancel="stopNudge"
                        @contextmenu.prevent
                     >
                        <ChevronRight class="size-4" />
                     </button>
                  </div>

                  <!-- Pastga siljitish tugmasi -->
                  <button
                     type="button"
                     class="flex items-center justify-center size-7 sm:size-8 rounded-lg border border-(--z-border) bg-(--z-card) text-(--z-foreground) hover:bg-(--z-muted) active:scale-95 transition-all shadow-xs touch-none cursor-pointer"
                     title="Pastga 1px siljitish"
                     @pointerdown="startNudge(0, 1)"
                     @pointerup="stopNudge"
                     @pointerleave="stopNudge"
                     @pointercancel="stopNudge"
                     @contextmenu.prevent
                  >
                     <ChevronDown class="size-4" />
                  </button>
               </div>

               <!-- 3. Masshtabni (Scale) o'zgartirish inputi -->
               <div class="flex flex-col gap-1.5 p-2 rounded-xl bg-(--z-muted)/40 border border-(--z-border)">
                  <div class="flex items-center justify-between text-xs">
                     <span class="font-medium text-(--z-foreground) flex items-center gap-1.5">
                        <ZoomIn class="size-3.5 text-(--z-primary)" />
                        Masshtab (Kattalashtirish)
                     </span>
                     <span class="font-mono text-xs font-semibold text-(--z-primary)">
                        {{ Number(activeImage?.crop_scale ?? 1).toFixed(2) }}x
                     </span>
                  </div>
                  <div class="flex items-center gap-2">
                     <button
                        type="button"
                        class="flex items-center justify-center size-7 rounded-lg border border-(--z-border) bg-(--z-card) hover:bg-(--z-muted) text-(--z-foreground) active:scale-95 transition-all disabled:opacity-40 cursor-pointer"
                        :disabled="(activeImage?.crop_scale ?? 1) <= 1"
                        title="Kichraytirish"
                        @click="onScaleChange((activeImage?.crop_scale ?? 1) - 0.05)"
                     >
                        <Minus class="size-3.5" />
                     </button>
                     <input
                        type="range"
                        min="1"
                        max="3"
                        step="0.01"
                        :value="activeImage?.crop_scale ?? 1"
                        @input="onScaleChange(parseFloat(($event.target as HTMLInputElement).value))"
                        class="grow h-1.5 rounded-lg bg-(--z-muted) accent-(--z-primary) cursor-pointer"
                     />
                     <button
                        type="button"
                        class="flex items-center justify-center size-7 rounded-lg border border-(--z-border) bg-(--z-card) hover:bg-(--z-muted) text-(--z-foreground) active:scale-95 transition-all disabled:opacity-40 cursor-pointer"
                        :disabled="(activeImage?.crop_scale ?? 1) >= 3"
                        title="Kattalashtirish"
                        @click="onScaleChange((activeImage?.crop_scale ?? 1) + 0.05)"
                     >
                        <Plus class="size-3.5" />
                     </button>
                  </div>
               </div>
            </div>
         </div>

         <footer class="mt-5 flex gap-3">
            <BaseButton class="w-full" severity="secondary" @click="closeCropEditor">Bekor qilish</BaseButton>
            <BaseButton class="w-full" @click="saveCrop">Saqlash</BaseButton>
         </footer>
      </BaseModal>
   </Field>
</template>

<script setup lang="ts">
import { Field, FieldBindingObject } from "vee-validate";
import {
   Camera,
   Crop,
   Trash,
   Eye,
   ChevronUp,
   ChevronDown,
   ChevronLeft,
   ChevronRight,
   Minus,
   Plus,
   ZoomIn,
} from "lucide-vue-next";
import BaseModal from "@shared/ui/BaseModal.vue";
import ProductImageView from "@shared/ui/ProductImageView.vue";
import { computed, nextTick, onBeforeUnmount, ref, useAttrs } from "vue";

const inputFile = ref<HTMLInputElement | null>(null);
const cropStage = ref<HTMLElement | null>(null);
const cropImage = ref<HTMLImageElement | null>(null);
const attrs = useAttrs();

const props = withDefaults(
   defineProps<{
      name: string;
      max?: number;
   }>(),
   {
      max: 7,
   },
);

interface IImage {
   id?: number | null;
   src?: string;
   crop_src?: string | null;
   crop_x: number;
   crop_y: number;
   crop_scale: number;
   crop_changed?: boolean;
   url: string;
   originalUrl: string;
   file: File | string | null;
}

const imagesSource = ref<IImage[]>([]);
const activeImage = ref<IImage | null>(null);
const activeField = ref<{ onInput: (value: IImage[]) => void } | null>(null);
const frameWidth = ref(0);
const frameHeight = ref(0);
const naturalWidth = ref(0);
const naturalHeight = ref(0);
const cropRect = ref({ x: 0, y: 0, width: 0, height: 0 });
const originalCrop = ref<{ x: number; y: number; scale: number } | null>(null);
const dragState = ref<{
   handle: string;
   startX: number;
   startY: number;
   rect: { x: number; y: number; width: number; height: number };
} | null>(null);
const cropHandles = ["north", "east", "south", "west", "north-east", "south-east", "south-west", "north-west"];

onBeforeUnmount(() => {
   stopNudge();
   imagesSource.value.forEach((image) => {
      if (image.file instanceof File) {
         URL.revokeObjectURL(image.originalUrl);
      }
   });
});

const canAddMore = computed(() => {
   if (!attrs.multiple) return imagesSource.value.length === 0;
   return imagesSource.value.length < props.max;
});

const imageBounds = computed(() => {
   if (!naturalWidth.value || !naturalHeight.value || !frameWidth.value || !frameHeight.value) {
      return { left: 0, top: 0, width: 0, height: 0, scale: 0 };
   }

   const scale = Math.min(frameWidth.value / naturalWidth.value, frameHeight.value / naturalHeight.value);
   const width = naturalWidth.value * scale;
   const height = naturalHeight.value * scale;

   return {
      left: (frameWidth.value - width) / 2,
      top: (frameHeight.value - height) / 2,
      width,
      height,
      scale,
   };
});

const virtualCanvas = computed(() => {
   if (!naturalWidth.value || !naturalHeight.value) {
      return { width: 0, height: 0, photoX: 0, photoY: 0, isPortrait: false };
   }
   const isPortrait = naturalHeight.value >= naturalWidth.value;
   if (isPortrait) {
      const width = naturalHeight.value * (16 / 9);
      const height = naturalHeight.value;
      const photoX = (width - naturalWidth.value) / 2;
      return { width, height, photoX, photoY: 0, isPortrait: true };
   }
   const ratio = 16 / 9;
   const baseWidth = Math.min(naturalWidth.value, naturalHeight.value * ratio);
   const baseHeight = baseWidth / ratio;
   return { width: naturalWidth.value, height: naturalHeight.value, photoX: 0, photoY: 0, isPortrait: false };
});

const isPortraitActive = computed(() => virtualCanvas.value.isPortrait);

const editorImageStyle = computed(() => {
   if (virtualCanvas.value.isPortrait && virtualCanvas.value.width) {
      const scale = frameWidth.value / virtualCanvas.value.width;
      return {
         left: `${virtualCanvas.value.photoX * scale}px`,
         top: `0px`,
         width: `${naturalWidth.value * scale}px`,
         height: `${frameHeight.value}px`,
      };
   }
   return {
      left: `${imageBounds.value.left}px`,
      top: `${imageBounds.value.top}px`,
      width: `${imageBounds.value.width}px`,
      height: `${imageBounds.value.height}px`,
   };
});

const cropRectStyle = computed(() => {
   if (!cropRect.value.width || !cropRect.value.height) return null;

   if (virtualCanvas.value.isPortrait && virtualCanvas.value.width) {
      const scale = frameWidth.value / virtualCanvas.value.width;
      return {
         left: `${cropRect.value.x * scale}px`,
         top: `${cropRect.value.y * scale}px`,
         width: `${cropRect.value.width * scale}px`,
         height: `${cropRect.value.height * scale}px`,
      };
   }

   if (!imageBounds.value.scale) return null;
   return {
      left: `${imageBounds.value.left + cropRect.value.x * imageBounds.value.scale}px`,
      top: `${imageBounds.value.top + cropRect.value.y * imageBounds.value.scale}px`,
      width: `${cropRect.value.width * imageBounds.value.scale}px`,
      height: `${cropRect.value.height * imageBounds.value.scale}px`,
   };
});

function normalizedImage(image: Record<string, any>): IImage {
   const src = image.src ? `/storage/${image.src}` : "";
   const originalUrl = image.file instanceof File ? URL.createObjectURL(image.file) : image.file || src;

   return {
      ...image,
      crop_x: Number(image.crop_x ?? 50),
      crop_y: Number(image.crop_y ?? 50),
      crop_scale: Number(image.crop_scale ?? 1),
      url: originalUrl,
      originalUrl,
      file: image.file instanceof File ? image.file : null,
   };
}

async function inputMounted(field: FieldBindingObject<any>) {
   const nullable = attrs.multiple ? [] : null;
   await field.onInput(field.value || nullable);

   if (!field.value) return;

   const fieldImages = Array.isArray(field.value) ? field.value : [field.value];
   imagesSource.value = fieldImages.map((image: Record<string, any>) => normalizedImage(image));
   field.onInput(imagesSource.value.map(fieldImageValue));
}

function onNativeFileChange(event: Event, field: { onInput: (value: IImage[]) => void }) {
   const target = event.target as HTMLInputElement;
   if (!target.files?.length) return;

   const files = Array.from(target.files);

   if (attrs.multiple) {
      const allowedFiles = files.slice(0, props.max - imagesSource.value.length);
      imagesSource.value.push(
         ...allowedFiles.map((file) => {
            const url = URL.createObjectURL(file);

            return {
               id: null,
               crop_x: 50,
               crop_y: 50,
               crop_scale: 1,
               url,
               originalUrl: url,
               file,
            };
         }),
      );
      field.onInput(imagesSource.value.map(fieldImageValue));
   } else {
      imagesSource.value.forEach((image) => {
         if (image.file instanceof File) {
            URL.revokeObjectURL(image.originalUrl);
         }
      });
      const url = URL.createObjectURL(files[0]);
      imagesSource.value = [
         {
            id: null,
            crop_x: 50,
            crop_y: 50,
            crop_scale: 1,
            url,
            originalUrl: url,
            file: files[0],
         },
      ];
      field.onInput(imagesSource.value.map(fieldImageValue));
   }

   target.value = "";
}

function deleteImage(index: number, field: { onInput: (value: IImage[] | null) => void }) {
   const image = imagesSource.value[index];

   if (image.file instanceof File) {
      URL.revokeObjectURL(image.originalUrl);
   }

   imagesSource.value = imagesSource.value.filter((_, imageIndex) => imageIndex !== index);
   field.onInput(attrs.multiple ? imagesSource.value.map(fieldImageValue) : null);
}

function fieldImageValue(image: IImage) {
   const value = {
      ...image,
      crop_x: Math.round(image.crop_x),
      crop_y: Math.round(image.crop_y),
      crop_scale: Number(Number(image.crop_scale).toFixed(2)),
   };
   return value;
}

async function openCropEditor(image: IImage, field: { onInput: (value: IImage[]) => void }) {
   activeImage.value = image;
   activeField.value = field;
   originalCrop.value = {
      x: image.crop_x,
      y: image.crop_y,
      scale: image.crop_scale,
   };
   naturalWidth.value = 0;
   naturalHeight.value = 0;
   await nextTick();
   measureCropStage();
}

function measureCropStage(event?: Event) {
   if (event) {
      const image = event.target as HTMLImageElement;
      naturalWidth.value = image.naturalWidth;
      naturalHeight.value = image.naturalHeight;
   } else if (cropImage.value?.naturalWidth) {
      naturalWidth.value = cropImage.value.naturalWidth;
      naturalHeight.value = cropImage.value.naturalHeight;
   }

   const bounds = cropStage.value?.getBoundingClientRect();
   frameWidth.value = bounds?.width ?? 0;
   frameHeight.value = bounds?.height ?? 0;

   if (activeImage.value && naturalWidth.value && naturalHeight.value && bounds) {
      const { width: cWidth, height: cHeight, isPortrait } = virtualCanvas.value;

      if (isPortrait) {
         // Composite 16:9 canvas: crop box starts from where blur begins (x=0, y=0)!
         const cropW = cWidth / activeImage.value.crop_scale;
         const cropH = cHeight / activeImage.value.crop_scale;
         const maxShiftX = cWidth - cropW;
         const maxShiftY = cHeight - cropH;

         cropRect.value = {
            x: maxShiftX * (activeImage.value.crop_x / 100),
            y: maxShiftY * (activeImage.value.crop_y / 100),
            width: cropW,
            height: cropH,
         };
      } else {
         const baseWidth = Math.min(naturalWidth.value, naturalHeight.value * (16 / 9));
         const baseHeight = baseWidth / (16 / 9);
         const width = baseWidth / activeImage.value.crop_scale;
         const height = baseHeight / activeImage.value.crop_scale;

         cropRect.value = {
            x: (naturalWidth.value - width) * (activeImage.value.crop_x / 100),
            y: (naturalHeight.value - height) * (activeImage.value.crop_y / 100),
            width,
            height,
         };
      }
   }
}

function startCropDrag(event: PointerEvent, handle: string) {
   const scale = virtualCanvas.value.isPortrait && virtualCanvas.value.width
      ? frameWidth.value / virtualCanvas.value.width
      : imageBounds.value.scale;
   if (!scale) return;

   dragState.value = {
      handle,
      startX: event.clientX,
      startY: event.clientY,
      rect: { ...cropRect.value },
   };
   (event.currentTarget as HTMLElement).setPointerCapture(event.pointerId);
}

function moveCrop(event: PointerEvent) {
   const scale = virtualCanvas.value.isPortrait && virtualCanvas.value.width
      ? frameWidth.value / virtualCanvas.value.width
      : imageBounds.value.scale;
   if (!dragState.value || !activeImage.value || !scale) return;

   const { handle, rect, startX, startY } = dragState.value;
   const deltaX = (event.clientX - startX) / scale;
   const deltaY = (event.clientY - startY) / scale;

   const { width: maxW, height: maxH, isPortrait } = virtualCanvas.value;
   const boundW = isPortrait ? maxW : naturalWidth.value;
   const boundH = isPortrait ? maxH : naturalHeight.value;

   if (handle === "move") {
      cropRect.value = {
         ...rect,
         x: Math.max(0, Math.min(boundW - rect.width, rect.x + deltaX)),
         y: Math.max(0, Math.min(boundH - rect.height, rect.y + deltaY)),
      };
   } else {
      const isWest = handle.includes("west");
      const isEast = handle.includes("east");
      const isNorth = handle.includes("north");
      const isSouth = handle.includes("south");
      const ratio = 16 / 9;
      const widthDelta = isWest ? -deltaX : isEast ? deltaX : isNorth ? -deltaY * ratio : deltaY * ratio;
      const baseWidth = isPortrait ? maxW : Math.min(naturalWidth.value, naturalHeight.value * ratio);
      const maxWidth = Math.min(
         baseWidth,
         isWest ? rect.x + rect.width : boundW - rect.x,
         isNorth ? (rect.y + rect.height) * ratio : (boundH - rect.y) * ratio,
      );
      const width = Math.max(baseWidth / 3, Math.min(maxWidth, rect.width + widthDelta));
      const height = width / ratio;
      const x = isWest ? rect.x + rect.width - width : rect.x;
      const y = isNorth ? rect.y + rect.height - height : rect.y;

      cropRect.value = {
         x: Math.max(0, Math.min(boundW - width, x)),
         y: Math.max(0, Math.min(boundH - height, y)),
         width,
         height,
      };
   }

   updateCropMetadata();
}

let nudgeTimeout: ReturnType<typeof setTimeout> | null = null;
let nudgeInterval: ReturnType<typeof setInterval> | null = null;

function nudgeCrop(dx: number, dy: number) {
   if (!activeImage.value || !cropRect.value.width) return;
   const { width: maxW, height: maxH, isPortrait } = virtualCanvas.value;
   const boundW = isPortrait ? maxW : naturalWidth.value;
   const boundH = isPortrait ? maxH : naturalHeight.value;

   const scale = virtualCanvas.value.isPortrait && virtualCanvas.value.width
      ? frameWidth.value / virtualCanvas.value.width
      : imageBounds.value.scale;

   if (!scale) return;

   const deltaX = dx / scale;
   const deltaY = dy / scale;

   cropRect.value = {
      ...cropRect.value,
      x: Math.max(0, Math.min(boundW - cropRect.value.width, cropRect.value.x + deltaX)),
      y: Math.max(0, Math.min(boundH - cropRect.value.height, cropRect.value.y + deltaY)),
   };
   updateCropMetadata();
}

function startNudge(dx: number, dy: number) {
   stopNudge();
   nudgeCrop(dx, dy);
   nudgeTimeout = setTimeout(() => {
      nudgeInterval = setInterval(() => {
         nudgeCrop(dx, dy);
      }, 50);
   }, 250);
}

function stopNudge() {
   if (nudgeTimeout) {
      clearTimeout(nudgeTimeout);
      nudgeTimeout = null;
   }
   if (nudgeInterval) {
      clearInterval(nudgeInterval);
      nudgeInterval = null;
   }
}

function onScaleChange(newScale: number) {
   if (!activeImage.value || !cropRect.value.width) return;
   const clampedScale = Math.max(1, Math.min(3, newScale));
   const { width: maxW, height: maxH, isPortrait } = virtualCanvas.value;
   const boundW = isPortrait ? maxW : naturalWidth.value;
   const boundH = isPortrait ? maxH : naturalHeight.value;
   const ratio = 16 / 9;
   const baseWidth = isPortrait ? maxW : Math.min(naturalWidth.value, naturalHeight.value * ratio);

   const newWidth = baseWidth / clampedScale;
   const newHeight = newWidth / ratio;
   const centerX = cropRect.value.x + cropRect.value.width / 2;
   const centerY = cropRect.value.y + cropRect.value.height / 2;

   cropRect.value = {
      width: newWidth,
      height: newHeight,
      x: Math.max(0, Math.min(boundW - newWidth, centerX - newWidth / 2)),
      y: Math.max(0, Math.min(boundH - newHeight, centerY - newHeight / 2)),
   };
   updateCropMetadata();
}

function updateCropMetadata() {
   if (!activeImage.value || !cropRect.value.width) return;

   const { width: cWidth, height: cHeight, isPortrait } = virtualCanvas.value;

   if (isPortrait) {
      activeImage.value.crop_scale = Math.max(1, Math.min(3, Number((cWidth / cropRect.value.width).toFixed(2))));
      activeImage.value.crop_x = cWidth === cropRect.value.width
         ? 50
         : Number(((cropRect.value.x / (cWidth - cropRect.value.width)) * 100).toFixed(2));
      activeImage.value.crop_y = cHeight === cropRect.value.height
         ? 50
         : Number(((cropRect.value.y / (cHeight - cropRect.value.height)) * 100).toFixed(2));
   } else {
      const baseWidth = Math.min(naturalWidth.value, naturalHeight.value * (16 / 9));
      activeImage.value.crop_scale = Math.max(1, Math.min(3, Number((baseWidth / cropRect.value.width).toFixed(2))));
      activeImage.value.crop_x = naturalWidth.value === cropRect.value.width
         ? 50
         : Number(((cropRect.value.x / (naturalWidth.value - cropRect.value.width)) * 100).toFixed(2));
      activeImage.value.crop_y = naturalHeight.value === cropRect.value.height
         ? 50
         : Number(((cropRect.value.y / (naturalHeight.value - cropRect.value.height)) * 100).toFixed(2));
   }
}

function stopCropDrag() {
   dragState.value = null;
}

function saveCrop() {
   if (activeImage.value) {
      activeImage.value.crop_src = null;
      activeImage.value.crop_changed = true;
      activeImage.value.url = activeImage.value.originalUrl;
   }

   if (activeField.value) {
      activeField.value.onInput(imagesSource.value.map(fieldImageValue));
   }
   originalCrop.value = null;
   closeCropEditor();
}

function closeCropEditor() {
   stopNudge();
   if (activeImage.value && originalCrop.value) {
      activeImage.value.crop_x = originalCrop.value.x;
      activeImage.value.crop_y = originalCrop.value.y;
      activeImage.value.crop_scale = originalCrop.value.scale;
   }
   activeImage.value = null;
   activeField.value = null;
   originalCrop.value = null;
   dragState.value = null;
}

function getHandleClass(handle: string): string {
   switch (handle) {
      case "north":
         return "left-1/2 -top-[4px] -translate-x-1/2 w-[44px] h-[6px] cursor-ns-resize rounded-full bg-white shadow-md after:absolute after:-inset-3 after:content-['']";
      case "south":
         return "left-1/2 -bottom-[4px] -translate-x-1/2 w-[44px] h-[6px] cursor-ns-resize rounded-full bg-white shadow-md after:absolute after:-inset-3 after:content-['']";
      case "east":
         return "top-1/2 -right-[4px] -translate-y-1/2 w-[6px] h-[44px] cursor-ew-resize rounded-full bg-white shadow-md after:absolute after:-inset-3 after:content-['']";
      case "west":
         return "top-1/2 -left-[4px] -translate-y-1/2 w-[6px] h-[44px] cursor-ew-resize rounded-full bg-white shadow-md after:absolute after:-inset-3 after:content-['']";
      case "north-east":
         return "-top-2.5 -right-2.5 size-5 sm:size-4.5 border-2 border-white bg-(--z-primary) rounded-full shadow-lg cursor-nesw-resize after:absolute after:-inset-3.5 after:content-['']";
      case "south-east":
         return "-bottom-2.5 -right-2.5 size-5 sm:size-4.5 border-2 border-white bg-(--z-primary) rounded-full shadow-lg cursor-nwse-resize after:absolute after:-inset-3.5 after:content-['']";
      case "south-west":
         return "-bottom-2.5 -left-2.5 size-5 sm:size-4.5 border-2 border-white bg-(--z-primary) rounded-full shadow-lg cursor-nesw-resize after:absolute after:-inset-3.5 after:content-['']";
      case "north-west":
         return "-top-2.5 -left-2.5 size-5 sm:size-4.5 border-2 border-white bg-(--z-primary) rounded-full shadow-lg cursor-nwse-resize after:absolute after:-inset-3.5 after:content-['']";
      default:
         return "";
   }
}
</script>
