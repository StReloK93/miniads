<template>
   <Field :name="props.name" v-slot="{ field }" class="flex flex-col gap-1">
      <main class="relative">
         <span class="text-xs text-gray-500 absolute -top-5 right-1.5">
            {{ imagesSource.length }} / {{ props.max }}
         </span>
         <div :class="{ 'grid gap-1 grid-cols-3': attrs.multiple }">
            <main
               v-for="(image, index) in imagesSource"
               :key="image.id ?? image.url"
               :class="[index === 0 ? 'col-span-3 aspect-video' : 'aspect-square']"
               class="relative overflow-hidden rounded-(--z-rounded)"
            >
               <ProductImageView
                  :src="image.url"
                  :crop-x="image.crop_x"
                  :crop-y="image.crop_y"
                  :crop-scale="image.crop_scale"
                  class="h-full w-full"
                  alt="Tanlangan e’lon rasmi"
               />
               <BaseButton
                  @click="deleteImage(index, field)"
                  class="absolute! top-1 right-1 z-10"
                  rounded
                  severity="danger"
                  icon-only
                  variant="text"
                  size="sm"
                  aria-label="Rasmni o‘chirish"
               >
                  <template #icon>
                     <Trash class="size-5" />
                  </template>
               </BaseButton>
               <BaseButton
                  @click="openCropEditor(image, field)"
                  class="absolute! bottom-1 right-1 z-10 shadow-lg"
                  rounded
                  severity="primary"
                  icon-only
                  size="sm"
                  aria-label="Rasmni kesish"
               >
                  <template #icon>
                     <Crop class="size-5 text-white" />
                  </template>
               </BaseButton>
            </main>

            <label
               v-if="canAddMore"
               :class="[imagesSource.length > 0 ? 'aspect-square' : 'col-span-3 aspect-video']"
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
         description="Ramkani chetlaridan torting, rasmni esa ichidan surib joylashtiring."
         @close="closeCropEditor"
      >
         <template #icon>
            <Crop class="size-4" />
         </template>

         <div
            ref="cropStage"
            class="relative aspect-video touch-none overflow-hidden rounded-(--z-rounded) bg-black/90"
            @pointermove="moveCrop"
            @pointerup="stopCropDrag"
            @pointercancel="stopCropDrag"
         >
            <img
               v-if="activeImage"
               :key="activeImage.url"
               ref="cropImage"
               :src="activeImage.originalUrl"
               class="absolute max-w-none select-none"
               :style="editorImageStyle"
               alt="Kesish ko‘rinishi"
               draggable="false"
               @load="measureCropStage"
            />
            <div
               v-if="cropRectStyle"
               class="crop-frame absolute cursor-move"
               :style="cropRectStyle"
               @pointerdown.stop="startCropDrag($event, 'move')"
            >
               <span class="crop-grid crop-grid--vertical" />
               <span class="crop-grid crop-grid--horizontal" />
               <span
                  v-for="handle in cropHandles"
                  :key="handle"
                  class="crop-handle"
                  :class="`crop-handle--${handle}`"
                  @pointerdown.stop="startCropDrag($event, handle)"
               />
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
import { Field } from "vee-validate";
import { Camera, Crop, Trash } from "lucide-vue-next";
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

const editorImageStyle = computed(() => ({
   left: `${imageBounds.value.left}px`,
   top: `${imageBounds.value.top}px`,
   width: `${imageBounds.value.width}px`,
   height: `${imageBounds.value.height}px`,
}));

