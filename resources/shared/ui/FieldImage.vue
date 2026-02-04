<template>
   <Field :name="props.name" v-slot="{ field, handleChange }" class="flex flex-col gap-1">
      <main class="relative">
         <input
            ref="inputFile"
            type="file"
            :id="props.name + 'id'"
            v-bind="$attrs"
            @input="handleChange"
            @change="(event) => onNativeFileChange(event, field)"
            class="hidden"
            @vue:mounted="inputMouted(field)"
         />
         <div :class="{ 'grid gap-4 grid-cols-3': attrs.multiple }">
            <main v-for="(image, index) in images_source" :key="index" class="relative">
               <BaseButton
                  @click.stop="deleteImage({ url: image.url, index }, field)"
                  class="absolute! top-1 right-1 z-50"
                  rounded
                  severity="secondary"
                  icon-only
                  size="sm"
               >
                  <template #icon>
                     <XMarkIcon class="size-5" />
                  </template>
               </BaseButton>
               <div class="w-full aspect-square">
                  <img
                     :src="image.url"
                     class="rounded-md aspect-square grayscale object-cover w-full border border-slate-200 dark:border-slate-700"
                  />
               </div>
            </main>
            <label
               v-if="attrs.multiple || images_source.length === 0"
               class="bg-secondary aspect-square cursor-pointer flex justify-center items-center rounded-md border border-slate-200 dark:border-slate-700"
               :for="props.name + 'id'"
            >
               <img :src="'/images/image.svg'" class="w-10 grayscale" />
            </label>
         </div>
      </main>
   </Field>
</template>

<script setup lang="ts">
import { Field } from "vee-validate";
import { XMarkIcon } from "@heroicons/vue/24/outline";
import { ref } from "vue";
import { useAttrs } from "vue";

const inputFile = ref<HTMLInputElement | null>(null);
const attrs = useAttrs();
const props = defineProps<{ name: string }>();

interface IImage {
   id?: number | null;
   url: string;
   file: File | string | null;
}

const images_source = ref<IImage[]>([]);
async function inputMouted($field: any) {
   var nullable;
   if (attrs.multiple) nullable = [];
   else nullable = null;
   await $field.onInput($field.value || nullable);

   if ($field.value) {
      const isArray = Array.isArray($field.value);

      if (isArray) {
         images_source.value = $field.value?.map((image) => ({ ...image, file: image.url }));
      } else {
         images_source.value = [{ url: $field.value, file: $field.value.url }];
      }
   }
}

function onNativeFileChange(event: Event, $field: any) {
   const target = event.target as HTMLInputElement;
   if (!target?.files || target.files.length === 0) return;

   const files = Array.from(target.files);

   if (attrs.multiple) {
      images_source.value.push(
         ...files.map((file) => {
            return { id: null, url: URL.createObjectURL(file), file };
         }),
      );
      $field.onInput(images_source.value);
   } else {
      if (inputFile.value) inputFile.value.value = "";
      images_source.value = [
         {
            id: null,
            url: URL.createObjectURL(files[0]),
            file: files[0],
         },
      ];
      $field.onInput(files[0]);
   }
}

function deleteImage(image: { index: number; url: string }, $field: any) {
   const files = $field.value as (File | string)[];
   if (!files || files.length === 0) return;

   if (attrs.multiple) {
      const newFiles = files.filter((_, i) => i !== image.index);
      $field.onInput(newFiles);
      if (images_source.value[image.index].file instanceof File) {
         URL.revokeObjectURL(image.url);
      }
      images_source.value = images_source.value.filter((item, index) => index !== image.index);
   } else {
      $field.onInput(null);
      if (images_source.value[0].file instanceof File) {
         URL.revokeObjectURL(image.url);
      }
      images_source.value = [];
   }
}
</script>
