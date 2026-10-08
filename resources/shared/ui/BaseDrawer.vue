<template>
   <Teleport to="body">
      <TransitionRoot appear :show="open" as="template">
         <Dialog as="div" class="fixed inset-0 z-50 overflow-hidden" @close="close">
            <TransitionChild
               as="template"
               enter="transition-opacity duration-200 ease-out"
               enter-from="opacity-0"
               enter-to="opacity-100"
               leave="transition-opacity duration-150 ease-in"
               leave-from="opacity-100"
               leave-to="opacity-0"
            >
               <div class="fixed inset-0 bg-slate-950/40 backdrop-blur-xs" />
            </TransitionChild>

            <div class="fixed inset-0 z-1 flex justify-end overflow-hidden pointer-events-none">
               <TransitionChild
                  as="template"
                  enter="transition duration-200 ease-out"
                  enter-from="translate-x-full opacity-0 sm:translate-x-6"
                  enter-to="translate-x-0 opacity-100"
                  leave="transition duration-150 ease-in"
                  leave-from="translate-x-0 opacity-100"
                  leave-to="translate-x-full opacity-0 sm:translate-x-6"
               >
                  <DialogPanel
                     class="pointer-events-auto flex h-full w-full max-w-130 flex-col border-l border-(--z-border) bg-(--z-card) text-(--z-foreground) shadow-2xl transition-all max-sm:max-w-full max-sm:border-l-0"
                  >
                     <header class="flex min-h-17 items-center justify-between gap-4 border-b border-(--z-border) px-5 py-3.5">
                        <div class="min-w-0">
                           <slot name="header">
                              <DialogTitle class="m-0 text-base font-semibold text-(--z-foreground)">
                                 {{ title }}
                              </DialogTitle>
                              <DialogDescription v-if="description" class="mt-1 text-xs text-(--z-muted-text)">
                                 {{ description }}
                              </DialogDescription>
                           </slot>
                        </div>
                        <button
                           class="grid size-9 shrink-0 place-items-center rounded-full bg-(--z-muted) text-(--z-foreground) transition hover:opacity-80 active:scale-95 cursor-pointer"
                           type="button"
                           aria-label="Yopish"
                           @click="close"
                        >
                           <X class="size-5" />
                        </button>
                     </header>
                     <div class="min-h-0 flex-1 overflow-y-auto p-5 max-sm:p-4">
                        <slot />
                     </div>
                  </DialogPanel>
               </TransitionChild>
            </div>
         </Dialog>
      </TransitionRoot>
   </Teleport>
</template>

<script setup lang="ts">
import { onBeforeUnmount, watch } from "vue";
import { Dialog, DialogDescription, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from "@headlessui/vue";
import { X } from "lucide-vue-next";

const props = withDefaults(defineProps<{ open: boolean; title?: string; description?: string }>(), {
   title: "",
   description: "",
});
const emit = defineEmits<{ (event: "close"): void }>();

function close() {
   emit("close");
}

watch(
   () => props.open,
   (open) => {
      document.body.classList.toggle("overflow-hidden", open);
   },
   { immediate: true },
);

onBeforeUnmount(() => {
   document.body.classList.remove("overflow-hidden");
});
</script>
