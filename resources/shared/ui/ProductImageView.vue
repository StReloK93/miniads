<template>
   <div ref="container" class="relative block overflow-hidden bg-black/40 select-none [-webkit-touch-callout:none]" @contextmenu.prevent @dragstart.prevent>
      <!-- Blurred backdrop for portrait images -->
      <img
         v-if="isPortrait"
         :src="src"
         :style="backdropStyle"
         class="absolute max-w-none object-cover scale-105 blur-[6px] opacity-75 brightness-85 pointer-events-none select-none [-webkit-touch-callout:none]"
         alt=""
         aria-hidden="true"
         decoding="async"
         draggable="false"
         @contextmenu.prevent
      />
      <!-- Sharp foreground image -->
      <img
         ref="imageRef"
         :src="src"
         :style="imageStyle"
         class="absolute max-w-none pointer-events-none select-none [-webkit-touch-callout:none]"
         :class="{ 'shadow-2xl': isPortrait }"
         :alt="alt"
         decoding="async"
         draggable="false"
         @load="measureImage"
         @contextmenu.prevent
      />
   </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, type CSSProperties } from "vue";

const props = withDefaults(
   defineProps<{
      src: string;
      cropX?: number;
      cropY?: number;
      cropScale?: number;
      alt?: string;
   }>(),
   {
      cropX: 50,
      cropY: 50,
      cropScale: 1,
      alt: "",
   },
);

const container = ref<HTMLElement | null>(null);
const imageRef = ref<HTMLImageElement | null>(null);
const naturalSize = ref({ width: 0, height: 0 });
const containerSize = ref({ width: 0, height: 0 });
let resizeObserver: ResizeObserver | null = null;

const isPortrait = computed(() => {
   const { width, height } = naturalSize.value;
   if (!width || !height) return false;
   return height >= width;
});

const backdropStyle = computed<CSSProperties>(() => {
   const { width: naturalWidth, height: naturalHeight } = naturalSize.value;
   const { width: frameWidth, height: frameHeight } = containerSize.value;

   if (!naturalWidth || !naturalHeight || !frameWidth || !frameHeight || !isPortrait.value) {
      return {
         inset: "0",
         width: "100%",
         height: "100%",
      };
   }

   const cWidth = naturalHeight * (16 / 9);
   const cHeight = naturalHeight;
   const scale = Math.max(1, Math.min(3, props.cropScale));
   const cropX = Math.max(0, Math.min(100, props.cropX)) / 100;
   const cropY = Math.max(0, Math.min(100, props.cropY)) / 100;

   const cropW = cWidth / scale;
   const cropH = cHeight / scale;
   const cropWindowX = (cWidth - cropW) * cropX;
   const cropWindowY = (cHeight - cropH) * cropY;

   const viewScale = Math.max(frameWidth / cropW, frameHeight / cropH);
   const extraX = (frameWidth - cropW * viewScale) / 2;
   const extraY = (frameHeight - cropH * viewScale) / 2;

   const left = extraX - cropWindowX * viewScale;
   const top = extraY - cropWindowY * viewScale;
   const width = cWidth * viewScale;
   const height = cHeight * viewScale;

   return {
      left: `${left}px`,
      top: `${top}px`,
      width: `${width}px`,
      height: `${height}px`,
   };
});

const imageStyle = computed<CSSProperties>(() => {
   const { width: naturalWidth, height: naturalHeight } = naturalSize.value;
   const { width: frameWidth, height: frameHeight } = containerSize.value;

   if (!naturalWidth || !naturalHeight || !frameWidth || !frameHeight) {
      return {
         inset: "0",
         width: "100%",
         height: "100%",
         objectFit: "cover",
         objectPosition: `${props.cropX}% ${props.cropY}%`,
         transform: `scale(${props.cropScale})`,
      };
   }

   const scale = Math.max(1, Math.min(3, props.cropScale));
   const cropX = Math.max(0, Math.min(100, props.cropX)) / 100;
   const cropY = Math.max(0, Math.min(100, props.cropY)) / 100;
   const CROP_RATIO = 16 / 9;

   if (isPortrait.value) {
      // Treat the 16:9 composite (blur + photo) as one unified image
      const cWidth = naturalHeight * CROP_RATIO;
      const cHeight = naturalHeight;

      const cropW = cWidth / scale;
      const cropH = cHeight / scale;
      const cropWindowX = (cWidth - cropW) * cropX;
      const cropWindowY = (cHeight - cropH) * cropY;

      const viewScale = Math.max(frameWidth / cropW, frameHeight / cropH);
      const extraX = (frameWidth - cropW * viewScale) / 2;
      const extraY = (frameHeight - cropH * viewScale) / 2;

      const photoXInComposite = (cWidth - naturalWidth) / 2;
      const left = extraX + (photoXInComposite - cropWindowX) * viewScale;
      const top = extraY - cropWindowY * viewScale;
      const width = naturalWidth * viewScale;
      const height = naturalHeight * viewScale;

      return {
         left: `${left}px`,
         top: `${top}px`,
         width: `${width}px`,
         height: `${height}px`,
      };
   }

   // Landscape (albomniy) image:
   // Crop was performed against fixed 16:9 ratio
   const baseWidth = Math.min(naturalWidth, naturalHeight * CROP_RATIO);
   const baseHeight = baseWidth / CROP_RATIO;
   const cropWidth = baseWidth / scale;
   const cropHeight = baseHeight / scale;
   const cropWindowX = (naturalWidth - cropWidth) * cropX;
   const cropWindowY = (naturalHeight - cropHeight) * cropY;

   const viewScale = Math.max(frameWidth / cropWidth, frameHeight / cropHeight);
   const extraX = (frameWidth - cropWidth * viewScale) / 2;
   const extraY = (frameHeight - cropHeight * viewScale) / 2;

   const left = extraX - cropWindowX * viewScale;
   const top = extraY - cropWindowY * viewScale;
   const width = naturalWidth * viewScale;
   const height = naturalHeight * viewScale;

   return {
      left: `${left}px`,
      top: `${top}px`,
      width: `${width}px`,
      height: `${height}px`,
   };
});

function measureContainer() {
   const bounds = container.value?.getBoundingClientRect();
   if (!bounds) return;
   containerSize.value = { width: bounds.width, height: bounds.height };
}

function measureImage(event: Event) {
   const image = event.target as HTMLImageElement;
   naturalSize.value = { width: image.naturalWidth, height: image.naturalHeight };
   measureContainer();
}

onMounted(() => {
   measureContainer();
   if (imageRef.value?.complete && imageRef.value.naturalWidth) {
      naturalSize.value = {
         width: imageRef.value.naturalWidth,
         height: imageRef.value.naturalHeight,
      };
   }
   if (container.value) {
      resizeObserver = new ResizeObserver(measureContainer);
      resizeObserver.observe(container.value);
   }
});

onBeforeUnmount(() => {
   resizeObserver?.disconnect();
});
</script>
