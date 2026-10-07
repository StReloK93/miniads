<template>
   <Teleport to="body">
      <TransitionRoot appear :show="open" as="template">
         <Dialog as="div" class="ui-drawer-overlay" @close="close">
            <TransitionChild
               as="template"
               enter="transition-opacity duration-200 ease-out"
               enter-from="opacity-0"
               enter-to="opacity-100"
               leave="transition-opacity duration-150 ease-in"
               leave-from="opacity-100"
               leave-to="opacity-0"
            >
               <div class="ui-drawer-backdrop" />
            </TransitionChild>
            <div class="ui-drawer-shell">
               <TransitionChild
                  as="template"
                  enter="transition duration-200 ease-out"
                  enter-from="translate-x-5 opacity-0"
                  enter-to="translate-x-0 opacity-100"
                  leave="transition duration-150 ease-in"
                  leave-from="translate-x-0 opacity-100"
                  leave-to="translate-x-5 opacity-0"
               >
                  <DialogPanel class="ui-drawer-panel">
                     <header class="ui-drawer-header">
                        <div class="min-w-0">
                           <slot name="header">
                              <DialogTitle class="ui-drawer-title">{{ title }}</DialogTitle>
                              <DialogDescription v-if="description" class="ui-drawer-description">
                                 {{ description }}
                              </DialogDescription>
                           </slot>
                        </div>
                        <button class="ui-drawer-close" type="button" aria-label="Yopish" @click="close">
                           <X class="size-5" />
                        </button>
                     </header>
                     <div class="ui-drawer-content"><slot /></div>
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
      document.body.classList.toggle("ui-drawer-open", open);
   },
   { immediate: true },
);

onBeforeUnmount(() => {
   document.body.classList.remove("ui-drawer-open");
});
</script>

<style>
.ui-drawer-overlay {
   position: fixed;
   z-index: 1000;
   inset: 0;
}

.ui-drawer-backdrop {
   position: fixed;
   inset: 0;
   background: rgb(15 23 42 / 42%);
   backdrop-filter: blur(2px);
}

.ui-drawer-shell {
   position: fixed;
   z-index: 1;
   inset: 0;
   display: flex;
   justify-content: flex-end;
   overflow: hidden;
   pointer-events: none;
}

.ui-drawer-panel {
   display: flex;
   width: min(520px, 100%);
   height: 100%;
   flex-direction: column;
   pointer-events: auto;
   border-left: 1px solid var(--z-border);
   background: var(--z-card);
   color: var(--z-foreground);
   box-shadow: -20px 0 60px rgb(15 23 42 / 12%);
}

.ui-drawer-header {
   display: flex;
   min-height: 68px;
   align-items: center;
   justify-content: space-between;
   gap: 16px;
   border-bottom: 1px solid var(--z-border);
   padding: 14px 20px;
}

.ui-drawer-title {
   margin: 0;
   font-size: 16px;
   font-weight: 650;
}

.ui-drawer-description {
   margin: 4px 0 0;
   color: var(--z-muted-text);
   font-size: 12px;
}

.ui-drawer-close {
   display: grid;
   width: 36px;
   height: 36px;
   flex: 0 0 auto;
   place-items: center;
   border: 0;
   border-radius: 50%;
   background: var(--z-muted);
   color: var(--z-foreground);
   cursor: pointer;
}

.ui-drawer-content {
   min-height: 0;
   flex: 1;
   overflow: auto;
   padding: 20px;
}

.ui-drawer-fade-enter-active,
.ui-drawer-fade-leave-active,
.ui-drawer-slide-enter-active,
.ui-drawer-slide-leave-active {
   transition: all 180ms ease;
}

.ui-drawer-fade-enter-from,
.ui-drawer-fade-leave-to {
   opacity: 0;
}

.ui-drawer-slide-enter-from,
.ui-drawer-slide-leave-to {
   transform: translateX(24px);
   opacity: 0;
}

@media (max-width: 640px) {
   .ui-drawer-panel {
      width: 100%;
      border-left: 0;
   }

   .ui-drawer-content {
      padding: 15px;
   }
}
</style>
