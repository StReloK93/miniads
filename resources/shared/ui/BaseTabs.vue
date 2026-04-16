<template>
   <div class="border-b border-(--z-border)">
      <div class="relative flex w-full overflow-x-none">
         <button
            v-for="(item, index) in items"
            :key="getKey(item, index)"
            ref="tabRefs"
            class="min-w-0 flex-1 shrink-0 cursor-pointer border-0 bg-transparent px-2 py-2 text-center text-sm font-medium whitespace-nowrap transition-colors duration-200 ease-(--ease-soft) sm:px-3 sm:py-2.5"
            :class="index === activeIndex ? 'text-(--z-foreground)' : 'text-(--z-muted-text)'"
            @click="selectTab(index)"
         >
            <span class="block truncate">
               {{ getLabel(item) }}
            </span>
         </button>

         <span
            class="absolute -bottom-px left-0 h-0.5 toppx rounded-(--z-rounded) bg-(--z-foreground) transition-[transform,width] duration-200 ease-(--ease-soft)"
            :style="indicatorStyle"
         />
      </div>
   </div>
</template>

<script setup lang="ts">
import { ref, watch, nextTick, onMounted, onBeforeUnmount } from "vue";

/* ================= PROPS ================= */

type Item = any | Record<string, any>;

const props = defineProps<{
   items: Item[];
   modelValue?: number;
   labelKey?: string;
   valueKey?: string;
}>();

/* ================= EMITS ================= */

const emit = defineEmits<{
   (e: "update:modelValue", value: number): void;
   (
      e: "change",
      payload: {
         index: number;
         value: any;
         item: Item;
      },
   ): void;
}>();

/* ================= STATE ================= */

const activeIndex = ref(props.modelValue ?? 0);
const tabRefs = ref<HTMLElement[]>([]);
const indicatorStyle = ref<Record<string, string>>({});

/* ================= HELPERS ================= */

const getLabel = (item: Item) => {
   if (typeof item === "string") return item;
   return item[props.labelKey || "label"];
};

const getValue = (item: Item, index: number) => {
   if (typeof item === "string") return index;
   return item[props.valueKey || "id"];
};

const getKey = (item: Item, index: number) => {
   if (typeof item === "string") return index;
   return item[props.valueKey || "id"] ?? index;
};

/* ================= LOGIC ================= */

const updateIndicator = () => {
   const el = tabRefs.value[activeIndex.value];
   if (!el) return;

   indicatorStyle.value = {
      width: `${el.offsetWidth}px`,
      transform: `translateX(${el.offsetLeft}px)`,
   };
};

const scrollToActiveTab = () => {
   const el = tabRefs.value[activeIndex.value];
   if (!el) return;

   el.scrollIntoView({
      behavior: "smooth",
      inline: "center",
      block: "nearest",
   });
};

const selectTab = async (index: number) => {
   activeIndex.value = index;
   emit("update:modelValue", index);
   emit("change", {
      index,
      value: getValue(props.items[index], index),
      item: props.items[index],
   });

   await nextTick();
   updateIndicator();
   scrollToActiveTab();
};

const handleResize = async () => {
   await nextTick();
   updateIndicator();
};

/* ================= WATCHERS ================= */

watch(
   () => props.modelValue,
   async (val) => {
      if (val !== undefined) {
         activeIndex.value = val;
         await nextTick();
         updateIndicator();
         scrollToActiveTab();
      }
   },
);

watch(
   () => props.items,
   async () => {
      await nextTick();
      updateIndicator();
   },
   { deep: true },
);

onMounted(async () => {
   await nextTick();
   updateIndicator();
   window.addEventListener("resize", handleResize);
});

onBeforeUnmount(() => {
   window.removeEventListener("resize", handleResize);
});
</script>
