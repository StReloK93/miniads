<template>
   <!-- Logo va muqova: bosib tanlanadi; "×" — olib tashlash (saqlanganda kuchga kiradi). Holat sahifada (pick/remove) -->
   <div class="grid grid-cols-[auto_1fr] gap-3 mt-2">
      <div>
         <p class="label">{{ $t("vitrina.form.logo") }}</p>
         <div class="relative">
            <label class="pick is-logo">
               <img v-if="logo" :src="logo" alt="" class="size-full object-cover" />
               <Camera v-else class="size-5" />
               <input
                  type="file"
                  accept="image/jpeg,image/png,image/webp"
                  class="hidden"
                  @change="onPick($event, 'logo')"
               />
            </label>
            <BaseButton
               v-if="logo"
               class="pick-remove"
               severity="glass"
               rounded
               icon-only
               size="xs"
               :aria-label="$t('imageField.remove')"
               @click="emit('remove', 'logo')"
            >
               <template #icon><X class="size-3.5 opacity-70" /></template>
            </BaseButton>
         </div>
      </div>
      <div>
         <p class="label">{{ $t("vitrina.form.cover") }}</p>
         <div class="relative">
            <label class="pick is-cover">
               <img v-if="cover" :src="cover" alt="" class="absolute inset-0 size-full object-cover" />
               <ImagePlus v-else class="size-5" />
               <input
                  type="file"
                  accept="image/jpeg,image/png,image/webp"
                  class="hidden"
                  @change="onPick($event, 'cover')"
               />
            </label>
            <BaseButton
               v-if="cover"
               class="pick-remove"
               severity="glass"
               rounded
               icon-only
               size="xs"
               :aria-label="$t('imageField.remove')"
               @click="emit('remove', 'cover')"
            >
               <template #icon><X class="size-3.5 opacity-70" /></template>
            </BaseButton>
         </div>
      </div>
   </div>
</template>

<script setup lang="ts">
import { Camera, ImagePlus, X } from "lucide-vue-next";
import BaseButton from "@shared/ui/BaseButton.vue";

type ImageField = "logo" | "cover";

defineProps<{ logo: string | null; cover: string | null }>();
const emit = defineEmits<{
   (e: "pick", field: ImageField, file: File): void;
   (e: "remove", field: ImageField): void;
}>();

function onPick(event: Event, field: ImageField) {
   const input = event.target as HTMLInputElement;
   const file = input.files?.[0];
   input.value = "";
   if (file) emit("pick", field, file);
}
</script>

<style scoped>
/* BaseFormFields sarlavhasi bilan bir xil */
.label {
   display: block;
   margin-bottom: 0.375rem;
   padding: 0 0.125rem;
   color: var(--z-foreground);
   font-size: 0.75rem;
   font-weight: 600;
   letter-spacing: 0.025em;
}

.pick {
   position: relative;
   display: grid;
   height: 4.5rem;
   place-items: center;
   overflow: hidden;
   border: 1.5px dashed var(--z-border);
   background: var(--z-card);
   color: var(--z-muted-text);
   cursor: pointer;

   &.is-logo {
      width: 4.5rem;
      border-radius: 999px;
   }

   &.is-cover {
      border-radius: var(--z-rounded);
   }
}

/* Minimal: kichik shisha doira, rangsiz "×" — logo va muqovada bir xil joyda (o'ng yuqori burchak) */
.pick-remove {
   position: absolute;
   top: -0.125rem;
   right: -0.125rem;
   z-index: 1;
}
</style>
