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
         <!-- CONTROL -->
         <ListboxButton ref="reference" class="ui-select__control w-full flex items-center justify-between px-3 py-2">
            <span class="truncate" :class="{ 'text-(--z-muted-text)': !field.value }">
               {{ selectedLabel(field.value) }}
            </span>

            <span class="flex gap-2 items-center">
               <Check v-if="field.value && selectIcon" class="h-4 w-4 text-blue-600" />
               <ChevronDown class="h-4 w-4 transition-transform duration-150" :class="{ 'rotate-180': slotOpen }" />
            </span>
         </ListboxButton>

         <div class="relative">
            <!-- Tashqi wrapper faqat positioning uchun -->
            <div v-if="slotOpen" ref="floating" :style="floatingStyles" class="z-50 w-(--ref-width)">
               <Transition
                  enter-active-class="transition ease-out duration-150"
                  enter-from-class="opacity-0 scale-95"
                  enter-to-class="opacity-100 scale-100"
                  leave-active-class="transition ease-in duration-100"
                  leave-from-class="opacity-100 scale-100"
                  leave-to-class="opacity-0 scale-95"
               >
                  <!-- Ichki blok faqat animatsiya uchun -->
                  <ListboxOptions
                     v-show="isPositioned"
                     static
                     class="ui-select__options overflow-auto border border-(--z-border)"
                  >
                     <ListboxOption
                        v-for="option in normalizedOptions"
                        :key="option.value"
                        :value="option.value"
                        class="ui-select__option"
                        :class="{ 'bg-(--z-muted)': field.value === option.value }"
                        v-slot="{ selected }"
                     >
                        <span>{{ option.label }}</span>
                        <Check v-if="selected" class="h-4 w-4 text-blue-600" />
                     </ListboxOption>
                  </ListboxOptions>
               </Transition>
            </div>
         </div>
      </Listbox>
   </Field>
</template>

<script setup lang="ts">
import { ref, computed, nextTick, watch } from "vue";
import { Field } from "vee-validate";
import { Listbox, ListboxButton, ListboxOptions, ListboxOption } from "@headlessui/vue";
import { Check, ChevronDown } from "lucide-vue-next";
import { useFloating, offset, flip, shift, size, autoUpdate } from "@floating-ui/vue";

const props = withDefaults(
   defineProps<{
      name: string;
      options: any[];
      value?: string;
      id?: string;
      placeholder?: string;
      selectIcon?: boolean;
   }>(),
   { placeholder: "Tanlang", value: "value", id: "id", selectIcon: true },
);

const reference = ref<HTMLElement | null>(null);
const floating = ref<HTMLElement | null>(null);
const isPositioned = ref(false);

const { floatingStyles, update } = useFloating(reference, floating, {
   placement: "bottom-start",
   whileElementsMounted: autoUpdate,
   middleware: [
      offset(4),
      flip(),
      shift({ padding: 8 }),
      size({
         apply({ rects, elements }) {
            elements.floating.style.setProperty("--ref-width", `${rects.reference.width}px`);
         },
      }),
   ],
});

const normalizedOptions = computed(() =>
   props.options.map((opt) => {
      if (typeof opt === "object") {
         return { label: opt[props.value], value: opt[props.id] };
      }
      return { label: String(opt), value: opt };
   }),
);

function selectedLabel(value: any) {
   return normalizedOptions.value.find((o) => o.value === value)?.label ?? props.placeholder;
}

/**
 * Birinchi ochilganda:
 * 1) wrapper DOMga kiradi
 * 2) floating update bo'ladi
 * 3) keyin ko'rsatamiz
 */
watch(floating, async (el) => {
   if (el) {
      isPositioned.value = false;
      await nextTick();
      await update();
      isPositioned.value = true;
   } else {
      isPositioned.value = false;
   }
});
</script>

<style scoped lang="scss">
.ui-select__control {
   padding-inline: var(--space-md);
   padding-block: var(--space-md);
   cursor: pointer;
}

.child .ui-select__control {
   font-size: 14px;
   padding-inline: var(--space-md);
   padding-block: 10px;
   cursor: pointer;
}

/* ERROR */
.ui-select[data-error] .ui-select__control {
   border-color: var(--z-danger);
}

/* OPTIONS */
.ui-select__options {
   background: var(--z-card);
   border-radius: var(--z-rounded);
   max-height: 240px;
   padding: 5px;
   box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
}

.child .ui-select__options {
   font-size: 14px;
}

/* OPTION */
.ui-select__option {
   display: flex;
   justify-content: space-between;
   align-items: center;
   padding: var(--space-md) var(--space-md);
   border-radius: 10px;
   cursor: pointer;
}

.ui-select__option[data-headlessui-state~="selected"] {
   color: var(--z-primary);
}
</style>
