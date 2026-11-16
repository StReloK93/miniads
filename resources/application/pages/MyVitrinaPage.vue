<template>
   <section class="grid grid-rows-[auto_1fr]">
      <aside class="pb-3 mb-3 border-b border-(--z-border) -mx-4 px-4">
         <div class="flex items-center gap-2">
            <button
               v-if="showAppBackButton"
               type="button"
               class="-ml-1.5 p-1.5 rounded-full active:bg-(--z-muted)"
               :aria-label="$t('common.back')"
               @click="goBack(router, { name: 'profile' })"
            >
               <ArrowLeft class="size-5" />
            </button>
            <h3 class="grow font-extrabold text-xl">{{ $t("vitrina.mine") }}</h3>
            <RouterLink
               v-if="vitrina"
               :to="{ name: 'vitrina', params: { slug: vitrina.slug } }"
               class="text-sm font-medium text-(--z-primary) inline-flex items-center gap-1"
            >
               <Eye class="size-4" />
               {{ $t("vitrina.view") }}
            </RouterLink>
         </div>

         <div v-if="vitrina" class="mt-3 grid grid-cols-2 gap-1 rounded-full bg-(--z-muted) p-1">
            <button type="button" class="tab" :class="{ 'is-active': tab === 'items' }" @click="tab = 'items'">
               {{ $t("vitrina.tabItems") }} <span class="opacity-60 tabular-nums">{{ vitrina.items?.length ?? 0 }}</span>
            </button>
            <button type="button" class="tab" :class="{ 'is-active': tab === 'info' }" @click="tab = 'info'">
               {{ $t("vitrina.tabInfo") }}
            </button>
         </div>

         <!-- Holati: tasdiq kutilmoqda / rad etildi -->
         <div v-if="vitrina && vitrina.status !== 'approved'" class="status mt-3" :class="`is-${vitrina.status}`">
            <component :is="vitrina.status === 'pending' ? Clock : CircleAlert" class="size-4 shrink-0" />
            <p class="text-xs leading-snug">
               {{ $t(`vitrina.status.${vitrina.status}`) }}
               <template v-if="vitrina.reject_reason">— {{ vitrina.reject_reason }}</template>
            </p>
         </div>
      </aside>

      <div class="relative">
         <div v-if="isLoading" class="absolute inset-0 flex flex-col gap-3">
            <div v-for="n in 4" :key="n" class="skeleton h-14"></div>
         </div>

         <!-- Kartalar soyasi chetda kesilmasin: aylanadigan qism sahifa chetigacha, ichida odatdagi oraliq -->
         <main v-else-if="vitrina && tab === 'items'" class="absolute inset-0 -mx-4 px-4 pt-1 pb-6 overflow-y-auto no-scrollbar">

               <!-- ================= Mahsulotlar ================= -->
               <div class="flex flex-col gap-5">
                  <!-- Statistika: vitrina tasdiqlangach mazmunli bo'ladi -->
                  <section v-if="stats && vitrina.status === 'approved'">
                     <h4 class="section-title">{{ $t("vitrina.stats.title", { n: stats.days }) }}</h4>
                     <div class="grid grid-cols-2 gap-2">
                        <div v-for="tile in statTiles" :key="tile.key" class="stat-tile">
                           <component :is="tile.icon" class="size-4 text-(--z-muted-text)" />
                           <span class="text-xl font-bold tabular-nums leading-none">{{ tile.value.toLocaleString("ru-RU") }}</span>
                           <span class="text-[11px] text-(--z-muted-text) leading-tight">{{ tile.label }}</span>
                        </div>
                     </div>
                  </section>

                  <section>
                     <h4 class="section-title">{{ $t("vitrina.sections") }}</h4>
                     <div class="flex flex-wrap gap-2">
                        <button
                           v-for="section in sections"
                           :key="section.id"
                           type="button"
                           class="chip"
                           :class="{ 'is-active': selectedSectionId === section.id }"
                           @click="selectedSectionId = selectedSectionId === section.id ? null : section.id"
                        >
                           {{ section.name }}
                           <span class="opacity-60 tabular-nums">{{ countIn(section.id) }}</span>
                        </button>
                        <button type="button" class="chip chip-add" @click="startSection(null)">
                           <Plus class="size-3.5" /> {{ $t("vitrina.addSection") }}
                        </button>
                     </div>

                     <!-- Tanlangan bo'lim: nomini o'zgartirish yoki o'chirish -->
                     <div v-if="selectedSection && sectionDraft === null" class="mt-2 flex items-center gap-2 text-xs">
                        <button type="button" class="link" @click="startSection(selectedSection)">
                           <Pencil class="size-3.5" /> {{ $t("vitrina.renameSection") }}
                        </button>
                        <button type="button" class="link text-(--z-danger)!" @click="removeSection(selectedSection)">
                           <Trash2 class="size-3.5" /> {{ $t("vitrina.deleteSection") }}
                        </button>
                     </div>

                     <form v-if="sectionDraft !== null" class="mt-2.5 flex gap-2" @submit.prevent="saveSection">
                        <input
                           ref="sectionInput"
                           v-model="sectionDraft"
                           class="field"
                           maxlength="40"
                           required
                           :placeholder="$t('vitrina.sectionHint')"
                        />
                        <BaseButton type="submit" :loading="savingSection">{{ $t("common.save") }}</BaseButton>
                        <BaseButton type="button" severity="secondary" icon-only @click="sectionDraft = null">
                           <template #icon><X class="size-4" /></template>
                        </BaseButton>
                     </form>
                  </section>

                  <section>
                     <div class="flex items-center justify-between mb-2">
                        <h4 class="section-title mb-0!">
                           {{ selectedSection ? selectedSection.name : $t("vitrina.allItems") }}
                        </h4>
                        <div class="flex items-center gap-1.5">
                           <BaseButton
                              v-if="visibleItems.length > 1"
                              size="xs"
                              :severity="reordering ? 'primary' : 'secondary'"
                              :loading="savingOrder"
                              @click="toggleReorder"
                           >
                              <template #icon>
                                 <component :is="reordering ? Check : ArrowUpDown" class="size-3.5" />
                              </template>
                              {{ reordering ? $t("vitrina.order.done") : $t("vitrina.order.button") }}
                           </BaseButton>
                           <BaseButton v-if="!reordering" size="xs" @click="openItem(null)">
                              <template #icon><Plus class="size-3.5" /></template>
                              {{ $t("vitrina.addItem") }}
                           </BaseButton>
                        </div>
                     </div>
                     <p v-if="reordering" class="mb-2 px-1 text-xs text-(--z-muted-text)">{{ $t("vitrina.order.hint") }}</p>

                     <ul v-if="visibleItems.length" class="card divide-y divide-(--z-border)">
                        <li v-for="(item, index) in visibleItems" :key="item.id" class="relative">
                           <button type="button" class="item-row" :class="{ 'pr-24!': reordering }" @click="reordering || openItem(item)">
                              <img v-if="item.images?.length" :src="storageUrl(item.images[0])!" alt="" class="size-11 rounded-lg object-cover shrink-0" />
                              <span v-else class="size-11 rounded-lg shrink-0 grid place-items-center bg-(--z-muted) text-(--z-muted-text)">
                                 <ImageOff class="size-4" />
                              </span>
                              <span class="min-w-0 grow">
                                 <span class="block text-sm font-medium truncate" :class="{ 'opacity-50': !item.is_available }">{{ item.title }}</span>
                                 <span class="block text-xs text-(--z-muted-text) truncate">
                                    {{ itemPrice(item) || $t("vitrina.priceModes.none") }}
                                    <template v-if="!item.is_available"> · {{ $t("vitrina.unavailable") }}</template>
                                 </span>
                              </span>
                              <ChevronRight v-if="!reordering" class="size-4 shrink-0 text-(--z-muted-text)" />
                           </button>
                           <!-- Tartiblash: tepaga / pastga (Telegram ichida sudrab tashlash aylantirish bilan to'qnashadi) -->
                           <div v-if="reordering" class="absolute inset-y-0 right-3 flex items-center gap-1">
                              <button
                                 type="button"
                                 class="move"
                                 :disabled="index === 0"
                                 :aria-label="$t('vitrina.order.up')"
                                 @click="move(item, -1)"
                              >
                                 <ChevronUp class="size-4" />
                              </button>
                              <button
                                 type="button"
                                 class="move"
                                 :disabled="index === visibleItems.length - 1"
                                 :aria-label="$t('vitrina.order.down')"
                                 @click="move(item, 1)"
                              >
                                 <ChevronDown class="size-4" />
                              </button>
                           </div>
                        </li>
                     </ul>
                     <button v-else type="button" class="empty" @click="openItem(null)">
                        <PackagePlus class="size-6 mb-1.5" />
                        <span class="text-sm font-medium">{{ $t("vitrina.addFirstItem") }}</span>
                        <span class="text-xs text-(--z-muted-text)">{{ $t(`vitrina.addFirstHint.${vitrina.type}`) }}</span>
                     </button>
                  </section>
               </div>

         </main>

         <!-- ================= Ma'lumotlar: e'lon formasidagi BaseForm va maydonlar ================= -->
         <BaseForm
            v-else
            :key="infoFormKey"
            class="absolute inset-0"
            :input-configs="infoInputs"
            :submit="saveInfo"
            :submit-label="vitrina ? $t('common.save') : $t('vitrina.create')"
         >
            <template #inputs>
               <div v-if="!vitrina" class="intro mb-4">
                  <Sparkles class="size-5 shrink-0 text-(--z-primary)" />
                  <p class="text-xs leading-snug">{{ $t("vitrina.intro") }}</p>
               </div>
               <p v-if="infoSaved" class="mb-3 text-center text-xs font-medium text-emerald-600">{{ $t("vitrina.saved") }}</p>

               <div class="mb-3">
                  <p class="label">{{ $t("vitrina.form.type") }}</p>
                  <div class="grid grid-cols-1 gap-2">
                     <button
                        v-for="key in VITRINA_TYPE_KEYS"
                        :key="key"
                        type="button"
                        class="type-option"
                        :class="{ 'is-active': infoType === key }"
                        :style="{ '--tone': VITRINA_TYPES[key].tone }"
                        @click="infoType = key"
                     >
                        <span class="type-icon"><component :is="VITRINA_TYPES[key].icon" class="size-4.5" /></span>
                        <span class="min-w-0 text-left">
                           <span class="block text-sm font-semibold">{{ $t(`vitrina.types.${key}`) }}</span>
                           <span class="block text-xs text-(--z-muted-text) leading-snug">{{ $t(`vitrina.typeHints.${key}`) }}</span>
                        </span>
                     </button>
                  </div>
               </div>

               <!-- Logo va muqova -->
               <div class="mb-3 grid grid-cols-[auto_1fr] gap-3">
                  <div>
                     <p class="label">{{ $t("vitrina.form.logo") }}</p>
                     <label class="logo-pick">
                        <img v-if="logoPreview" :src="logoPreview" alt="" class="size-full object-cover" />
                        <Camera v-else class="size-5" />
                        <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="pick($event, 'logo')" />
                     </label>
                  </div>
                  <div>
                     <p class="label">{{ $t("vitrina.form.cover") }}</p>
                     <label class="cover-pick">
                        <img v-if="coverPreview" :src="coverPreview" alt="" class="absolute inset-0 size-full object-cover" />
                        <ImagePlus v-else class="size-5" />
                        <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="pick($event, 'cover')" />
                     </label>
                  </div>
               </div>
            </template>
         </BaseForm>
      </div>

      <VitrinaItemForm
         :open="itemFormOpen"
         :item="editingItem"
         :sections="sections"
         :price-types="priceTypes"
         :default-section-id="selectedSectionId"
         :default-price-mode="vitrina?.type === 'portfolio' ? 'none' : 'fixed'"
         @close="itemFormOpen = false"
         @saved="onItemSaved"
         @deleted="onItemDeleted"
         @publish="publishItem"
      />
   </section>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import {
   ArrowLeft,
   ArrowUpDown,
   Camera,
   Check,
   ChevronDown,
   ChevronRight,
   ChevronUp,
   Megaphone,
   MessageCircle,
   Phone,
   CircleAlert,
   Clock,
   Eye,
   ImageOff,
   ImagePlus,
   PackagePlus,
   Pencil,
   Plus,
   Sparkles,
   Trash2,
   X,
} from "lucide-vue-next";
import BaseButton from "@shared/ui/BaseButton.vue";
import BaseForm from "@shared/ui/BaseForm.vue";
import { Inputs } from "@shared/ui/inputs";
import { z } from "zod";
import VitrinaRepo from "@shared/entities/Vitrina/VitrinaRepo";
import { getPriceTypes } from "@shared/entities/Product/ProductInputs";
import { useCity } from "@shared/entities/District/useCity";
import { useAuth } from "@shared/store/useAuth";
import type { IVitrina, IVitrinaItem, IVitrinaSection, IVitrinaStats, InputConfig, VitrinaType } from "@shared/types";
import { AI_DRAFT_MAX_IMAGES, useAiDraft } from "@/features/product/aiDraft";
import { t } from "@shared/i18n";
import { goBack, showAppBackButton } from "@/modules/navigation";
import VitrinaItemForm from "@/features/vitrina/VitrinaItemForm.vue";
import { VITRINA_TYPES, VITRINA_TYPE_KEYS, itemPrice, storageUrl } from "@/features/vitrina/vitrinaKit";
import { errorMessage } from "@/features/vitrina/vitrinaForm";

const router = useRouter();
const authStore = useAuth();
const cityStore = useCity();

const vitrina = ref<IVitrina | null>(null);
const isLoading = ref(true);
const tab = ref<"items" | "info">("items");
const priceTypes = ref<{ id: number; name: string; type: string }[]>([]);

/* ================= Ma'lumotlar formasi ================= */
const infoType = ref<VitrinaType>("shop");
const infoFormKey = ref(0);
const files = reactive<{ logo: File | null; cover: File | null }>({ logo: null, cover: null });
const previews = reactive<{ logo: string | null; cover: string | null }>({ logo: null, cover: null });
const infoSaved = ref(false);

const logoPreview = computed(() => previews.logo ?? storageUrl(vitrina.value?.logo));
const coverPreview = computed(() => previews.cover ?? storageUrl(vitrina.value?.cover));

const optionalText = (max: number) => z.string().trim().max(max).optional().nullable();

/**
 * Maydonlar e'lon formasidagi komponentlar bilan (FieldText, FieldTextarea, FieldSelect, FieldMask...).
 * Yangi vitrina: foydalanuvchi tanlagan shahar, profildagi raqam va Telegram oldindan qo'yiladi.
 */
const infoInputs = computed<InputConfig[]>(() => {
   const source = vitrina.value;
   const user = authStore.user;
   const handle = (value?: string | null) => (value ? `@${value}` : "");

   return [
      {
         component: Inputs.FieldText,
         name: "name",
         required: true,
         value: source?.name ?? "",
         props: { title: t("vitrina.form.name"), placeholder: t("vitrina.form.nameHint"), maxlength: 60 },
         schema: z.string({ message: t("form.required") }).trim().min(2, t("form.minChars", { n: 2 })).max(60),
         class: ["mb-3"],
      },
      {
         component: Inputs.FieldTextarea,
         name: "description",
         optional: true,
         value: source?.description ?? "",
         props: { title: t("vitrina.form.about"), placeholder: t("vitrina.form.aboutHint"), rows: 3, maxHeight: 200, maxlength: 1000 },
         schema: optionalText(1000),
         class: ["mb-3"],
      },
      {
         component: Inputs.FieldSelect,
         name: "district_id",
         required: true,
         value: source ? (source.district_id ?? 0) : (user?.active_district_id ?? 0),
         props: { title: t("district.city"), options: cityStore.cities ?? [], value: "name" },
         schema: z.coerce.number().min(0),
         class: ["mb-3"],
      },
      {
         component: Inputs.FieldText,
         name: "address",
         optional: true,
         value: source?.address ?? "",
         props: { title: t("vitrina.form.address"), placeholder: t("vitrina.form.addressHint"), maxlength: 150 },
         schema: optionalText(150),
         class: ["mb-3"],
      },
      {
         component: Inputs.FieldMask,
         name: "phone",
         optional: true,
         value: source ? (source.phone ?? "") : (user?.phone ?? ""),
         props: { title: t("profile.phone"), mask: "99-999-99-99", placeholder: "93-123-45-67", inputmode: "tel" },
         schema: optionalText(30),
         class: ["mb-3"],
      },
      {
         component: Inputs.FieldTimeRange,
         name: "work_hours",
         optional: true,
         value: source?.work_hours ?? "",
         props: { title: t("vitrina.form.hours"), fromLabel: t("vitrina.form.hoursFrom"), toLabel: t("vitrina.form.hoursTo") },
         schema: optionalText(60),
         class: ["mb-3"],
      },
      {
         component: Inputs.FieldText,
         name: "telegram",
         optional: true,
         value: source ? handle(source.telegram) : handle(user?.username),
         props: { title: "Telegram", placeholder: "@username", maxlength: 40 },
         schema: optionalText(40),
         class: ["mb-3"],
      },
      {
         component: Inputs.FieldText,
         name: "instagram",
         optional: true,
         value: handle(source?.instagram),
         props: { title: "Instagram", placeholder: "@instagram", maxlength: 40 },
         schema: optionalText(40),
      },
   ];
});

function pick(event: Event, field: "logo" | "cover") {
   const input = event.target as HTMLInputElement;
   const file = input.files?.[0];
   input.value = "";
   if (!file) return;
   if (previews[field]) URL.revokeObjectURL(previews[field]!);
   files[field] = file;
   previews[field] = URL.createObjectURL(file);
}

/** BaseForm chaqiradi: xato bo'lsa o'zi ko'rsatadi (shuning uchun bu yerda ushlanmaydi) */
async function saveInfo(values: unknown) {

   infoSaved.value = false;
   const data = new FormData();
   data.append("type", infoType.value);
   for (const [key, value] of Object.entries(values as Record<string, unknown>)) {
      data.append(key, value === null || value === undefined ? "" : String(value));
   }
   if (files.logo) data.append("logo", files.logo);
   if (files.cover) data.append("cover", files.cover);

   const isNew = !vitrina.value;
   const { data: saved } = await VitrinaRepo.save(data);
   vitrina.value = saved;
   files.logo = files.cover = null;
   infoSaved.value = true;
   // Yaratilgach — darhol mahsulot qo'shishga
   if (isNew) {
      tab.value = "items";
      loadStats();
   }
}

/* ================= Bo'limlar ================= */
const sections = computed<IVitrinaSection[]>(() => vitrina.value?.sections ?? []);
const selectedSectionId = ref<number | null>(null);
const selectedSection = computed(() => sections.value.find((section) => section.id === selectedSectionId.value) ?? null);
/** null — forma yopiq; satr — yangi yoki tahrirlanayotgan bo'lim nomi */
const sectionDraft = ref<string | null>(null);
const editingSection = ref<IVitrinaSection | null>(null);
const savingSection = ref(false);
const sectionInput = ref<HTMLInputElement | null>(null);

function startSection(section: IVitrinaSection | null) {
   editingSection.value = section;
   sectionDraft.value = section?.name ?? "";
   nextTick(() => sectionInput.value?.focus());
}

async function saveSection() {
   if (!vitrina.value || !sectionDraft.value?.trim()) return;
   savingSection.value = true;
   try {
      const name = sectionDraft.value.trim();
      if (editingSection.value) {
         const { data } = await VitrinaRepo.updateSection(editingSection.value.id, name);
         editingSection.value.name = data.name;
      } else {
         const { data } = await VitrinaRepo.storeSection(name);
         vitrina.value.sections = [...sections.value, data];
         selectedSectionId.value = data.id;
      }
      sectionDraft.value = null;
   } catch (e) {
      alert(errorMessage(e));
   } finally {
      savingSection.value = false;
   }
}

async function removeSection(section: IVitrinaSection) {
   if (!vitrina.value) return;
   try {
      await VitrinaRepo.deleteSection(section.id);
      vitrina.value.sections = sections.value.filter((item) => item.id !== section.id);
      // Mahsulotlari o'chmaydi — "Boshqa" ga tushadi (serverdagidek)
      for (const item of vitrina.value.items ?? []) {
         if (item.vitrina_section_id === section.id) item.vitrina_section_id = null;
      }
      selectedSectionId.value = null;
   } catch (e) {
      alert(errorMessage(e));
   }
}

/* ================= Mahsulotlar ================= */
const itemFormOpen = ref(false);
const editingItem = ref<IVitrinaItem | null>(null);

const visibleItems = computed(() => {
   const items = vitrina.value?.items ?? [];
   return selectedSectionId.value ? items.filter((item) => item.vitrina_section_id === selectedSectionId.value) : items;
});

function countIn(sectionId: number) {
   return (vitrina.value?.items ?? []).filter((item) => item.vitrina_section_id === sectionId).length;
}

function openItem(item: IVitrinaItem | null) {
   editingItem.value = item;
   itemFormOpen.value = true;
}

function onItemSaved(saved: IVitrinaItem) {
   if (!vitrina.value) return;
   const items = vitrina.value.items ?? [];
   const index = items.findIndex((item) => item.id === saved.id);
   vitrina.value.items = index === -1 ? [...items, saved] : items.map((item) => (item.id === saved.id ? saved : item));
}

function onItemDeleted(id: number) {
   if (!vitrina.value) return;
   vitrina.value.items = (vitrina.value.items ?? []).filter((item) => item.id !== id);
}

/* ================= Tartiblash ================= */
const reordering = ref(false);
const savingOrder = ref(false);

/** Ko'rinib turgan ro'yxatda qo'shnisi bilan almashadi (bo'lim tanlangan bo'lsa — shu bo'lim ichida) */
function move(item: IVitrinaItem, direction: -1 | 1) {
   if (!vitrina.value?.items) return;
   const neighbour = visibleItems.value[visibleItems.value.indexOf(item) + direction];
   if (!neighbour) return;
   const items = [...vitrina.value.items];
   const a = items.indexOf(item);
   const b = items.indexOf(neighbour);
   [items[a], items[b]] = [items[b], items[a]];
   vitrina.value.items = items;
}

async function toggleReorder() {
   if (!reordering.value) {
      reordering.value = true;
      return;
   }
   savingOrder.value = true;
   try {
      await VitrinaRepo.orderItems((vitrina.value?.items ?? []).map((item) => item.id));
      reordering.value = false;
   } catch (e) {
      alert(errorMessage(e));
   } finally {
      savingOrder.value = false;
   }
}

/* ================= Statistika ================= */
const stats = ref<IVitrinaStats | null>(null);
const statTiles = computed(() => {
   const s = stats.value;
   if (!s) return [];
   return [
      { key: "views", icon: Eye, value: s.views, label: t("vitrina.stats.views") },
      { key: "phone", icon: Phone, value: s.phone, label: t("vitrina.stats.phone") },
      { key: "telegram", icon: MessageCircle, value: s.telegram, label: t("vitrina.stats.telegram") },
      { key: "ads", icon: Megaphone, value: s.ads_views, label: t("vitrina.stats.ads", { n: s.ads }, s.ads) },
   ];
});

async function loadStats() {
   try {
      stats.value = (await VitrinaRepo.stats()).data;
   } catch {
      // Statistika ixtiyoriy: yuklanmasa blok ko'rinmaydi
   }
}

/* ================= Mahsulotdan e'lon ================= */
const aiDraftStore = useAiDraft();

/**
 * Vitrina mahsulotini e'lon formasiga o'tkazadi: nom, tavsif, narx, telefon, shahar va rasmlar.
 * Bo'limni foydalanuvchi tanlaydi (vitrina mahsulotida e'lon bo'limi yo'q); e'lon vitrinaga bog'lanadi.
 */
async function publishItem(item: IVitrinaItem) {
   if (!vitrina.value) return;
   const images = await Promise.all(
      (item.images ?? []).slice(0, AI_DRAFT_MAX_IMAGES).map(async (path, index) => {
         try {
            const blob = await (await fetch(storageUrl(path)!)).blob();
            return new File([blob], `vitrina-${item.id}-${index}.webp`, { type: blob.type || "image/webp" });
         } catch {
            return null;
         }
      }),
   );
   const hasPrice = item.price_mode === "fixed" || item.price_mode === "from";

   aiDraftStore.set(
      {
         draft: {
            category_id: 0,
            title: item.title,
            description: item.description ?? "",
            price: hasPrice ? item.price : null,
            price_type_id: item.price_type_id ?? 1,
            phone: vitrina.value.phone ?? null,
            district_id: vitrina.value.district_id,
            parameters: {},
         },
         missing: [],
         duplicate: null,
         remaining: null,
      },
      images.filter((file): file is File => file !== null),
      vitrina.value.id,
   );
   itemFormOpen.value = false;
   router.push({ name: "create-select-category" });
}

onMounted(async () => {
   try {
      const [{ data }, types] = await Promise.all([
         VitrinaRepo.mine(),
         getPriceTypes(),
         cityStore.cities?.length ? Promise.resolve() : cityStore.getCities(),
      ]);
      vitrina.value = data.vitrina ?? null;
      priceTypes.value = (types ?? []) as typeof priceTypes.value;
      infoType.value = vitrina.value?.type ?? "shop";
      if (!vitrina.value) tab.value = "info";
      else loadStats();
   } catch (error) {
      alert(errorMessage(error));
      tab.value = "info";
   } finally {
      isLoading.value = false;
   }
});
</script>

<style scoped>
.tab {
   height: 2rem;
   border-radius: 999px;
   font-size: 0.8125rem;
   font-weight: 600;
   cursor: pointer;

   &.is-active {
      background: var(--z-card);
      box-shadow: 0 1px 3px rgb(0 0 0 / 0.08);
   }
}

.stat-tile {
   display: flex;
   flex-direction: column;
   gap: 0.375rem;
   padding: 0.75rem;
   border: 1px solid var(--z-glass-border);
   border-radius: var(--z-rounded);
   background: var(--z-card);
}

.move {
   display: grid;
   place-items: center;
   width: 2.25rem;
   height: 2.25rem;
   border: 1px solid var(--z-border);
   border-radius: 999px;
   background: var(--z-background);
   cursor: pointer;

   &:disabled {
      opacity: 0.35;
      cursor: default;
   }
}

.section-title {
   margin: 0 0 0.5rem 0.25rem;
   color: var(--z-muted-text);
   font-size: 0.6875rem;
   font-weight: 700;
   letter-spacing: 0.08em;
   text-transform: uppercase;
}

/* BaseFormFields sarlavhasi bilan bir xil (tur, logo, muqova — forma maydonlari qatorida) */
.label {
   display: block;
   margin-bottom: 0.375rem;
   padding: 0 0.125rem;
   color: var(--z-foreground);
   font-size: 0.75rem;
   font-weight: 600;
   letter-spacing: 0.025em;
}

.card {
   overflow: hidden;
   border: 1px solid var(--z-glass-border);
   border-radius: var(--z-rounded);
   background: var(--z-card);
   box-shadow: var(--z-glass-edge, none);
}

.chip {
   display: inline-flex;
   height: 2.125rem;
   align-items: center;
   gap: 0.375rem;
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

.chip-add {
   border-style: dashed;
   color: var(--z-primary);
}

.link {
   display: inline-flex;
   align-items: center;
   gap: 0.25rem;
   padding: 0.25rem 0.5rem;
   color: var(--z-muted-text);
   font-weight: 500;
   cursor: pointer;
}

.item-row {
   display: flex;
   width: 100%;
   align-items: center;
   gap: 0.75rem;
   padding: 0.625rem 0.75rem;
   text-align: left;
   cursor: pointer;

   &:active {
      background: var(--z-muted);
   }
}

.empty {
   display: flex;
   width: 100%;
   flex-direction: column;
   align-items: center;
   border: 1.5px dashed var(--z-border);
   border-radius: var(--z-rounded);
   padding: 1.75rem 1rem;
   color: var(--z-foreground);
   text-align: center;
   cursor: pointer;
}

.status,
.intro {
   display: flex;
   align-items: center;
   gap: 0.625rem;
   border: 1px solid var(--z-border);
   border-radius: var(--z-rounded);
   background: var(--z-card);
   padding: 0.75rem;
}

.status.is-pending {
   border-color: rgb(245 158 11 / 0.5);
   color: rgb(180 83 9);
}

.status.is-rejected {
   border-color: color-mix(in srgb, var(--z-danger) 60%, transparent);
   color: var(--z-danger);
}

:global(html.dark) .status.is-pending {
   color: rgb(251 191 36);
}

.type-option {
   display: flex;
   align-items: center;
   gap: 0.75rem;
   border: 1px solid var(--z-border);
   border-radius: var(--z-rounded);
   background: var(--z-card);
   padding: 0.625rem 0.75rem;
   cursor: pointer;

   &.is-active {
      border-color: rgb(var(--tone));
      box-shadow: 0 0 0 1px rgb(var(--tone));
   }
}

.type-icon {
   display: grid;
   width: 2.25rem;
   height: 2.25rem;
   flex-shrink: 0;
   place-items: center;
   border-radius: 999px;
   background: rgb(var(--tone) / 0.14);
   color: rgb(var(--tone));
}

.logo-pick,
.cover-pick {
   position: relative;
   display: grid;
   height: 4.5rem;
   place-items: center;
   overflow: hidden;
   border: 1.5px dashed var(--z-border);
   background: var(--z-card);
   color: var(--z-muted-text);
   cursor: pointer;
}

.logo-pick {
   width: 4.5rem;
   border-radius: 999px;
}

.cover-pick {
   border-radius: var(--z-rounded);
}
</style>
