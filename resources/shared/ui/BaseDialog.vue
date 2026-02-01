<template>
   <TransitionRoot :show="modelValue" appear>
      <Dialog as="div" class="ui-dialog" @close="emit('update:modelValue', false)">
         <!-- OVERLAY -->
         <TransitionChild
            as="div"
            class="ui-dialog__overlay"
            enter="duration-(--duration-normal) ease-(--ease-soft)"
            enter-from="opacity-0"
            enter-to="opacity-100"
            leave="duration-(--duration-fast) ease-(--ease-soft)"
            leave-from="opacity-100"
            leave-to="opacity-0"
         />

         <!-- WRAPPER -->
         <div class="ui-dialog__wrapper">
            <TransitionChild
               as="div"
               enter="duration-(--duration-normal) ease-(--ease-soft)"
               enter-from="opacity-0 scale-95"
               enter-to="opacity-100 scale-100"
               leave="duration-(--duration-fast) ease-(--ease-soft)"
               leave-from="opacity-100 scale-100"
               leave-to="opacity-0 scale-95"
            >
               <DialogPanel class="ui-dialog__panel" :data-size="size">
                  <!-- HEADER -->
                  <header v-if="$slots.title" class="ui-dialog__header">
                     <DialogTitle class="ui-dialog__title">
                        <slot name="title" />
                     </DialogTitle>
                  </header>

                  <!-- BODY -->
                  <section class="ui-dialog__body">
                     <slot />
                  </section>

                  <!-- FOOTER -->
                  <footer v-if="$slots.footer" class="ui-dialog__footer">
                     <slot name="footer" />
                  </footer>
               </DialogPanel>
            </TransitionChild>
         </div>
      </Dialog>
   </TransitionRoot>
</template>

<script setup lang="ts">
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from "@headlessui/vue";

type Size = "sm" | "md" | "lg";

withDefaults(
   defineProps<{
      modelValue: boolean;
      size?: Size;
   }>(),
   {
      size: "md",
   },
);

const emit = defineEmits<{
   (e: "update:modelValue", value: boolean): void;
}>();
</script>
