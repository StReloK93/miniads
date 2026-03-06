<template>
   <TransitionRoot appear :show="open" as="template">
      <Dialog as="div" class="relative z-100" @close="close">
         <!-- Backdrop -->
         <TransitionChild
            as="template"
            enter="transition-opacity duration-200 ease-out"
            enter-from="opacity-0"
            enter-to="opacity-100"
            leave="transition-opacity duration-150 ease-in"
            leave-from="opacity-100"
            leave-to="opacity-0"
         >
            <div class="fixed inset-0 bg-black/40 backdrop-blur-[2px] z-100" />
         </TransitionChild>

         <div class="fixed inset-0 overflow-y-auto z-200">
            <div class="flex min-h-full justify-center p-4 items-center overflow-hidden">
               <!-- Panel -->
               <TransitionChild
                  as="template"
                  enter="transition duration-200 ease-out"
                  enter-from="opacity-0 translate-y-4 scale-[0.98]"
                  enter-to="opacity-100 translate-y-0 scale-100"
                  leave="transition duration-150 ease-in"
                  leave-from="opacity-100 translate-y-0 scale-100"
                  leave-to="opacity-0 translate-y-4 scale-[0.98]"
               >
                  <DialogPanel
                     class="w-full max-w-md transform rounded-(--z-rounded) bg-(--z-background) text-left align-middle shadow-sm transition-all"
                  >
                     <div class="p-(--space-lg)">
                        <!-- Header -->
                        <div class="flex min-w-0 gap-3" :class="[description ? 'items-start' : 'items-center']">
                           <div
                              class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-(--z-muted) text-(--z-primary)"
                           >
                              <slot name="icon"> </slot>
                           </div>

                           <div class="min-w-0">
                              <DialogTitle as="h3" class="text-[17px] font-semibold leading-6 text-(--z-foreground)">
                                 {{ title }}
                              </DialogTitle>

                              <DialogDescription
                                 v-if="description"
                                 class="mt-1 text-sm leading-5 text-(--z-muted-text)"
                              >
                                 {{ description }}
                              </DialogDescription>
                           </div>
                        </div>

                        <!-- Body -->
                        <div class="mt-4">
                           <slot>
                              <p class="text-sm leading-6 text-(--z-muted-text)">
                                 Bu yerga modal ichidagi content yoziladi.
                              </p>
                           </slot>
                        </div>

                        <!-- Footer -->
                        <div class="mt-6 flex gap-4">
                           <BaseButton @click="close" type="button" severity="glass" class="w-full">
                              {{ cancelText }}
                           </BaseButton>

                           <BaseButton @click="$emit('confirm')" type="button" class="w-full">
                              {{ confirmText }}
                           </BaseButton>
                        </div>
                     </div>
                  </DialogPanel>
               </TransitionChild>
            </div>
         </div>
      </Dialog>
   </TransitionRoot>
</template>

<script setup lang="ts">
import { X as Close } from "lucide-vue-next";
import { Dialog, DialogDescription, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from "@headlessui/vue";

interface Props {
   open: boolean;
   title?: string;
   description?: string;
   confirmText?: string;
   cancelText?: string;
   showCancel?: boolean;
   danger?: boolean;
}

withDefaults(defineProps<Props>(), {
   description: "",
   confirmText: "Tasdiqlash",
   cancelText: "Bekor qilish",
   showCancel: true,
   danger: false,
});

const emit = defineEmits<{
   (e: "close"): void;
   (e: "confirm"): void;
}>();

function close() {
   emit("close");
}
</script>
