<template>
   <BaseDrawer :open="open" :title="item ? $t('vitrina.form.editItem') : $t('vitrina.form.newItem')" @close="emit('close')">
      <form id="vitrina-item-form" class="flex flex-col gap-4" @submit.prevent="save">
         <p v-if="error" class="text-sm text-(--z-danger)">{{ error }}</p>

         <!-- Rasmlar: eskilari (× bilan olib tashlanadi) va yangilari; ko'pi bilan 6 ta -->
         <div>
            <label class="label">{{ $t("vitrina.form.photos") }}</label>
            <div class="flex flex-wrap gap-2">
               <div v-for="(photo, index) in photos" :key="photo.key" class="thumb">
                  <img :src="photo.src" alt="" class="size-full object-cover" />
                  <button type="button" class="thumb-remove" :aria-label="$t('common.delete')" @click="removePhoto(index)">
                     <X class="size-3" />
                  </button>
               </div>
               <label v-if="photos.length < MAX_PHOTOS" class="thumb thumb-add">
                  <ImagePlus class="size-5" />
                  <input type="file" accept="image/jpeg,image/png,image/webp" multiple class="hidden" @change="addPhotos" />
               </label>
            </div>
         </div>

         <div>
            <label class="label" for="vi-title">{{ $t("vitrina.form.itemTitle") }}</label>
            <input id="vi-title" v-model="form.title" class="field" maxlength="100" required :placeholder="$t('vitrina.form.itemTitleHint')" />
         </div>

         <div v-if="sections.length">
            <label class="label">{{ $t("vitrina.form.section") }}</label>
            <FieldSelect
               :model-value="form.vitrina_section_id ?? 0"
               :options="[{ id: 0, name: $t('vitrina.otherSection') }, ...sections]"
               value="name"
               @update:model-value="(id) => (form.vitrina_section_id = id || null)"
            />
         </div>

         <div>
            <label class="label" for="vi-desc">{{ $t("common.description") }}</label>
            <textarea id="vi-desc" v-model="form.description" class="field py-2.5" rows="3" maxlength="2000" />
         </div>

         <div>
            <label class="label">{{ $t("vitrina.form.price") }}</label>
            <div class="flex flex-wrap gap-2 mb-2.5">
               <button
                  v-for="mode in PRICE_MODES"
                  :key="mode"
                  type="button"
                  class="chip"
                  :class="{ 'is-active': form.price_mode === mode }"
                  @click="form.price_mode = mode"
               >
                  {{ $t(`vitrina.priceModes.${mode}`) }}
               </button>
            </div>
            <div v-if="hasPrice" class="grid grid-cols-[1fr_auto] gap-2">
               <input
                  v-model="priceText"
                  class="field tabular-nums"
                  inputmode="numeric"
                  required
                  :placeholder="$t('vitrina.form.priceHint')"
                  @input="priceText = formatDigits(priceText)"
               />
               <FieldSelect
                  v-if="priceTypes.length"
                  v-model="form.price_type_id"
                  :options="priceTypes"
                  value="type"
                  class="w-24"
               />
            </div>
            <input
               v-if="hasPrice"
               v-model="form.unit"
               class="field mt-2"
               maxlength="30"
               :placeholder="$t('vitrina.form.unitHint')"
            />
         </div>

         <BaseSwitch v-model="form.is_available">
            <span class="block text-sm font-medium">{{ $t("vitrina.form.available") }}</span>
            <span class="block text-xs text-(--z-muted-text)">{{ $t("vitrina.form.availableHint") }}</span>
         </BaseSwitch>
      </form>

      <template #footer>
         <div class="flex gap-3">
            <!-- Ikki bosqich: birinchi bosish so'raydi, ikkinchisi o'chiradi (Telegram ichida confirm() ishonchsiz) -->
            <BaseButton v-if="item" severity="danger" :icon-only="!confirmDelete" :loading="deleting" :aria-label="$t('common.delete')" @click="remove">
               <template #icon><Trash2 class="size-4" /></template>
               <template v-if="confirmDelete">{{ $t("vitrina.form.confirmDelete") }}</template>
            </BaseButton>
            <BaseButton type="submit" form="vitrina-item-form" class="grow" :loading="saving">
               {{ $t("common.save") }}
            </BaseButton>
         </div>
      </template>
   </BaseDrawer>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from "vue";
import { ImagePlus, Trash2, X } from "lucide-vue-next";
import BaseDrawer from "@shared/ui/BaseDrawer.vue";
import BaseButton from "@shared/ui/BaseButton.vue";
import BaseSwitch from "@shared/ui/BaseSwitch.vue";
import FieldSelect from "@shared/ui/FieldSelect.vue";
import VitrinaRepo from "@shared/entities/Vitrina/VitrinaRepo";
import type { IVitrinaItem, IVitrinaSection, VitrinaPriceMode } from "@shared/types";
import { storageUrl } from "@/features/vitrina/vitrinaKit";
import { errorMessage } from "@/features/vitrina/vitrinaForm";

const MAX_PHOTOS = 6;
const PRICE_MODES: VitrinaPriceMode[] = ["fixed", "from", "negotiable", "none"];

