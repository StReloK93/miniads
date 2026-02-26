<template>
   <div class="tabs">
      <div class="tabs-list" ref="listRef">
         <button
            v-for="(item, index) in items"
            :key="getKey(item, index)"
            class="tab"
            :class="{ active: index === activeIndex }"
            @click="selectTab(index)"
            ref="tabRefs"
         >
            {{ getLabel(item) }}
         </button>

         <!-- Active bottom indicator -->
         <span class="indicator" :style="indicatorStyle" />
      </div>
   </div>
</template>

<script setup lang="ts">
import { ref, watch, nextTick, onMounted } from "vue";

/* ================= PROPS ================= */

type Item = string | Record<string, any>;

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
const listRef = ref<HTMLElement | null>(null);
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
};

/* ================= WATCHERS ================= */

watch(
   () => props.modelValue,
   async (val) => {
      if (val !== undefined) {
         activeIndex.value = val;
         await nextTick();
         updateIndicator();
      }
   },
);

onMounted(() => {
   updateIndicator();
});
</script>

<style scoped>
.tabs {
   border-bottom: 1px solid var(--z-border);
   display: flex;
}
.tabs-list {
   position: relative;
   display: flex;
   width: 100%; /* 🔑 butun joyni oladi */
}

.tab {
   border-bottom: 1px solid var(--z-border);
   flex: 1 1 0%; /* 🔑 teng bo‘linadi */
   text-align: center;
}

.tab {
   background: none;
   border: none;
   cursor: pointer;

   padding: var(--space-sm) 0;

   font-size: 14px;
   font-weight: 500;
   color: var(--z-muted-text);

   transition: color var(--duration) var(--ease-soft);
}

.tab.active {
   color: var(--z-foreground);
}

.indicator {
   position: absolute;
   bottom: 0;
   left: 0;

   height: 2px;
   background: var(--z-foreground);
   border-radius: var(--z-rounded);

   transition:
      transform var(--duration) var(--ease-soft),
      width var(--duration) var(--ease-soft);
}
</style>
