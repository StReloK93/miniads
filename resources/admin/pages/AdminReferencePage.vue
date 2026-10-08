<template>
   <section class="admin-page">
      <!-- Create / Edit Drawer -->
      <BaseDrawer
         :open="drawerOpen"
         :title="editingId ? `${itemLabel}ni tahrirlash` : `Yangi ${itemLabel.toLowerCase()} qo'shish`"
         :description="drawerDescription"
         @close="closeDrawer"
      >
         <form class="flex flex-col h-full gap-4" @submit.prevent="save">
            <p v-if="drawerError" class="admin-alert admin-alert-error mb-2">{{ drawerError }}</p>

            <div class="flex-1 flex flex-col gap-4 overflow-y-auto">
               <AdminField
                  id="ref-name"
                  :label="kind === 'districts' ? 'Shahar nomi' : 'Narx turi nomi'"
                  placeholder="Nomini kiriting"
                  :model-value="form.name"
                  @update:model-value="form.name = String($event)"
               />

               <template v-if="kind === 'price-types'">
                  <AdminField
                     id="ref-type"
                     label="Qisqa ko'rinishi"
                     placeholder="Masalan, so'm/oy yoki $"
                     :model-value="form.type"
                     @update:model-value="form.type = String($event)"
                  />

                  <div class="admin-field">
                     <label class="admin-field-label" for="ref-pos">Narx yonidagi joylashuvi</label>
                     <FieldSelect
                        id="ref-pos"
                        v-model="form.position"
                        :options="positionOptions"
                     />
                  </div>
               </template>
            </div>

            <footer class="mt-auto pt-4 border-t border-(--z-border) flex items-center justify-end gap-3">
               <BaseButton
                  type="button"
                  severity="secondary"
                  variant="text"
                  size="sm"
                  :disabled="saving"
                  @click="closeDrawer"
               >
                  Bekor qilish
               </BaseButton>
               <BaseButton
                  type="submit"
                  severity="primary"
                  size="sm"
                  :loading="saving"
                  :disabled="!form.name.trim()"
               >
                  <template #icon><Check class="size-4" /></template>
                  {{ editingId ? "Saqlash" : "Qo'shish" }}
               </BaseButton>
            </footer>
         </form>
      </BaseDrawer>

      <!-- Delete Confirmation Modal -->
      <BaseModal
         :open="itemToDelete !== null"
         title="O'chirishni tasdiqlang"
         :description="`“${itemToDelete?.name}” yozuvini o'chirib tashlamoqchimisiz?`"
         confirm-text="O'chirish"
         cancel-text="Bekor qilish"
         danger
         @close="itemToDelete = null"
         @confirm="confirmDelete"
      >
         <template #icon>
            <TriangleAlert class="size-5 text-(--z-danger)" />
         </template>
         <p class="text-sm text-(--z-muted-text)">
            Ushbu amalni ortga qaytarib bo'lmaydi. Tegishli e'lonlar mazkur parametrga bog'langan bo'lishi mumkin.
         </p>
      </BaseModal>

      <!-- Page Header -->
      <header class="admin-page-heading">
         <div>
            <h1>{{ title }}</h1>
            <p class="admin-muted">{{ description }}</p>
         </div>
         <BaseButton size="sm" @click="openCreateDrawer">
            <template #icon><Plus class="size-4" /></template>
            {{ addButtonLabel }}
         </BaseButton>
      </header>

      <p v-if="error" class="admin-alert admin-alert-error">{{ error }}</p>

      <!-- Table Panel -->
      <section class="admin-panel admin-table-panel">
         <div v-if="loading" class="admin-loading">Ro'yxat yuklanmoqda…</div>
         <div v-else class="admin-table-wrap">
            <table class="admin-table">
               <thead>
                  <tr>
                     <th>Nomi</th>
                     <th v-if="kind === 'price-types'">Ko'rinishi</th>
                     <th v-if="kind === 'price-types'">Joylashuvi</th>
                     <th class="admin-actions-heading">Amallar</th>
                  </tr>
               </thead>
               <tbody>
                  <tr v-for="item in items" :key="item.id">
                     <td>
                        <strong>{{ item.name }}</strong>
                     </td>
                     <td v-if="kind === 'price-types'">
                        <span class="admin-badge admin-badge-info">{{ item.type || "—" }}</span>
                     </td>
                     <td v-if="kind === 'price-types'">
                        {{ item.position === "left" ? "Chap tomonda" : "O'ng tomonda" }}
                     </td>
                     <td class="admin-actions">
                        <div class="flex items-center justify-end gap-1.5">
                           <BaseButton
                              size="xs"
                              severity="secondary"
                              icon-only
                              title="Tahrirlash"
                              :aria-label="`${item.name} ni tahrirlash`"
                              @click="edit(item)"
                           >
                              <template #icon><Pencil class="size-3.5" /></template>
                           </BaseButton>
                           <BaseButton
                              size="xs"
                              severity="danger"
                              icon-only
                              title="O'chirish"
                              :aria-label="`${item.name} ni o'chirish`"
                              @click="itemToDelete = item"
                           >
                              <template #icon><Trash2 class="size-3.5" /></template>
                           </BaseButton>
                        </div>
                     </td>
                  </tr>
                  <tr v-if="items.length === 0">
                     <td :colspan="kind === 'price-types' ? 4 : 2" class="admin-empty-cell">
                        Hozircha hech qanday {{ itemLabel.toLowerCase() }} qo'shilmagan.
                     </td>
                  </tr>
               </tbody>
            </table>
         </div>
      </section>
   </section>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from "vue";
