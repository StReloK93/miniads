<template>
   <Field :name="name" v-slot="{ field, meta, handleChange }">
      <Listbox
         :model-value="field.value"
         @update:model-value="handleChange"
         as="div"
         class="ui-select"
         :data-size="size"
         :data-error="(meta.touched && !meta.valid) || undefined"
      >
         <div class="relative">
            <!-- CONTROL -->
            <ListboxButton
               class="ui-select__control w-full flex items-center justify-between transition focus:outline-none"
            >
               <span class="truncate">
                  {{ selectedLabel(field.value) }}
               </span>

               <ChevronDownIcon class="h-5 w-5 text-(--color-text-secondary)" />
            </ListboxButton>

            <!-- OPTIONS -->
            <ListboxOptions class="ui-select__options absolute z-10 mt-1 w-full overflow-auto">
               <ListboxOption
                  v-for="option in normalizedOptions"
                  :key="option.value"
                  :value="option.value"
                  class="ui-select__option"
                  v-slot="{ selected }"
               >
                  <span class="truncate">
                     {{ option.label }}
                  </span>

                  <CheckIcon v-if="selected" class="h-4 w-4 text-(--color-primary)" />
               </ListboxOption>
            </ListboxOptions>
         </div>
      </Listbox>
   </Field>
</template>

<script setup lang="ts">
import { CheckIcon, ChevronDownIcon } from "@heroicons/vue/24/outline";
import { Listbox, ListboxButton, ListboxOptions, ListboxOption } from "@headlessui/vue";
import { Field } from "vee-validate";
import { computed } from "vue";

type Size = "sm" | "md" | "lg";

type NormalizedOption = {
   label: string;
   value: any;
};

const props = withDefaults(
   defineProps<{
      name: string;
      options: any[];
      size?: Size;
      labelKey?: string;
      valueKey?: string;
      placeholder?: string;
   }>(),
   {
      size: "md",
      placeholder: "Tanlang",
   },
);

/**
 * 🔑 OPTIONS NORMALIZATION
 * - string[]        → { label, value }
 * - {label,value}  → o‘z holicha
 * - custom object  → labelKey/valueKey orqali
 */
const normalizedOptions = computed<NormalizedOption[]>(() => {
   return props.options.map((opt) => {
      // 1️⃣ Oddiy string / number
      if (typeof opt === "string" || typeof opt === "number") {
         return { label: String(opt), value: opt };
      }

      // 2️⃣ { label, value }
      if ("label" in opt && "value" in opt) {
         return { label: opt.label, value: opt.value };
      }

      // 3️⃣ Custom keys
      if (props.labelKey && props.valueKey) {
         return {
            label: opt[props.labelKey],
            value: opt[props.valueKey],
         };
      }

      // 4️⃣ Fallback (xato bo‘lsa ham yiqilmasin)
      return {
         label: String(opt),
         value: opt,
      };
   });
});

function selectedLabel(value: any) {
   const found = normalizedOptions.value.find((o) => o.value === value);
   return found ? found.label : props.placeholder;
}
</script>
