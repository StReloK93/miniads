<template>
   <Field :name="name" v-slot="{ field, meta, handleChange }">
      <Listbox
         v-slot="{ open: slotOpen }"
         :model-value="field.value"
         @update:model-value="handleChange"
         as="div"
         class="ui-select field p-0!"
         :data-error="(meta.touched && !meta.valid) || undefined"
      >
         <!-- CONTROL ui-select  -->
         <ListboxButton ref="reference" class="ui-select__control w-full flex items-center justify-between px-3 py-2">
            <span class="truncate"> {{ selectedLabel(field.value) }} </span>

            <span class="flex gap-2 items-center">
               <Check v-if="field.value" class="h-4 w-4 text-blue-600" />
               <ChevronDown class="h-4 w-4 transition-transform duration-150" :class="{ 'rotate-180': slotOpen }" />
            </span>
         </ListboxButton>

         <!-- OPTIONS -->
         <div class="relative">
            <Transition
               enter-active-class="transition ease-out duration-150"
               enter-from-class="opacity-0 scale-95"
               enter-to-class="opacity-100 scale-100"
               leave-active-class="transition ease-in duration-100"
               leave-from-class="opacity-100 scale-100"
               leave-to-class="opacity-0 scale-95"
            >
               <!-- 🔑 MUHIM: open && isPositioned -->
               <ListboxOptions
                  v-if="slotOpen"
                  v-show="isPositioned"
                  ref="floating"
                  :style="floatingStyles"
                  class="ui-select__options z-50 w-(--ref-width) overflow-auto border border-(--z-color-border)"
               >
                  <ListboxOption
                     v-for="option in normalizedOptions"
                     :key="option.value"
                     :value="option.value"
                     class="cursor-pointer px-3 py-2 flex justify-between items-center hover:bg-gray-100"
                     :class="{ 'bg-(--z-color-border)': field.value === option.value }"
                     v-slot="{ selected }"
                  >
                     <span>{{ option.label }}</span>
                     <Check v-if="selected" class="h-4 w-4 text-blue-600" />
                  </ListboxOption>
               </ListboxOptions>
            </Transition>
         </div>
      </Listbox>
   </Field>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { Field } from "vee-validate";
import { Listbox, ListboxButton, ListboxOptions, ListboxOption } from "@headlessui/vue";
import { Check, ChevronDown } from "lucide-vue-next";
import { useFloating, offset, flip, shift, size, autoUpdate } from "@floating-ui/vue";

/* ================= PROPS ================= */

const props = withDefaults(
   defineProps<{
      name: string;
      options: any[];
      placeholder?: string;
   }>(),
   { placeholder: "Tanlang" },
);

/* ================= STATE ================= */

const reference = ref<HTMLElement | null>(null);
const floating = ref<HTMLElement | null>(null);
const isPositioned = ref(false);

/* ================= FLOATING ================= */

const { floatingStyles } = useFloating(reference, floating, {
   placement: "bottom-start",
   whileElementsMounted: autoUpdate,
   middleware: [
      offset(4),
      flip(),
      shift({ padding: 8 }),
      size({
         apply({ rects, elements }) {
            elements.floating.style.setProperty("--ref-width", `${rects.reference.width}px`);

            // 🔥 faqat joy aniq bo‘lganda render qilish uchun
            if (!isPositioned.value) {
               isPositioned.value = true;
            }
         },
      }),
   ],
});

/* 🔑 open yopilganda reset */
watch(
   () => floating.value,
   (val) => {
      if (!val) {
         isPositioned.value = false;
      }
   },
);

/* ================= OPTIONS ================= */

const normalizedOptions = computed(() =>
   props.options.map((opt) => {
      if (typeof opt === "object") {
         return { label: opt.label, value: opt.value };
      }
      return { label: String(opt), value: opt };
   }),
);

function selectedLabel(value: any) {
   return normalizedOptions.value.find((o) => o.value === value)?.label ?? props.placeholder;
}
</script>