import { Check, Pencil, Plus, Trash2, TriangleAlert } from "lucide-vue-next";
import AdminRepo from "@admin/entities/AdminRepo";
import BaseButton from "@shared/ui/BaseButton.vue";
import BaseDrawer from "@shared/ui/BaseDrawer.vue";
import BaseModal from "@shared/ui/BaseModal.vue";
import AdminField from "@shared/ui/AdminField.vue";
import FieldSelect from "@shared/ui/FieldSelect.vue";

type ReferenceKind = "districts" | "price-types";
type ReferenceItem = { id: number; name: string; type?: string; position?: "left" | "right" };

const props = defineProps<{ kind: ReferenceKind }>();

const title = computed(() => (props.kind === "districts" ? "Shaharlar" : "Narx turlari"));
const itemLabel = computed(() => (props.kind === "districts" ? "Shahar" : "Narx turi"));
const description = computed(() =>
   props.kind === "districts"
      ? "E'lonlarda va hudud filtrlashida ishlatiladigan shaharlar."
      : "E'lon narxida ko'rsatiladigan birlik va joylashuv sozlamalari.",
);

const drawerDescription = computed(() =>
   editingId.value
      ? "Mavjud ma'lumotlarni o'zgartiring va saqlang."
      : "Katalog uchun yangi yozuv kiriting."
);

const addButtonLabel = computed(() =>
   props.kind === "districts" ? "Shahar qo'shish" : "Narx turi qo'shish"
);

const positionOptions = [
   { label: "O'ng tomon (100 so'm)", value: "right" },
   { label: "Chap tomon ($ 100)", value: "left" },
];

const items = ref<ReferenceItem[]>([]);
const loading = ref(false);
const saving = ref(false);
const editingId = ref<number | null>(null);
const drawerOpen = ref(false);
const error = ref("");
const drawerError = ref("");
const itemToDelete = ref<ReferenceItem | null>(null);

const form = reactive({ name: "", type: "", position: "right" as "left" | "right" });

async function load() {
   loading.value = true;
   error.value = "";
   try {
      const { data } = await AdminRepo.reference(props.kind);
      items.value = data.filter((item: ReferenceItem) => item.id !== 0);
   } catch (exception) {
      console.error(`${title.value} ro'yxati yuklanmadi.`, exception);
      error.value = `${title.value} ro'yxatini yuklab bo'lmadi.`;
   } finally {
      loading.value = false;
   }
}

function openCreateDrawer() {
   editingId.value = null;
   form.name = "";
   form.type = "";
   form.position = "right";
   drawerError.value = "";
   drawerOpen.value = true;
}

function edit(item: ReferenceItem) {
   editingId.value = item.id;
   form.name = item.name;
   form.type = item.type || "";
   form.position = item.position || "right";
   drawerError.value = "";
   drawerOpen.value = true;
}

function closeDrawer() {
   drawerOpen.value = false;
   editingId.value = null;
   form.name = "";
   form.type = "";
   form.position = "right";
   drawerError.value = "";
}

async function save() {
   if (!form.name.trim()) return;

   saving.value = true;
   drawerError.value = "";
   const payload: Record<string, any> =
      props.kind === "districts"
         ? { name: form.name.trim() }
         : { name: form.name.trim(), type: form.type.trim(), position: form.position };

   try {
      if (editingId.value) {
         await AdminRepo.updateReference(props.kind, editingId.value, payload);
      } else {
         await AdminRepo.storeReference(props.kind, payload);
      }
      closeDrawer();
      await load();
   } catch (exception: any) {
      console.error(`${title.value} ma'lumotini saqlab bo'lmadi.`, exception);
      drawerError.value = exception.response?.data?.message || "Ma'lumotni saqlab bo'lmadi.";
   } finally {
      saving.value = false;
   }
}

async function confirmDelete() {
   if (!itemToDelete.value) return;

   const target = itemToDelete.value;
   itemToDelete.value = null;
   error.value = "";

   try {
      await AdminRepo.deleteReference(props.kind, target.id);
      if (editingId.value === target.id) closeDrawer();
      await load();
   } catch (exception: any) {
      console.error(`${title.value} yozuvini o'chirib bo'lmadi.`, exception);
      error.value = exception.response?.data?.message || "Yozuvni o'chirib bo'lmadi.";
   }
}

onMounted(() => load());

watch(
   () => props.kind,
   () => {
      closeDrawer();
      load();
   },
);
</script>
