<template>
   <!-- Agar VeeValidate name berilgan bo'lsa -->
   <Field v-if="name" :name="name" v-slot="{ field, meta, handleChange }">
      <Listbox
         v-bind="$attrs"
         v-slot="{ open: slotOpen }"
         :model-value="modelValue !== undefined ? modelValue : field.value"
         :disabled="disabled"
         @update:model-value="(val) => onSelectValue(val, handleChange)"
         as="div"
         class="relative flex items-center transition focus:outline-none focus-within:outline-none"
         :class="[
            variant === 'addon' || variant === 'borderless'
               ? 'h-full border-0 bg-transparent shadow-none w-auto'
               : [
                    'w-full border border-(--z-border) bg-(--z-field-background) focus-within:ring-2 focus-within:ring-(--z-primary)/20',
                    props.size === 'xs'
                       ? 'min-h-7.5 rounded-md text-xs'
                       : props.size === 'sm'
                         ? 'min-h-9 rounded-lg text-xs'
                         : 'min-h-11 rounded-(--z-rounded) text-sm',
                    meta.touched && !meta.valid ? 'border-(--z-danger)!' : '',
                 ],
            disabled ? 'opacity-60 cursor-not-allowed' : '',
         ]"
      >
         <!-- CONTROL -->
         <ListboxButton
            ref="reference"
            class="flex items-center justify-between text-(--z-foreground) focus:outline-none cursor-pointer select-none"
            :class="[
               variant === 'addon' || variant === 'borderless'
                  ? 'h-8 px-2.5 py-1 text-xs sm:text-sm font-semibold rounded-lg bg-(--z-muted)/40 hover:bg-(--z-muted)/70 transition-colors gap-1.5'
                  : [
                       'w-full',
                       props.size === 'xs'
                          ? 'px-2 py-1 text-xs'
                          : props.size === 'sm'
                            ? 'px-2.5 py-1.5 text-xs'
                            : 'px-3 py-2 text-sm',
                    ],
            ]"
         >
            <span class="truncate" :class="{ 'text-(--z-muted-text)': isValueEmpty(modelValue !== undefined ? modelValue : field.value) }">
               {{ selectedLabel(modelValue !== undefined ? modelValue : field.value) }}
            </span>

            <span class="ml-1 flex shrink-0 items-center gap-1">
               <Check v-if="!isValueEmpty(modelValue !== undefined ? modelValue : field.value) && selectIcon" class="h-3.5 w-3.5 text-emerald-500" />
               <ChevronDown class="h-3.5 w-3.5 text-(--z-muted-text) transition-transform duration-150" :class="{ 'rotate-180': slotOpen }" />
            </span>
         </ListboxButton>

         <!-- DROPDOWN OPTIONS -->
         <div class="relative">
            <div v-if="slotOpen" ref="floating" :style="floatingStyles" class="z-50 w-(--ref-width)">
               <Transition
                  enter-active-class="transition ease-out duration-150"
                  enter-from-class="opacity-0 scale-95"
                  enter-to-class="opacity-100 scale-100"
                  leave-active-class="transition ease-in duration-100"
                  leave-from-class="opacity-100 scale-100"
                  leave-to-class="opacity-0 scale-95"
               >
                  <ListboxOptions
                     v-show="isPositioned"
                     static
                     class="max-h-60 overflow-auto rounded-xl border border-(--z-border) bg-(--z-card) p-1 text-(--z-foreground) shadow-xl focus:outline-none"
                  >
                     <ListboxOption
                        v-for="option in normalizedOptions"
                        :key="String(option.value)"
                        :value="option.value"
                        class="flex items-center justify-between rounded-lg cursor-pointer transition-colors duration-150 select-none"
                        :class="[
                           (modelValue !== undefined ? modelValue : field.value) === option.value
                              ? 'bg-(--z-muted) text-(--z-primary) font-semibold'
                              : 'text-(--z-foreground) hover:bg-(--z-muted)/60',
                           props.size === 'sm' || props.size === 'xs' || variant === 'addon' || variant === 'borderless' ? 'py-1.5 px-2.5 text-xs' : 'py-2 px-3 text-sm',
                        ]"
                        v-slot="{ selected }"
                     >
                        <span class="truncate">{{ option.label }}</span>
                        <Check v-if="selected" class="ml-2 h-3.5 w-3.5 shrink-0 text-emerald-500" />
                     </ListboxOption>
                  </ListboxOptions>
               </Transition>
            </div>
         </div>
      </Listbox>
   </Field>

   <!-- Agar oddiy v-model ishlatilsa (VeeValidate shart emas) -->
   <Listbox
      v-else
      v-bind="$attrs"
      v-slot="{ open: slotOpen }"
      :model-value="modelValue"
      :disabled="disabled"
      @update:model-value="onSelectValue"
      as="div"
      class="relative flex items-center transition focus:outline-none focus-within:outline-none"
      :class="[
         variant === 'addon' || variant === 'borderless'
            ? 'h-full border-0 bg-transparent shadow-none w-auto'
            : [
                 'w-full border border-(--z-border) bg-(--z-field-background) focus-within:ring-2 focus-within:ring-(--z-primary)/20',
                 props.size === 'xs'
                    ? 'min-h-7.5 rounded-md text-xs'
                    : props.size === 'sm'
                      ? 'min-h-9 rounded-lg text-xs'
                      : 'min-h-11 rounded-(--z-rounded) text-sm',
              ],
         disabled ? 'opacity-60 cursor-not-allowed' : '',
      ]"
   >
      <!-- CONTROL -->
      <ListboxButton
         ref="reference"
         class="flex items-center justify-between text-(--z-foreground) focus:outline-none cursor-pointer select-none"
         :class="[
            variant === 'addon' || variant === 'borderless'
               ? 'h-8 px-2.5 py-1 text-xs sm:text-sm font-semibold rounded-lg bg-(--z-muted)/40 hover:bg-(--z-muted)/70 transition-colors gap-1.5'
               : [
                    'w-full',
                    props.size === 'xs'
                       ? 'px-2 py-1 text-xs'
                       : props.size === 'sm'
                         ? 'px-2.5 py-1.5 text-xs'
                         : 'px-3 py-2 text-sm',
                 ],
         ]"
      >
         <span class="truncate" :class="{ 'text-(--z-muted-text)': isValueEmpty(modelValue) }">
            {{ selectedLabel(modelValue) }}
         </span>

         <span class="ml-1 flex shrink-0 items-center gap-1">
            <Check v-if="!isValueEmpty(modelValue) && selectIcon" class="h-3.5 w-3.5 text-emerald-500" />
            <ChevronDown class="h-4 w-4 text-(--z-muted-text) transition-transform duration-150" :class="{ 'rotate-180': slotOpen }" />
         </span>
      </ListboxButton>

      <!-- DROPDOWN OPTIONS -->
      <div class="relative">
         <div v-if="slotOpen" ref="floating" :style="floatingStyles" class="z-50 w-(--ref-width)">
            <Transition
               enter-active-class="transition ease-out duration-150"
               enter-from-class="opacity-0 scale-95"
               enter-to-class="opacity-100 scale-100"
               leave-active-class="transition ease-in duration-100"
               leave-from-class="opacity-100 scale-100"
               leave-to-class="opacity-0 scale-95"
            >
               <ListboxOptions
                  v-show="isPositioned"
                  static
                  class="max-h-60 overflow-auto rounded-xl border border-(--z-border) bg-(--z-card) p-1 text-(--z-foreground) shadow-xl focus:outline-none"
               >
                  <ListboxOption
                     v-for="option in normalizedOptions"
                     :key="String(option.value)"
                     :value="option.value"
                     class="flex items-center justify-between rounded-lg cursor-pointer transition-colors duration-150 select-none"
                     :class="[
                        modelValue === option.value
                           ? 'bg-(--z-muted) text-(--z-primary) font-semibold'
                           : 'text-(--z-foreground) hover:bg-(--z-muted)/60',
                        props.size === 'sm' || props.size === 'xs' || variant === 'addon' || variant === 'borderless' ? 'py-1.5 px-2.5 text-xs' : 'py-2 px-3 text-sm',
                     ]"
                     v-slot="{ selected }"
                  >
                     <span class="truncate">{{ option.label }}</span>
                     <Check v-if="selected" class="ml-2 h-3.5 w-3.5 shrink-0 text-emerald-500" />
                  </ListboxOption>
               </ListboxOptions>
            </Transition>
         </div>
      </div>
   </Listbox>