const props = defineProps<{
   open: boolean;
   item: IVitrinaItem | null;
   sections: IVitrinaSection[];
   priceTypes: { id: number; name: string; type: string }[];
   /** Yangi mahsulot qaysi bo'limga (ro'yxatda tanlangan bo'lim) */
   defaultSectionId: number | null;
   /** Portfolio uchun yangi ish — narxsiz */
   defaultPriceMode: VitrinaPriceMode;
}>();

const emit = defineEmits<{
   (e: "close"): void;
   (e: "saved", item: IVitrinaItem): void;
   (e: "deleted", id: number): void;
}>();

type Photo = { key: string; src: string; path?: string; file?: File };

const form = reactive({
   title: "",
   description: "",
   vitrina_section_id: null as number | null,
   price_mode: "fixed" as VitrinaPriceMode,
   price_type_id: 1 as number | null,
   unit: "",
   is_available: true,
});
const priceText = ref("");
const photos = ref<Photo[]>([]);
const saving = ref(false);
const deleting = ref(false);
const error = ref("");
const confirmDelete = ref(false);

const hasPrice = computed(() => form.price_mode === "fixed" || form.price_mode === "from");

// Har ochilganda: tahrirlanayotgan mahsulotdan yoki bo'sh formadan
watch(
   () => props.open,
   (open) => {
      if (!open) return;
      const item = props.item;
      error.value = "";
      confirmDelete.value = false;
      form.title = item?.title ?? "";
      form.description = item?.description ?? "";
      form.vitrina_section_id = item ? item.vitrina_section_id : props.defaultSectionId;
      form.price_mode = item?.price_mode ?? props.defaultPriceMode;
      form.price_type_id = item?.price_type_id ?? props.priceTypes[0]?.id ?? 1;
      form.unit = item?.unit ?? "";
      form.is_available = item?.is_available ?? true;
      priceText.value = item?.price != null ? formatDigits(String(item.price)) : "";
      photos.value = (item?.images ?? []).map((path) => ({ key: path, src: storageUrl(path)!, path }));
   },
);

function formatDigits(value: string): string {
   const digits = value.replace(/\D/g, "").slice(0, 12);
   return digits ? Number(digits).toLocaleString("ru-RU") : "";
}

function addPhotos(event: Event) {
   const input = event.target as HTMLInputElement;
   for (const file of Array.from(input.files ?? []).slice(0, MAX_PHOTOS - photos.value.length)) {
      photos.value.push({ key: `${file.name}-${file.size}-${Math.random()}`, src: URL.createObjectURL(file), file });
   }
   input.value = "";
}

function removePhoto(index: number) {
   const [photo] = photos.value.splice(index, 1);
   if (photo?.file) URL.revokeObjectURL(photo.src);
}

async function save() {
   saving.value = true;
   error.value = "";
   try {
      const data = new FormData();
      data.append("title", form.title.trim());
      data.append("description", form.description.trim());
      data.append("vitrina_section_id", String(form.vitrina_section_id ?? ""));
      data.append("price_mode", form.price_mode);
      if (hasPrice.value) {
         data.append("price", priceText.value.replace(/\D/g, ""));
         data.append("price_type_id", String(form.price_type_id ?? ""));
         data.append("unit", form.unit.trim());
      }
      data.append("is_available", form.is_available ? "1" : "0");
      for (const photo of photos.value) {
         if (photo.path) data.append("keep_images[]", photo.path);
         if (photo.file) data.append("images[]", photo.file);
      }

      const { data: saved } = await VitrinaRepo.saveItem(data, props.item?.id);
      emit("saved", saved);
      emit("close");
   } catch (e) {
      error.value = errorMessage(e);
   } finally {
      saving.value = false;
   }
}

async function remove() {
   if (!props.item) return;
   if (!confirmDelete.value) {
      confirmDelete.value = true;
      return;
   }
   deleting.value = true;
   try {
      await VitrinaRepo.deleteItem(props.item.id);
      emit("deleted", props.item.id);
      emit("close");
   } catch (e) {
      error.value = errorMessage(e);
   } finally {
      deleting.value = false;
   }
}
</script>

<style scoped>
.label {
   display: block;
   margin-bottom: 0.375rem;
   color: var(--z-muted-text);
   font-size: 0.75rem;
   font-weight: 600;
}

.chip {
   height: 2.125rem;
   border: 1px solid var(--z-border);
   border-radius: 999px;
   background: var(--z-card);
   padding: 0 0.875rem;
   font-size: 0.8125rem;
   font-weight: 500;
   cursor: pointer;

   &.is-active {
      border-color: var(--z-primary);
      background: var(--z-primary);
      color: var(--z-primary-foreground);
   }
}

.thumb {
   position: relative;
   width: 4.5rem;
   height: 4.5rem;
   overflow: hidden;
   border-radius: 0.75rem;
   background: var(--z-muted);
}

.thumb-add {
   display: grid;
   place-items: center;
   border: 1.5px dashed var(--z-border);
   background: transparent;
   color: var(--z-muted-text);
   cursor: pointer;
}

.thumb-remove {
   position: absolute;
   top: 0.25rem;
   right: 0.25rem;
   display: grid;
   width: 1.25rem;
   height: 1.25rem;
   place-items: center;
   border-radius: 999px;
   background: rgb(0 0 0 / 0.6);
   color: white;
   cursor: pointer;
}
</style>
