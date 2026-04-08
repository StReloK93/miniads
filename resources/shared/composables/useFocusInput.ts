import { onMounted, onBeforeUnmount, ref } from "vue";

export function useFocusedInput() {
   const focusedElement = ref<HTMLElement | null>(null);
   const hasFocusedInput = ref(false);

   const updateFocusedElement = () => {
      const el = document.activeElement;

      if (
         el instanceof HTMLInputElement ||
         el instanceof HTMLTextAreaElement ||
         el instanceof HTMLSelectElement ||
         (el instanceof HTMLElement && el.isContentEditable)
      ) {
         focusedElement.value = el as HTMLElement;
         hasFocusedInput.value = true;
      } else {
         focusedElement.value = null;
         hasFocusedInput.value = false;
      }
   };

   onMounted(() => {
      document.addEventListener("focusin", updateFocusedElement);
      document.addEventListener("focusout", updateFocusedElement);

      updateFocusedElement();
   });

   onBeforeUnmount(() => {
      document.removeEventListener("focusin", updateFocusedElement);
      document.removeEventListener("focusout", updateFocusedElement);
   });

   return {
      focusedElement,
      hasFocusedInput,
   };
}
