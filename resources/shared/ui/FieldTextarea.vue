<template>
   <Field :name="props.name" v-slot="{ field, handleChange }">
      <div class="field py-3!" :data-size="size">
         <textarea
            ref="el"
            v-bind="{ ...field, ...$attrs }"
            :rows="rows"
            :style="{
               maxHeight: maxHeight ? maxHeight + 'px' : undefined,
            }"
            class="block w-full resize-none bg-transparent text-sm leading-[1.4] text-(--z-foreground) placeholder:text-sm placeholder:text-(--z-muted-text) focus:outline-none"
            @input="onInput($event, handleChange)"
         />
      </div>
   </Field>
</template>

<script setup lang="ts">
import { Field } from "vee-validate";
import { ref } from "vue";

type Size = "sm" | "md" | "lg";

const props = withDefaults(
   defineProps<{
      name: string;
      size?: Size;
      autoHeight?: boolean;
      maxHeight?: number;
      rows?: number;
   }>(),
   {
      size: "md",
      autoHeight: true,
      rows: 2,
   },
);

const el = ref<HTMLTextAreaElement | null>(null);

function resize() {
   if (!props.autoHeight || !el.value) return;

   el.value.style.height = "auto";
   el.value.style.height = el.value.scrollHeight + "px";
}

function onInput(event: Event, callback: (event: Event) => void) {
   callback?.(event);
   resize();
}
</script>
