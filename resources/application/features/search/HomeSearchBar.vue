<template>
   <!--
      Telegram uslubi: maydon bosh sahifaning o'zida. Fokus olganda sahifa qidiruv rejimiga o'tadi
      (HomePage), yonida — filtr va "Bekor". Oddiy holatda yonida barcha bo'limlar tugmasi.
   -->
   <form class="flex items-center gap-2 pb-3" role="search" @submit.prevent="onSubmit">
      <label class="field grow gap-2.5 min-w-0">
         <!-- Qidiruvda lupa orqaga strelkasiga aylanadi: chiqish shu yerda, alohida "Bekor" shart emas -->
         <button
            type="button"
            class="relative size-6 -ml-1 shrink-0 grid place-items-center rounded-full text-(--z-muted-text)"
            :class="active ? 'cursor-pointer text-(--z-foreground)! active:bg-(--z-muted)' : 'pointer-events-none'"
            :tabindex="active ? 0 : -1"
            :aria-label="active ? 'Qidiruvdan chiqish' : undefined"
            @mousedown.prevent
            @click="emit('cancel')"
         >
            <Search class="search-icon size-4.5" :class="{ 'is-hidden': active }" />
            <ArrowLeft class="search-icon size-4.5" :class="{ 'is-hidden': !active }" />
         </button>
         <input
            ref="input"
            v-model="model"
            type="search"
            enterkeyhint="search"
            autocomplete="off"
            placeholder="Nima qidiryapsiz?"
            class="w-full min-w-0 bg-transparent outline-none text-(--z-foreground) placeholder:text-(--z-muted-text)"
            @focus="emit('activate')"
            @blur="emit('blur')"
            @keydown.esc="emit('cancel')"
         />
         <!-- Qidirilayotganini ko'z qaraydigan joyda — maydonning o'zida ko'rsatamiz -->
         <LoaderCircle v-if="loading" class="size-4 shrink-0 animate-spin text-(--z-muted-text)" aria-label="Qidirilmoqda" />
         <button
            v-if="model"
            type="button"
            class="size-6 -mr-1 shrink-0 grid place-items-center rounded-full bg-(--z-muted) text-(--z-muted-text) cursor-pointer"
            aria-label="Tozalash"
            @mousedown.prevent
            @click="model = ''"
         >
            <X class="size-3.5" />
         </button>
      </label>

      <Transition name="search-action" mode="out-in">
         <BaseButton
            v-if="active"
            key="active"
            icon-only
            :severity="filterCount ? 'primary' : 'secondary'"
            class="relative shrink-0"
            aria-label="Filtrlar"
            @mousedown.prevent
            @click="emit('filters')"
         >
            <template #icon>
               <SlidersHorizontal class="size-4.5" />
            </template>
            <span
               v-if="filterCount"
               class="absolute -top-1 -right-1 min-w-4.5 h-4.5 px-1 grid place-items-center rounded-full bg-(--z-background) text-(--z-foreground) ring-1 ring-(--z-border) text-[10px] font-bold"
            >
               {{ filterCount }}
            </span>
         </BaseButton>

         <RouterLink v-else key="idle" :to="{ name: 'categories' }" class="shrink-0" aria-label="Barcha bo'limlar">
            <BaseButton icon-only severity="secondary">
               <template #icon>
                  <LayoutGrid class="size-5" stroke-width="1.75" />
               </template>
            </BaseButton>
         </RouterLink>
      </Transition>
   </form>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { ArrowLeft, LayoutGrid, LoaderCircle, Search, SlidersHorizontal, X } from "lucide-vue-next";
import BaseButton from "@shared/ui/BaseButton.vue";

defineProps<{ active: boolean; filterCount: number; loading?: boolean }>();
const model = defineModel<string>({ default: "" });
const emit = defineEmits<{
   (e: "activate"): void;
   (e: "blur"): void;
   (e: "cancel"): void;
   (e: "submit"): void;
   (e: "filters"): void;
}>();

const input = ref<HTMLInputElement | null>(null);

// Enter: tarixga yoziladi va klaviatura yopiladi — natijalarni to'liq ekranda ko'rish uchun
function onSubmit() {
   emit("submit");
   input.value?.blur();
}

defineExpose({
   focus: () => input.value?.focus(),
   blur: () => input.value?.blur(),
});
</script>

<style scoped>
.search-action-enter-active,
.search-action-leave-active {
   transition:
      opacity 0.18s var(--page-ease, ease),
      transform 0.18s var(--page-ease, ease);
}

.search-action-enter-from,
.search-action-leave-to {
   opacity: 0;
   transform: translateX(8px);
}

/* Lupa ↔ strelka: bir joyda aylanib almashadi */
.search-icon {
   position: absolute;
   transition:
      opacity 0.2s ease,
      transform 0.25s var(--page-ease, ease);

   &.is-hidden {
      opacity: 0;
      transform: rotate(-90deg) scale(0.6);
   }
}
</style>
