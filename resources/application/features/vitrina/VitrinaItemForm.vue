<template>
   <BaseDrawer :open="open" :title="item ? $t('vitrina.form.editItem') : $t('vitrina.form.newItem')" @close="emit('close')">
      <!-- Maydonlar e'lon formasidagi komponentlar bilan (BaseFormFields): ko'rinish va tekshiruv bir xil -->
      <Form
         id="vitrina-item-form"
         :key="formKey"
         :initial-values="initialValues"
         :validation-schema="validationSchema"
         class="flex flex-col"
         @submit="save"
      >
         <p v-if="error" class="mb-3 text-sm text-(--z-danger)">{{ error }}</p>

         <!-- Rasmlar: eskilari (× bilan olib tashlanadi) va yangilari; ko'pi bilan 6 ta -->
         <div class="mb-3">
            <p class="label">{{ $t("vitrina.form.photos") }}</p>
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

         <BaseFormFields :input-configs="mainInputs" />

         <div class="mt-3 mb-3">
            <p class="label">{{ $t("vitrina.form.price") }}</p>
            <div class="flex flex-wrap gap-2">
               <button
                  v-for="mode in PRICE_MODES"
                  :key="mode"
                  type="button"
                  class="chip"
                  :class="{ 'is-active': priceMode === mode }"
                  @click="priceMode = mode"
               >
                  {{ $t(`vitrina.priceModes.${mode}`) }}
               </button>
            </div>
         </div>

         <BaseFormFields v-if="hasPrice" :input-configs="priceInputs" />

         <BaseSwitch v-model="isAvailable" class="mt-1">
            <span class="block text-sm font-medium">{{ $t("vitrina.form.available") }}</span>
            <span class="block text-xs text-(--z-muted-text)">{{ $t("vitrina.form.availableHint") }}</span>
         </BaseSwitch>
      </Form>

      <template #footer>
         <!-- Saqlangan mahsulotni bir bosishda e'lon qilish: forma shu ma'lumotlar bilan to'ldiriladi -->
         <button
            v-if="item"
            type="button"
            class="mb-3 w-full flex items-center gap-2.5 rounded-xl border border-(--z-border) px-3 py-2.5 text-left active:bg-(--z-muted)"
            @click="emit('publish', item)"
         >
            <Megaphone class="size-4.5 shrink-0 text-(--z-primary)" />
            <span class="min-w-0 grow">
               <span class="block text-sm font-semibold">{{ $t("vitrina.publish.button") }}</span>
               <span class="block text-xs text-(--z-muted-text) leading-snug">{{ $t("vitrina.publish.hint") }}</span>
            </span>
         </button>
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
import { computed, ref, watch } from "vue";
import { Form } from "vee-validate";
import { toTypedSchema } from "@vee-validate/zod";
import { z } from "zod";
import { ImagePlus, Megaphone, Trash2, X } from "lucide-vue-next";
import BaseDrawer from "@shared/ui/BaseDrawer.vue";
import BaseButton from "@shared/ui/BaseButton.vue";
import BaseSwitch from "@shared/ui/BaseSwitch.vue";
import BaseFormFields from "@shared/ui/BaseFormFields.vue";
import { Inputs } from "@shared/ui/inputs";
import { t } from "@shared/i18n";
import VitrinaRepo from "@shared/entities/Vitrina/VitrinaRepo";
import type { InputConfig, IVitrinaItem, IVitrinaSection, VitrinaPriceMode } from "@shared/types";
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
   (e: "publish", item: IVitrinaItem): void;
}>();

type Photo = { key: string; src: string; path?: string; file?: File };

// Formadan tashqaridagi qismlar: narx turi tugmalari, "Hozir bor" va rasmlar
const priceMode = ref<VitrinaPriceMode>("fixed");
const isAvailable = ref(true);
const photos = ref<Photo[]>([]);
const saving = ref(false);
const deleting = ref(false);
const error = ref("");
const confirmDelete = ref(false);
/** Har ochilishda forma yangidan (boshlang'ich qiymatlar shu mahsulotdan) */
const formKey = ref(0);

const hasPrice = computed(() => priceMode.value === "fixed" || priceMode.value === "from");

