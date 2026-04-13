<template>
   <Field :name="props.name" v-slot="{ field }" class="flex flex-col gap-1">
      <main class="relative">
         <span class="text-xs text-gray-500 absolute -top-5 right-1.5">
            {{ images_source.length }} / {{ props.max }}
         </span>
         <div :class="{ 'grid gap-1 grid-cols-3': attrs.multiple }">
            <main
               v-for="(image, index) in images_source"
               :key="index"
               :class="[index == 0 ? 'col-span-3 aspect-video' : 'aspect-square']"
               class="relative"
            >
               <BaseButton
                  @click="deleteImage({ url: image.url, index }, field)"
                  class="absolute! top-0 right-0 z-50"
                  rounded
                  severity="danger"
                  icon-only
                  variant="text"
                  size="sm"
               >
                  <template #icon>
                     <Trash class="size-5" />
                  </template>
               </BaseButton>

               <div class="w-full">
                  <img
                     :src="image.url"
                     :class="[index == 0 ? 'col-span-3 aspect-video' : 'aspect-square']"
                     class="rounded-(--z-rounded) grayscale object-cover w-full"
                  />
               </div>
            </main>

            <label
               v-if="canAddMore"
               :class="[images_source.length > 0 ? 'aspect-square' : 'col-span-3 aspect-video']"
               class="cursor-pointer flex justify-center items-center rounded-(--z-rounded) bg-(--z-primary)/5 border border-(--z-primary) border-dashed"
            >
               <input
                  ref="inputFile"
                  type="file"
                  accept="image/jpeg,image/png,image/webp"
                  v-bind="$attrs"
                  @change="(event) => onNativeFileChange(event, field)"
                  class="hidden"
                  @vue:mounted="inputMouted(field)"
               />
               <div class="size-10 bg-(--z-field-background) rounded-full flex justify-center items-center">
                  <Camera class="size-5" />
               </div>
            </label>
         </div>
      </main>
   </Field>
</template>

<script setup lang="ts">
import { Field } from "vee-validate";
import { Camera, Trash } from "lucide-vue-next";
import { computed, ref, useAttrs } from "vue";

const inputFile = ref<HTMLInputElement | null>(null);
const attrs = useAttrs();

const props = withDefaults(
   defineProps<{
      name: string;
      max?: number;
   }>(),
   {
      max: 7,
   },
);

interface IImage {
   id?: number | null;
   url: string;
   file: File | string | null;
}

const images_source = ref<IImage[]>([]);

const canAddMore = computed(() => {
   if (!attrs.multiple) return images_source.value.length === 0;
   return images_source.value.length < props.max;
});

async function inputMouted($field: any) {
   const nullable = attrs.multiple ? [] : null;
   await $field.onInput($field.value || nullable);

   if ($field.value) {
      const isArray = Array.isArray($field.value);

      if (isArray) {
         images_source.value = $field.value.map((image: any) => ({
            ...image,
            file: image.file ?? image.url,
         }));
      } else {
         images_source.value = [
            {
               url: $field.value.url ?? $field.value,
               file: $field.value.file ?? $field.value.url ?? $field.value,
            },
         ];
      }
   }
}

function onNativeFileChange(event: Event, $field: any) {
   const target = event.target as HTMLInputElement;
   if (!target?.files || target.files.length === 0) return;

   const files = Array.from(target.files);

   if (attrs.multiple) {
      const remainCount = props.max - images_source.value.length;
      if (remainCount <= 0) {
         if (inputFile.value) inputFile.value.value = "";
         return;
      }

      const allowedFiles = files.slice(0, remainCount);

      images_source.value.push(
         ...allowedFiles.map((file) => ({
            id: null,
            url: URL.createObjectURL(file),
            file,
         })),
      );

      $field.onInput(images_source.value);

      if (inputFile.value) inputFile.value.value = "";
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
   const files = $field.value as (File | string)[] | File | string | null;
   if (!files) return;

   if (attrs.multiple) {
      const newFiles = (files as (File | string)[]).filter((_, i) => i !== image.index);
      $field.onInput(newFiles);

      if (images_source.value[image.index]?.file instanceof File) {
         URL.revokeObjectURL(image.url);
      }

      images_source.value = images_source.value.filter((_, index) => index !== image.index);
   } else {
      $field.onInput(null);

      if (images_source.value[0]?.file instanceof File) {
         URL.revokeObjectURL(image.url);
      }

      images_source.value = [];
   }
}
</script>