const cropRectStyle = computed(() => {
   if (!imageBounds.value.scale || !cropRect.value.width || !cropRect.value.height) return null;

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

async function inputMounted(field: { value: unknown; onInput: (value: unknown) => void }) {
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
   const value = { ...image };
   delete value.url;
   delete value.originalUrl;

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
   }

   const bounds = cropStage.value?.getBoundingClientRect();
   frameWidth.value = bounds?.width ?? 0;
   frameHeight.value = bounds?.height ?? 0;

   if (activeImage.value && naturalWidth.value && naturalHeight.value && bounds) {
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

function startCropDrag(event: PointerEvent, handle: string) {
   if (!imageBounds.value.scale) return;

   dragState.value = {
      handle,
      startX: event.clientX,
      startY: event.clientY,
      rect: { ...cropRect.value },
   };
   (event.currentTarget as HTMLElement).setPointerCapture(event.pointerId);
}

function moveCrop(event: PointerEvent) {
   if (!dragState.value || !activeImage.value || !imageBounds.value.scale) return;

   const { handle, rect, startX, startY } = dragState.value;
   const deltaX = (event.clientX - startX) / imageBounds.value.scale;
   const deltaY = (event.clientY - startY) / imageBounds.value.scale;

   if (handle === "move") {
      cropRect.value = {
         ...rect,
         x: Math.max(0, Math.min(naturalWidth.value - rect.width, rect.x + deltaX)),
         y: Math.max(0, Math.min(naturalHeight.value - rect.height, rect.y + deltaY)),
      };
   } else {
      const isWest = handle.includes("west");
      const isEast = handle.includes("east");
      const isNorth = handle.includes("north");
      const isSouth = handle.includes("south");
      const ratio = 16 / 9;
      const widthDelta = isWest ? -deltaX : isEast ? deltaX : isNorth ? -deltaY * ratio : deltaY * ratio;
      const baseWidth = Math.min(naturalWidth.value, naturalHeight.value * ratio);
      const maxWidth = Math.min(
         baseWidth,
         isWest ? rect.x + rect.width : naturalWidth.value - rect.x,
         isNorth ? (rect.y + rect.height) * ratio : (naturalHeight.value - rect.y) * ratio,
      );
      const width = Math.max(baseWidth / 3, Math.min(maxWidth, rect.width + widthDelta));
      const height = width / ratio;
      const x = isWest ? rect.x + rect.width - width : rect.x;
      const y = isNorth ? rect.y + rect.height - height : rect.y;

      cropRect.value = {
         x: Math.max(0, Math.min(naturalWidth.value - width, x)),
         y: Math.max(0, Math.min(naturalHeight.value - height, y)),
         width,
         height,
      };
   }

   updateCropMetadata();
}

function updateCropMetadata() {
   if (!activeImage.value || !cropRect.value.width) return;

   const baseWidth = Math.min(naturalWidth.value, naturalHeight.value * (16 / 9));
   activeImage.value.crop_scale = Math.max(1, Math.min(3, baseWidth / cropRect.value.width));
   activeImage.value.crop_x = naturalWidth.value === cropRect.value.width
      ? 50
      : Math.round((cropRect.value.x / (naturalWidth.value - cropRect.value.width)) * 100);
   activeImage.value.crop_y = naturalHeight.value === cropRect.value.height
      ? 50
      : Math.round((cropRect.value.y / (naturalHeight.value - cropRect.value.height)) * 100);
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
</script>

<style scoped>
.crop-frame {
   border: 1px solid white;
   box-shadow: 0 0 0 9999px rgb(0 0 0 / 55%);
}

.crop-grid {
   position: absolute;
   pointer-events: none;
   background: rgb(255 255 255 / 50%);
}

.crop-grid--vertical {
   inset: 0;
   background:
      linear-gradient(to right, transparent calc(33.333% - 0.5px), rgb(255 255 255 / 55%) 33.333%, transparent calc(33.333% + 0.5px)),
      linear-gradient(to right, transparent calc(66.666% - 0.5px), rgb(255 255 255 / 55%) 66.666%, transparent calc(66.666% + 0.5px));
}

.crop-grid--horizontal {
   inset: 0;
   background:
      linear-gradient(to bottom, transparent calc(33.333% - 0.5px), rgb(255 255 255 / 55%) 33.333%, transparent calc(33.333% + 0.5px)),
      linear-gradient(to bottom, transparent calc(66.666% - 0.5px), rgb(255 255 255 / 55%) 66.666%, transparent calc(66.666% + 0.5px));
}

.crop-handle {
   position: absolute;
   z-index: 1;
   display: block;
   border-radius: 999px;
   background: white;
   touch-action: none;
}

.crop-handle--north,
.crop-handle--south {
   left: 50%;
   width: 34px;
   height: 5px;
   transform: translateX(-50%);
   cursor: ns-resize;
}

.crop-handle--north {
   top: -3px;
}

.crop-handle--south {
   bottom: -3px;
}

.crop-handle--east,
.crop-handle--west {
   top: 50%;
   width: 5px;
   height: 34px;
   transform: translateY(-50%);
   cursor: ew-resize;
}

.crop-handle--east {
   right: -3px;
}

.crop-handle--west {
   left: -3px;
}

.crop-handle--north-east,
.crop-handle--south-east,
.crop-handle--south-west,
.crop-handle--north-west {
   width: 13px;
   height: 13px;
   border: 2px solid white;
   background: var(--z-primary);
}

.crop-handle--north-east {
   top: -6px;
   right: -6px;
   cursor: nesw-resize;
}

.crop-handle--south-east {
   right: -6px;
   bottom: -6px;
   cursor: nwse-resize;
}

.crop-handle--south-west {
   bottom: -6px;
   left: -6px;
   cursor: nesw-resize;
}

.crop-handle--north-west {
   top: -6px;
   left: -6px;
   cursor: nwse-resize;
}
</style>