const mainInputs = computed<InputConfig[]>(() => [
   {
      component: Inputs.FieldText,
      name: "title",
      required: true,
      props: { title: t("vitrina.form.itemTitle"), placeholder: t("vitrina.form.itemTitleHint"), maxlength: 100 },
      schema: z.string({ message: t("form.required") }).trim().min(1, t("form.required")).max(100),
      class: ["mb-3"],
   },
   ...(props.sections.length
      ? [
           {
              component: Inputs.FieldSelect,
              name: "vitrina_section_id",
              props: {
                 title: t("vitrina.form.section"),
                 options: [{ id: 0, name: t("vitrina.otherSection") }, ...props.sections],
                 value: "name",
              },
              schema: z.coerce.number().min(0),
              class: ["mb-3"],
           },
        ]
      : []),
   {
      component: Inputs.FieldTextarea,
      name: "description",
      optional: true,
      props: { title: t("common.description"), rows: 3, maxHeight: 200, maxlength: 2000 },
      schema: z.string().trim().max(2000).optional().nullable(),
   },
]);

// Narx va valyuta — e'lon formasidagidek: valyuta narx maydoni ichida (teleport)
const priceInputs = computed<InputConfig[]>(() => [
   {
      component: Inputs.FieldNumber,
      name: "price",
      required: true,
      props: { placeholder: t("vitrina.form.priceHint"), max: 999999999999, class: "pr-28!" },
      schema: z.coerce.number({ message: t("form.required") }).min(1, t("form.priceMin")),
      class: ["mb-3"],
      teleport_parent_class: "parent_vitrina_price",
   },
   {
      component: Inputs.FieldSelect,
      name: "price_type_id",
      props: { options: props.priceTypes, value: "type", selectIcon: false, variant: "addon" },
      schema: z.coerce.number(),
      class: ["absolute", "inset-y-0", "right-0", "flex", "items-center", "pr-2"],
      teleport_child_class: "parent_vitrina_price",
   },
   {
      component: Inputs.FieldText,
      name: "unit",
      optional: true,
      props: { title: t("vitrina.form.unit"), placeholder: t("vitrina.form.unitHint"), maxlength: 30 },
      schema: z.string().trim().max(30).optional().nullable(),
      class: ["mb-3"],
   },
]);

// Faqat ko'rinib turgan maydonlar tekshiriladi ("Kelishiladi"da narx so'ralmaydi)
const validationSchema = computed(() =>
   toTypedSchema(
      z.object(
         Object.fromEntries(
            [...mainInputs.value, ...(hasPrice.value ? priceInputs.value : [])].map((input) => [input.name, input.schema]),
         ),
      ),
   ),
);

const initialValues = ref<Record<string, unknown>>({});

// Har ochilganda: tahrirlanayotgan mahsulotdan yoki bo'sh formadan
watch(
   () => props.open,
   (open) => {
      if (!open) return;
      const item = props.item;
      error.value = "";
      confirmDelete.value = false;
      priceMode.value = item?.price_mode ?? props.defaultPriceMode;
      isAvailable.value = item?.is_available ?? true;
      photos.value = (item?.images ?? []).map((path) => ({ key: path, src: storageUrl(path)!, path }));
      initialValues.value = {
         title: item?.title ?? "",
         vitrina_section_id: (item ? item.vitrina_section_id : props.defaultSectionId) ?? 0,
         description: item?.description ?? "",
         price: item?.price ?? undefined,
         price_type_id: item?.price_type_id ?? props.priceTypes[0]?.id ?? 1,
         unit: item?.unit ?? "",
      };
      formKey.value++;
   },
);

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

async function save(formValues: Record<string, unknown>) {
   saving.value = true;
   error.value = "";
   try {
      const values = formValues as Record<string, string | number | null | undefined>;
      const text = (value: unknown) => String(value ?? "").trim();
      const data = new FormData();
      data.append("title", text(values.title));
      data.append("description", text(values.description));
      data.append("vitrina_section_id", values.vitrina_section_id ? String(values.vitrina_section_id) : "");
      data.append("price_mode", priceMode.value);
      if (hasPrice.value) {
         data.append("price", text(values.price));
         data.append("price_type_id", text(values.price_type_id));
         data.append("unit", text(values.unit));
      }
      data.append("is_available", isAvailable.value ? "1" : "0");
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
