<template>
   <FormField v-slot="$field" class="flex flex-col gap-1">
      <main class="relative">
         <input
            type="file"
            :id="props.input.name"
            v-bind="props.input.props"
            @change="(event) => onNativeFileChange(event, $field)"
            class="hidden"
            @vue:mounted="inputMouted($field)"
         />
         <div class="grid gap-3 grid-cols-3">
            <main v-for="(image, index) in images_source" :key="index" class="relative">
               <Button
                  icon="pi pi-times"
                  size="small"
                  rounded
                  variant="text"
                  class="absolute! top-1 right-1 z-50"
                  severity="contrast"
                  @click.stop="deleteImage({ url: image.url, index }, $field)"
               />

               <img
                  :src="image.url"
                  class="rounded-md aspect-square grayscale object-cover w-full border border-secondary"
               />
            </main>
            <label
               class="bg-secondary aspect-square cursor-pointer flex justify-center items-center rounded-md border border-secondary hover:border-surface-300 hover:bg-surface-100 p-1.5"
               :for="props.input.name"
            >
               <img :src="'/images/image.svg'" class="w-10 grayscale" />
            </label>
         </div>
      </main>
   </FormField>
</template>

<script setup lang="ts">
import { ref } from "vue";
const props = defineProps<{ input }>();

interface IImage {
   id?: number | null;
   url: string;
   file: File | string | null;
}

const images_source = ref<IImage[]>([]);
async function inputMouted($field: any) {
   await $field.onInput({ value: $field.value });
   if ($field.value) {
      images_source.value = $field.value?.map((image) => ({ ...image, file: image.url }));
   }
}

function onNativeFileChange(event: Event, $field: any) {
   const target = event.target as HTMLInputElement;
   if (!target?.files || target.files.length === 0) return;

   const files = Array.from(target.files);

   images_source.value.push(
      ...files.map((file) => {
         return { id: null, url: URL.createObjectURL(file), file };
      }),
   );
   $field.onInput({ value: images_source.value });
}

function deleteImage(image: { index: number; url: string }, $field: any) {
   const files = $field.value as (File | string)[];
   if (!files || files.length === 0) return;

   const newFiles = files.filter((_, i) => i !== image.index);

   $field.onInput({ value: newFiles });

   if (images_source.value[image.index].file instanceof File) {
      URL.revokeObjectURL(image.url);
   }
   images_source.value = images_source.value.filter((item, index) => index !== image.index);
}
</script>
