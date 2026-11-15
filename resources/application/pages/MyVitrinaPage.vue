<template>
   <section class="grid grid-rows-[auto_1fr]">
      <aside class="pb-3 mb-3 border-b border-(--z-border) -mx-4 px-4">
         <div class="flex items-center gap-2">
            <button type="button" class="-ml-1.5 p-1.5 rounded-full active:bg-(--z-muted)" :aria-label="$t('common.back')" @click="goBack(router, { name: 'profile' })">
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
      </aside>

      <div class="relative">
         <main class="absolute inset-0 overflow-y-auto no-scrollbar pb-6">
            <div v-if="isLoading" class="flex flex-col gap-3">
               <div v-for="n in 4" :key="n" class="skeleton h-14"></div>
            </div>

            <template v-else>
               <!-- Holati: tasdiq kutilmoqda / rad etildi -->
               <div v-if="vitrina && vitrina.status !== 'approved'" class="status mb-4" :class="`is-${vitrina.status}`">
                  <component :is="vitrina.status === 'pending' ? Clock : CircleAlert" class="size-4 shrink-0" />
                  <p class="text-xs leading-snug">
                     {{ $t(`vitrina.status.${vitrina.status}`) }}
                     <template v-if="vitrina.reject_reason">— {{ vitrina.reject_reason }}</template>
                  </p>
               </div>

               <!-- ================= Mahsulotlar ================= -->
               <div v-if="vitrina && tab === 'items'" class="flex flex-col gap-5">
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
                        <BaseButton size="xs" @click="openItem(null)">
                           <template #icon><Plus class="size-3.5" /></template>
                           {{ $t("vitrina.addItem") }}
                        </BaseButton>
                     </div>

                     <ul v-if="visibleItems.length" class="card divide-y divide-(--z-border)">
                        <li v-for="item in visibleItems" :key="item.id">
                           <button type="button" class="item-row" @click="openItem(item)">
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
                              <ChevronRight class="size-4 shrink-0 text-(--z-muted-text)" />
                           </button>
                        </li>
                     </ul>
                     <button v-else type="button" class="empty" @click="openItem(null)">
                        <PackagePlus class="size-6 mb-1.5" />
                        <span class="text-sm font-medium">{{ $t("vitrina.addFirstItem") }}</span>
                        <span class="text-xs text-(--z-muted-text)">{{ $t(`vitrina.addFirstHint.${vitrina.type}`) }}</span>
                     </button>
                  </section>
               </div>

               <!-- ================= Ma'lumotlar ================= -->
               <form v-else class="flex flex-col gap-4" @submit.prevent="saveInfo">
                  <div v-if="!vitrina" class="intro">
                     <Sparkles class="size-5 shrink-0 text-(--z-primary)" />
                     <p class="text-xs leading-snug">{{ $t("vitrina.intro") }}</p>
                  </div>

                  <p v-if="infoError" class="text-sm text-(--z-danger)">{{ infoError }}</p>

                  <div>
                     <label class="label">{{ $t("vitrina.form.type") }}</label>
                     <div class="grid grid-cols-1 gap-2">
                        <button
                           v-for="key in VITRINA_TYPE_KEYS"
                           :key="key"
                           type="button"
                           class="type-option"
                           :class="{ 'is-active': info.type === key }"
                           :style="{ '--tone': VITRINA_TYPES[key].tone }"
                           @click="info.type = key"
                        >
                           <span class="type-icon"><component :is="VITRINA_TYPES[key].icon" class="size-4.5" /></span>
                           <span class="min-w-0 text-left">
                              <span class="block text-sm font-semibold">{{ $t(`vitrina.types.${key}`) }}</span>
                              <span class="block text-xs text-(--z-muted-text) leading-snug">{{ $t(`vitrina.typeHints.${key}`) }}</span>
                           </span>
                        </button>
                     </div>
                  </div>

                  <div>
                     <label class="label" for="v-name">{{ $t("vitrina.form.name") }}</label>
                     <input id="v-name" v-model="info.name" class="field" maxlength="60" required :placeholder="$t('vitrina.form.nameHint')" />
                  </div>

                  <!-- Logo va muqova -->
                  <div class="grid grid-cols-[auto_1fr] gap-3">
                     <div>
                        <label class="label">{{ $t("vitrina.form.logo") }}</label>
                        <label class="logo-pick">
                           <img v-if="logoPreview" :src="logoPreview" alt="" class="size-full object-cover" />
                           <Camera v-else class="size-5" />
                           <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="pick($event, 'logo')" />
                        </label>
                     </div>
                     <div>
                        <label class="label">{{ $t("vitrina.form.cover") }}</label>
                        <label class="cover-pick">
                           <img v-if="coverPreview" :src="coverPreview" alt="" class="absolute inset-0 size-full object-cover" />
                           <ImagePlus v-else class="size-5" />
                           <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="pick($event, 'cover')" />
                        </label>
                     </div>
                  </div>

                  <div>
                     <label class="label" for="v-desc">{{ $t("vitrina.form.about") }}</label>
                     <textarea id="v-desc" v-model="info.description" class="field py-2.5" rows="3" maxlength="1000" :placeholder="$t('vitrina.form.aboutHint')" />
                  </div>

                  <div>
                     <label class="label">{{ $t("district.city") }}</label>
                     <FieldSelect
                        :model-value="info.district_id ?? 0"
                        :options="cityStore.cities ?? []"
                        value="name"
                        @update:model-value="(id) => (info.district_id = id || null)"
                     />
                  </div>

                  <div>
                     <label class="label" for="v-address">{{ $t("vitrina.form.address") }}</label>
                     <input id="v-address" v-model="info.address" class="field" maxlength="150" :placeholder="$t('vitrina.form.addressHint')" />
                  </div>

                  <div class="grid grid-cols-2 gap-3">
                     <div>
                        <label class="label" for="v-phone">{{ $t("profile.phone") }}</label>
                        <input id="v-phone" v-model="info.phone" class="field" type="tel" inputmode="tel" maxlength="30" placeholder="93-123-45-67" />
                     </div>
                     <div>
                        <label class="label" for="v-hours">{{ $t("vitrina.form.hours") }}</label>
                        <input id="v-hours" v-model="info.work_hours" class="field" maxlength="60" placeholder="09:00–19:00" />
                     </div>
                     <div>
                        <label class="label" for="v-tg">Telegram</label>
                        <input id="v-tg" v-model="info.telegram" class="field" maxlength="40" placeholder="@username" />
                     </div>
                     <div>
                        <label class="label" for="v-ig">Instagram</label>
                        <input id="v-ig" v-model="info.instagram" class="field" maxlength="40" placeholder="@instagram" />
                     </div>
                  </div>

                  <BaseButton type="submit" class="w-full mt-1" :loading="savingInfo">
                     {{ vitrina ? $t("common.save") : $t("vitrina.create") }}
                  </BaseButton>
                  <p v-if="infoSaved" class="text-center text-xs text-emerald-600">{{ $t("vitrina.saved") }}</p>
               </form>
            </template>
         </main>
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
      />
   </section>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import {
   ArrowLeft,
   Camera,
   ChevronRight,
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
import FieldSelect from "@shared/ui/FieldSelect.vue";
import VitrinaRepo from "@shared/entities/Vitrina/VitrinaRepo";
import { getPriceTypes } from "@shared/entities/Product/ProductInputs";
import { useCity } from "@shared/entities/District/useCity";
import { useAuth } from "@shared/store/useAuth";
import type { IVitrina, IVitrinaItem, IVitrinaSection, VitrinaType } from "@shared/types";
import { goBack } from "@/modules/navigation";
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
const info = reactive({
   type: "shop" as VitrinaType,
   name: "",
   description: "",
   district_id: null as number | null,
   address: "",
   phone: "",
   telegram: "",
   instagram: "",
   work_hours: "",
});
const files = reactive<{ logo: File | null; cover: File | null }>({ logo: null, cover: null });
const previews = reactive<{ logo: string | null; cover: string | null }>({ logo: null, cover: null });
const savingInfo = ref(false);
const infoError = ref("");
const infoSaved = ref(false);

const logoPreview = computed(() => previews.logo ?? storageUrl(vitrina.value?.logo));
const coverPreview = computed(() => previews.cover ?? storageUrl(vitrina.value?.cover));

function fillInfo(source: IVitrina | null) {
   const user = authStore.user;
   info.type = source?.type ?? "shop";
   info.name = source?.name ?? "";
   info.description = source?.description ?? "";
   // Yangi vitrina: foydalanuvchi tanlagan shahar, profildagi raqam va Telegram
   info.district_id = source ? source.district_id : (user?.active_district_id ?? null);
   info.address = source?.address ?? "";
   info.phone = source ? (source.phone ?? "") : (user?.phone ?? "");
   info.telegram = source ? (source.telegram ? `@${source.telegram}` : "") : user?.username ? `@${user.username}` : "";
   info.instagram = source?.instagram ? `@${source.instagram}` : "";
   info.work_hours = source?.work_hours ?? "";
}

function pick(event: Event, field: "logo" | "cover") {
   const input = event.target as HTMLInputElement;
   const file = input.files?.[0];
   input.value = "";
   if (!file) return;
   if (previews[field]) URL.revokeObjectURL(previews[field]!);
   files[field] = file;
   previews[field] = URL.createObjectURL(file);
}

async function saveInfo() {
   savingInfo.value = true;
   infoError.value = "";
   infoSaved.value = false;
   try {
      const data = new FormData();
      for (const [key, value] of Object.entries(info)) data.append(key, value === null ? "" : String(value));
      if (files.logo) data.append("logo", files.logo);
      if (files.cover) data.append("cover", files.cover);

      const isNew = !vitrina.value;
      const { data: saved } = await VitrinaRepo.save(data);
      vitrina.value = saved;
      files.logo = files.cover = null;
      fillInfo(saved);
      infoSaved.value = true;
      // Yaratilgach — darhol mahsulot qo'shishga
      if (isNew) tab.value = "items";
   } catch (e) {
      infoError.value = errorMessage(e);
   } finally {
      savingInfo.value = false;
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

onMounted(async () => {
   try {
      const [{ data }, types] = await Promise.all([
         VitrinaRepo.mine(),
         getPriceTypes(),
         cityStore.cities?.length ? Promise.resolve() : cityStore.getCities(),
      ]);
      vitrina.value = data.vitrina ?? null;
      priceTypes.value = (types ?? []) as typeof priceTypes.value;
      fillInfo(vitrina.value);
      if (!vitrina.value) tab.value = "info";
   } catch (error) {
      infoError.value = errorMessage(error);
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

.section-title {
   margin: 0 0 0.5rem 0.25rem;
   color: var(--z-muted-text);
   font-size: 0.6875rem;
   font-weight: 700;
   letter-spacing: 0.08em;
   text-transform: uppercase;
}

.label {
   display: block;
   margin-bottom: 0.375rem;
   color: var(--z-muted-text);
   font-size: 0.75rem;
   font-weight: 600;
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
