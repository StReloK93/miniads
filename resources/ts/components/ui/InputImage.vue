<template>
   <FormField v-slot="$field" class="flex flex-col gap-1">
      <main class="relative">
         <input
            type="file"
            :id="props.input.name"
            v-bind="$field.props"
            @change="(event) => onNativeFileChange(event, $field)"
            class="hidden"
         />
         <Button
            v-if="src"
            icon="pi pi-times"
            size="small"
            rounded
            variant="text"
            class="absolute! top-1 right-1 z-50"
            severity="contrast"
            @click.stop="clear($field)"
         />
         <label
            class="bg-secondary aspect-square cursor-pointer w-full flex justify-center items-center rounded-xl border border-secondary hover:border-surface-300 hover:bg-surface-100 p-1.5"
            :for="props.input.name"
         >
            <Transition name="list" mode="out-in">
               <img v-if="src" :src="src" class="rounded-md w-full grayscale" />
               <img v-else :src="'/images/image.svg'" class="w-32 grayscale" />
            </Transition>
         </label>
      </main>
   </FormField>
</template>

<script setup lang="ts">
import { ref } from "vue";
const props = defineProps<{ input }>();
const src = ref<string | null>(props.input.value);

function onNativeFileChange(event: Event, $field: any) {
   const target = event.target as HTMLInputElement;
   if (target && target.files && target.files.length > 0) {
      const file = target.files[0]; // Haqiqiy File obyektini olamiz
      if (file) {
         $field.onInput({ value: file });
         const reader = new FileReader();
         reader.onload = (e) => {
            src.value = e.target?.result as string;
         };
         reader.readAsDataURL(file);
      }
   }
}

function clear($field: any) {
   src.value = null;
   $field.onInput({ value: null });
}
</script>
