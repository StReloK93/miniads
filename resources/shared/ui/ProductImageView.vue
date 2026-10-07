<template>
   <div ref="container" class="relative block overflow-hidden">
      <img
         :src="src"
         :style="imageStyle"
         class="absolute max-w-none"
         :alt="alt"
         decoding="async"
         draggable="false"
         @load="measureImage"
      />
   </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from "vue";

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
const naturalSize = ref({ width: 0, height: 0 });
const containerSize = ref({ width: 0, height: 0 });
let resizeObserver: ResizeObserver | null = null;

const imageStyle = computed(() => {
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
   const frameRatio = frameWidth / frameHeight;
   const baseWidth = Math.min(naturalWidth, naturalHeight * frameRatio);
   const baseHeight = baseWidth / frameRatio;
   const imageScale = (frameWidth / baseWidth) * scale;
   const cropWidth = baseWidth / scale;
   const cropHeight = baseHeight / scale;

   return {
      width: `${naturalWidth * imageScale}px`,
      height: `${naturalHeight * imageScale}px`,
      left: `${-((naturalWidth - cropWidth) * cropX * imageScale)}px`,
      top: `${-((naturalHeight - cropHeight) * cropY * imageScale)}px`,
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
   if (container.value) {
      resizeObserver = new ResizeObserver(measureContainer);
      resizeObserver.observe(container.value);
   }
});

onBeforeUnmount(() => {
   resizeObserver?.disconnect();
});
</script>
