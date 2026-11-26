<template>
   <Teleport to="body">
      <Transition name="adm-sheet">
         <div v-if="open" class="adm-sheet-layer adm-shell" @keydown.esc.stop="close">
            <div class="adm-sheet-backdrop z-overlay" aria-hidden="true" @click="close" />
            <section
               ref="panel"
               class="adm-sheet adm-detail z-glass-strong"
               role="dialog"
               aria-modal="true"
               :aria-label="title"
               tabindex="-1"
            >
               <!-- Sarlavha va yopish doim ko'rinib turadi -->
               <header class="adm-detail-head">
                  <div class="adm-sheet-grip" aria-hidden="true" />
                  <div class="adm-detail-title">
                     <div class="min-w-0 flex-1">
                        <h2>{{ title }}</h2>
                        <p v-if="subtitle">{{ subtitle }}</p>
                     </div>
                     <button ref="closeButton" type="button" class="adm-icon-btn" aria-label="Yopish" @click="close">
                        <X class="size-5" />
                     </button>
                  </div>
               </header>

               <div class="adm-detail-body">
                  <slot />
               </div>

               <!-- Amallar pastda qotirilgan: ro'yxatni oxirigacha aylantirish shart emas -->
               <footer v-if="$slots.actions" class="adm-detail-actions">
                  <slot name="actions" />
               </footer>
            </section>
         </div>
      </Transition>
   </Teleport>
</template>

<script setup lang="ts">
import { nextTick, onBeforeUnmount, ref, watch } from "vue";
import { X } from "lucide-vue-next";
import { useSwipeToClose } from "@admin/modules/useSwipeToClose";

/**
 * E'lon / foydalanuvchi tafsiloti: telefonda pastdan chiqadi (ichi o'zi aylanadi, amallar pastda),
 * kompyuterda markazda. Ro'yxat joyida qoladi — yopilganda o'sha joyga qaytiladi.
 */
const props = defineProps<{ open: boolean; title: string; subtitle?: string }>();
const emit = defineEmits<{ (e: "close"): void }>();

const panel = ref<HTMLElement | null>(null);
const closeButton = ref<HTMLButtonElement | null>(null);
let opener: HTMLElement | null = null;

function close() {
   emit("close");
}

// Telefonda pastga surib yopish; ichi aylantirilgan bo'lsa — avval aylanadi
useSwipeToClose(panel, close, () => panel.value?.querySelector<HTMLElement>(".adm-detail-body") ?? null);

/** Esc: ustida boshqa oyna (tasdiqlash, xabar yozish) ochiq bo'lsa — avval o'sha yopiladi */
function onKeydown(event: KeyboardEvent) {
   if (event.key !== "Escape" || !props.open) return;
   const dialogs = document.querySelectorAll('[role="dialog"]');
   if (dialogs[dialogs.length - 1] === panel.value) close();
}

watch(
   () => props.open,
   async (open) => {
      document.documentElement.classList.toggle("adm-lock", open);
      if (open) {
         opener = document.activeElement as HTMLElement | null;
         window.addEventListener("keydown", onKeydown);
         await nextTick();
         closeButton.value?.focus({ preventScroll: true });
      } else {
         window.removeEventListener("keydown", onKeydown);
         // Fokus bosilgan qatorga qaytadi (klaviatura bilan ishlaganda joy yo'qolmasin)
         opener?.focus?.({ preventScroll: true });
      }
   },
);

onBeforeUnmount(() => {
   window.removeEventListener("keydown", onKeydown);
   document.documentElement.classList.remove("adm-lock");
});
</script>
