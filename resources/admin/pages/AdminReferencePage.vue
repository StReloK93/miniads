<template>
   <section class="admin-page">
      <header class="admin-page-heading">
         <div>
            <p class="admin-eyebrow">KATALOG SOZLAMALARI</p>
            <h1>{{ title }}</h1>
            <p class="admin-muted">{{ description }}</p>
         </div>
      </header>

      <p v-if="error" class="admin-alert admin-alert-error">{{ error }}</p>
      <form class="admin-panel admin-reference-form" @submit.prevent="save">
         <label>
            <span>{{ kind === "districts" ? "Shahar nomi" : "Narx turi nomi" }}</span>
            <input v-model.trim="form.name" class="admin-input" required maxlength="100" />
         </label>
         <template v-if="kind === 'price-types'">
            <label>
               <span>Qisqa ko‘rinishi</span>
               <input v-model.trim="form.type" class="admin-input" required maxlength="40" placeholder="masalan, so‘m/oy" />
            </label>
            <label>
               <span>Narx yonidagi joylashuvi</span>
               <select v-model="form.position" class="admin-select">
                  <option value="right">O‘ng tomon</option>
                  <option value="left">Chap tomon</option>
               </select>
            </label>
         </template>
         <div class="admin-reference-actions">
            <button class="admin-button admin-button-primary" type="submit" :disabled="saving">
               {{ saving ? "Saqlanmoqda…" : editingId ? "O‘zgarishni saqlash" : "Qo‘shish" }}
            </button>
            <button v-if="editingId" class="admin-button admin-button-secondary" type="button" @click="resetForm">Bekor qilish</button>
         </div>
      </form>

      <section class="admin-panel admin-table-panel">
         <div v-if="loading" class="admin-loading">Ro‘yxat yuklanmoqda…</div>
         <div v-else class="admin-table-wrap">
            <table class="admin-table">
               <thead>
                  <tr>
                     <th>Nomi</th>
                     <th v-if="kind === 'price-types'">Ko‘rinishi</th>
                     <th v-if="kind === 'price-types'">Joylashuvi</th>
                     <th class="admin-actions-heading">Amallar</th>
                  </tr>
               </thead>
               <tbody>
                  <tr v-for="item in items" :key="item.id">
                     <td><strong>{{ item.name }}</strong></td>
                     <td v-if="kind === 'price-types'">{{ item.type }}</td>
                     <td v-if="kind === 'price-types'">{{ item.position === "left" ? "Chap" : "O‘ng" }}</td>
                     <td class="admin-actions">
                        <button class="admin-icon-button" type="button" :aria-label="`${item.name} ni tahrirlash`" title="Tahrirlash" @click="edit(item)">
                           <Pencil class="size-4" />
                        </button>
                        <button class="admin-icon-button admin-danger" type="button" :aria-label="`${item.name} ni o‘chirish`" title="O‘chirish" @click="remove(item)">
                           <Trash2 class="size-4" />
                        </button>
                     </td>
                  </tr>
                  <tr v-if="items.length === 0">
                     <td :colspan="kind === 'price-types' ? 4 : 2" class="admin-empty-cell">Ro‘yxat bo‘sh.</td>
                  </tr>
               </tbody>
            </table>
         </div>
      </section>
   </section>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from "vue";
import { Pencil, Trash2 } from "lucide-vue-next";
import AdminRepo from "@admin/entities/AdminRepo";

type ReferenceKind = "districts" | "price-types";
type ReferenceItem = { id: number; name: string; type?: string; position?: "left" | "right" };
const props = defineProps<{ kind: ReferenceKind }>();
const title = computed(() => (props.kind === "districts" ? "Shaharlar" : "Narx turlari"));
const description = computed(() =>
   props.kind === "districts"
      ? "E’lonlarda va hudud filtrlashida ishlatiladigan shaharlar."
      : "E’lon narxida ko‘rsatiladigan birlik va joylashuv sozlamalari.",
);
const items = ref<ReferenceItem[]>([]);
const loading = ref(false);
const saving = ref(false);
const editingId = ref<number | null>(null);
const error = ref("");
const form = reactive({ name: "", type: "", position: "right" as "left" | "right" });

async function load() {
   loading.value = true;
   error.value = "";
   try {
      const { data } = await AdminRepo.reference(props.kind);
      items.value = data.filter((item: ReferenceItem) => item.id !== 0);
   } catch (exception) {
      console.error(`${title.value} ro‘yxati yuklanmadi.`, exception);
      error.value = `${title.value} ro‘yxatini yuklab bo‘lmadi.`;
   } finally {
      loading.value = false;
   }
}

function edit(item: ReferenceItem) {
   editingId.value = item.id;
   form.name = item.name;
   form.type = item.type || "";
   form.position = item.position || "right";
   error.value = "";
}

function resetForm() {
   editingId.value = null;
   form.name = "";
   form.type = "";
   form.position = "right";
   error.value = "";
}

async function save() {
   saving.value = true;
   error.value = "";
   const payload = props.kind === "districts"
      ? { name: form.name }
      : { name: form.name, type: form.type, position: form.position };
   try {
      if (editingId.value) {
         await AdminRepo.updateReference(props.kind, editingId.value, payload);
      } else {
         await AdminRepo.storeReference(props.kind, payload);
      }
      resetForm();
      await load();
   } catch (exception: any) {
      console.error(`${title.value} ma’lumotini saqlab bo‘lmadi.`, exception);
      error.value = exception.response?.data?.message || "Ma’lumotni saqlab bo‘lmadi.";
   } finally {
      saving.value = false;
   }
}

async function remove(item: ReferenceItem) {
   if (!window.confirm(`“${item.name}” ni o‘chirmoqchimisiz?`)) return;
   error.value = "";
   try {
      await AdminRepo.deleteReference(props.kind, item.id);
      if (editingId.value === item.id) resetForm();
      await load();
   } catch (exception: any) {
      console.error(`${title.value} yozuvini o‘chirib bo‘lmadi.`, exception);
      error.value = exception.response?.data?.message || "Yozuvni o‘chirib bo‘lmadi.";
   }
}

onMounted(() => load());
watch(
   () => props.kind,
   () => {
      resetForm();
      load();
   },
);
</script>