</template>

<script setup lang="ts">
import { ref, computed, nextTick, watch } from "vue";
import { Field } from "vee-validate";
import { Listbox, ListboxButton, ListboxOptions, ListboxOption } from "@headlessui/vue";
import { Check, ChevronDown } from "lucide-vue-next";
import { useFloating, offset, flip, shift, size as sizeMiddleware, autoUpdate } from "@floating-ui/vue";
import type { Placement } from "@floating-ui/vue";

const props = withDefaults(
   defineProps<{
      name?: string;
      modelValue?: any;
      options: any[];
      value?: string;
      id?: string;
      placeholder?: string;
      selectIcon?: boolean;
      size?: "xs" | "sm" | "md";
      disabled?: boolean;
      variant?: "default" | "addon" | "borderless";
      placement?: Placement;
   }>(),
   {
      placeholder: "Tanlang",
      value: "value",
      id: "id",
      selectIcon: true,
      size: "md",
      disabled: false,
      variant: "default",
   },
);

const emit = defineEmits<{
   (e: "update:modelValue", value: any): void;
   (e: "change", value: any): void;
}>();

const reference = ref<HTMLElement | null>(null);
const floating = ref<HTMLElement | null>(null);
const isPositioned = ref(false);

const effectivePlacement = computed<Placement>(() => {
   if (props.placement) return props.placement;
   if (props.variant === "addon" || props.variant === "borderless") return "bottom-end";
   return "bottom-start";
});

const { floatingStyles, update } = useFloating(reference, floating, {
   placement: effectivePlacement,
   whileElementsMounted: autoUpdate,
   middleware: [
      offset(4),
      flip(),
      shift({ padding: 8 }),
      sizeMiddleware({
         apply({ rects, elements }) {
            const minWidth = props.variant === "addon" || props.variant === "borderless" ? 110 : 160;
            elements.floating.style.setProperty("--ref-width", `${Math.max(rects.reference.width, minWidth)}px`);
         },
      }),
   ],
});

const normalizedOptions = computed(() => {
   if (!Array.isArray(props.options)) return [];
   return props.options.map((opt) => {
      if (typeof opt === "object" && opt !== null) {
         const label = opt.label ?? opt[props.value] ?? opt.name ?? String(opt);
         const value = opt.value !== undefined ? opt.value : (opt[props.id] !== undefined ? opt[props.id] : opt.id);
         return { label, value };
      }
      return { label: String(opt), value: opt };
   });
});

function isValueEmpty(val: any) {
   return val === undefined || val === null || val === "";
}

function selectedLabel(val: any) {
   if (isValueEmpty(val)) return props.placeholder;
   const found = normalizedOptions.value.find((o) => o.value === val);
   return found ? found.label : (val || props.placeholder);
}

function onSelectValue(val: any, handleChange?: (val: any) => void) {
   if (handleChange) handleChange(val);
   emit("update:modelValue", val);
   emit("change", val);
}

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
