<template>
   <Field :name="props.name" v-slot="{ field, handleChange }">
      <div class="ui-textarea" :data-size="size">
         <textarea
            ref="el"
            v-bind="{ ...field, ...$attrs }"
            :rows="rows"
            :style="{
               maxHeight: maxHeight ? maxHeight + 'px' : undefined,
            }"
            class="ui-textarea__field w-full resize-none transition focus:outline-none"
            @input="onInput($event, handleChange)"
         />
      </div>
   </Field>
</template>

<script setup lang="ts">
import { Field } from "vee-validate";
import { ref, watch, nextTick } from "vue";

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

function onInput(event, callback: Function) {
   callback?.(event);
   resize();
}
</script>
