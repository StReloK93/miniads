<template>
   <!--
      Ixcham chiplar qatori: bo'limlar bir bosishda, lekin lentadan joy olmaydi.
      Barcha bo'limlar tugmasi qidiruv yonida (HomeSearchBar). E'lonlar soni — chip burchagidagi doirachada.
      pt-2: doiracha chipdan tepaga chiqadi, skroll konteyneri uni kesib qo'ymasin.
      Chetlar xiralashadi: o'ngda — "davomi bor", surilgandan keyin chapda — "orqada ham bor".
   -->
   <nav
      ref="scroller"
      class="chip-row -mx-4 px-4 pt-2 pb-3 flex gap-2.5 overflow-x-auto no-scrollbar"
      :class="{ 'fade-start': canScrollBack, 'fade-end': canScrollMore }"
      :aria-label="$t('categories.title')"
      @scroll.passive="updateFades"
   >
      <RouterLink
         v-for="category in CategoryStore.parentCategories"
         :key="category.id"
         :to="
            category.is_page
               ? { name: 'category', params: { id: category.id } }
               : { name: 'categories', query: { category_id: category.id } }
         "
         class="relative shrink-0 h-9 pl-2.5 pr-3.5 inline-flex items-center gap-1.5 rounded-full bg-(--z-card) border border-(--z-border) text-xs font-medium whitespace-nowrap select-none transition active:scale-95"
      >
         <component :is="icons[category.image]" stroke-width="1.75" class="size-4 text-(--z-muted-text)" />
         {{ category.name }}
         <CountBadge :count="CategoryStore.countFor(category.id)" class="-top-2 -right-1.5" />
      </RouterLink>
   </nav>
</template>

<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";
import icons from "@/features/category/icons";
import CountBadge from "@/features/category/CountBadge.vue";
import { useCategory } from "@shared/entities/Category/useCategory";

const CategoryStore = useCategory();

const scroller = ref<HTMLElement | null>(null);
const canScrollBack = ref(false);
const canScrollMore = ref(false);

/** Qaysi chetda yana chip bor — o'sha chet xiralashadi (2px — yaxlitlash xatosi uchun zaxira) */
function updateFades() {
   const el = scroller.value;
   if (!el) return;
   canScrollBack.value = el.scrollLeft > 2;
   canScrollMore.value = el.scrollLeft + el.clientWidth < el.scrollWidth - 2;
}

// Ekran o'lchami o'zgarsa (aylantirish, sm: kenglik) — qayta hisoblanadi
let resizeObserver: ResizeObserver | null = null;

onMounted(() => {
   CategoryStore.getCounts().catch((error) => console.error("Category counts failed.", error));
   updateFades();
   if (scroller.value && "ResizeObserver" in window) {
      resizeObserver = new ResizeObserver(updateFades);
      resizeObserver.observe(scroller.value);
   }
});

// Bo'limlar keyinroq yuklanadi — chiplar chizilgach hisoblaymiz
watch(
   () => CategoryStore.parentCategories,
   () => nextTick(updateFades),
);

onBeforeUnmount(() => resizeObserver?.disconnect());
</script>

<style scoped>
/*
   mask-image fon rangiga bog'liq emas: yorug' va qorong'i mavzuda, shisha fonda ham bir xil ishlaydi.
   Xiralashish chipning o'zini yo'qotadi — ostidagi fon ko'rinadi.
*/
.chip-row {
   --fade: 2.5rem;
   --fade-start: 0px;
   --fade-end: 0px;
   --fade-mask: linear-gradient(
      to right,
      transparent 0,
      #000 var(--fade-start),
      #000 calc(100% - var(--fade-end)),
      transparent 100%
   );
   /* Eski iOS (Telegram WebView) faqat prefiks bilan tushunadi */
   -webkit-mask-image: var(--fade-mask);
   mask-image: var(--fade-mask);
}

.chip-row.fade-start {
   --fade-start: var(--fade);
}

.chip-row.fade-end {
   --fade-end: var(--fade);
}
</style>
