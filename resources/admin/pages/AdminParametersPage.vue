<template>
   <section class="admin-page">
      <BaseDrawer :open="drawerOpen" :title="title" @close="drawerOpen = false">
         <BaseForm
            :key="formKey"
            :submit="submit"
            :super-refine="superRefine"
            :input-configs="inputConfigs"
            @close="drawerOpen = false"
         />
      </BaseDrawer>

      <header class="admin-page-heading">
         <div>
            <h1>Parametrlar</h1>
            <p class="admin-muted">Kategoriyalarga biriktiriladigan e'lon maydonlarini boshqaring.</p>
         </div>
         <BaseButton size="sm" @click="openCreateForm">
            <template #icon><Plus class="size-4" /></template>
            Parametr qo'shish
         </BaseButton>
      </header>

      <p v-if="error" class="admin-alert admin-alert-error">{{ error }}</p>

      <AdminTabs v-model="kind" :tabs="kindTabs" />

      <section class="admin-chart-card py-1!">
         <label class="admin-search my-2">
            <Search class="size-4 shrink-0" />
            <input v-model="search" type="search" placeholder="Parametr nomi yoki variant" />
         </label>
         <p v-if="loading" class="admin-loading">Parametrlar yuklanmoqda…</p>
         <div v-else class="admin-list">
            <div v-for="parameter in visible" :key="parameter.id" class="admin-list-row">
               <span class="admin-list-main">
                  <span class="admin-list-title">{{ parameter.title }}</span>
                  <span class="admin-list-meta">
                     {{ kindLabel(parameter) }}<template v-if="parameter.unit"> · {{ parameter.unit }}</template>
                     <template v-if="optionsOf(parameter).length"> · {{ optionsOf(parameter).length }} variant</template>
                     · #{{ parameter.id }}
                  </span>
                  <span v-if="optionsOf(parameter).length" class="admin-list-meta">{{ optionsOf(parameter).join(", ") }}</span>
               </span>
               <BaseButton size="xs" severity="secondary" icon-only title="Tahrirlash" @click="openEditForm(parameter.id)">
                  <template #icon><Pencil class="size-3.5" /></template>
               </BaseButton>
               <BaseButton size="xs" severity="danger" icon-only title="O'chirish" @click="deleteTarget = parameter">
                  <template #icon><Trash2 class="size-3.5" /></template>
               </BaseButton>
            </div>
            <p v-if="!visible.length" class="admin-muted py-3">Parametr topilmadi.</p>
         </div>
      </section>

      <BaseModal
         :open="deleteTarget !== null"
         title="O'chirishni tasdiqlang"
         :description="`«${deleteTarget?.title}» parametri o'chiriladi.`"
         confirm-text="O'chirish"
         cancel-text="Bekor qilish"
         danger
         @close="deleteTarget = null"
         @confirm="confirmDelete"
      >
         <template #icon><TriangleAlert class="size-5 text-(--z-danger)" /></template>
      </BaseModal>
   </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, shallowRef } from "vue";
import { Pencil, Plus, Search, Trash2, TriangleAlert } from "lucide-vue-next";
import BaseButton from "@shared/ui/BaseButton.vue";
import BaseDrawer from "@shared/ui/BaseDrawer.vue";
import BaseModal from "@shared/ui/BaseModal.vue";
import BaseForm from "@admin/components/BaseForm.vue";
import AdminTabs from "@admin/components/AdminTabs.vue";
import ParameterRepo from "@shared/entities/Parameter/ParameterRepo";
import { useFetchDecorator } from "@shared/composables/useFetch";
import { parameterInputs, superRefine } from "@shared/entities/Parameter/ParameterInputs";
import { IParameter } from "@shared/types";

const { data: parameters, execute: executeParameters, isLoading: loading } =
   useFetchDecorator<IParameter[]>(ParameterRepo.index);
const inputConfigs = shallowRef(parameterInputs);

// Telefonda 50+ parametr: bitta qatorda nom va turi, qidiruv va tur bo'yicha tablar
const KINDS: Record<string, string> = { FieldNumber: "Son", FieldSelect: "Tanlov", FieldText: "Matn" };
const kindLabel = (parameter: IParameter) => KINDS[parameter.component] ?? parameter.component;
// Serverdan variantlar oddiy satrlar ro'yxati bo'lib keladi
const optionsOf = (parameter: IParameter) => (parameter.options ?? []) as unknown as string[];
const kind = ref("all");
const search = ref("");
const deleteTarget = ref<IParameter | null>(null);

const kindTabs = computed(() => {
   const list = parameters.value ?? [];
   const count = (component: string) => list.filter((p) => p.component === component).length;
   return [
      { value: "all", label: "Hammasi", count: list.length },
      { value: "FieldSelect", label: "Tanlov", count: count("FieldSelect") },
      { value: "FieldNumber", label: "Son", count: count("FieldNumber") },
      { value: "FieldText", label: "Matn", count: count("FieldText") },
   ].filter((tab) => tab.value === "all" || tab.count);
});

const visible = computed(() => {
   const query = search.value.trim().toLowerCase();
   return (parameters.value ?? []).filter(
      (p) =>
         (kind.value === "all" || p.component === kind.value) &&
         (!query || p.title.toLowerCase().includes(query) || optionsOf(p).some((o) => o.toLowerCase().includes(query))),
   );
});

function confirmDelete() {
   if (deleteTarget.value) deleteParameter(deleteTarget.value.id);
   deleteTarget.value = null;
}
const drawerOpen = ref(false);
const title = ref("");
const error = ref("");
const formKey = ref(0);
const submit = ref<(values: Record<string, unknown>) => Promise<void>>(async () => {
   throw new Error("Parametr formasi saqlashga tayyor emas.");
});

async function openCreateForm() {
   error.value = "";
   title.value = "Yangi parametr qo'shish";
   inputConfigs.value.forEach((input) => (input.value = undefined));
   formKey.value += 1;
   submit.value = async (values) => {
      await ParameterRepo.store(values as unknown as IParameter);
      await executeParameters();
   };
   drawerOpen.value = true;
}

async function openEditForm(id: string | number) {
   error.value = "";
   title.value = "Parametrni tahrirlash";
   try {
      const { data: parameter } = await ParameterRepo.show(id);
      inputConfigs.value.forEach((input) => {
         input.value = parameter ? parameter[input.name] : undefined;
      });
      formKey.value += 1;
      submit.value = async (values) => {
         await ParameterRepo.update(id, values as unknown as IParameter);
         await executeParameters();
      };
      drawerOpen.value = true;
   } catch (exception) {
      console.error("Parametr ma'lumotini yuklab bo'lmadi.", exception);
      error.value = "Parametrni tahrirlash uchun ma'lumotni yuklab bo'lmadi.";
   }
}

async function deleteParameter(id: string | number) {
   try {
      await ParameterRepo.delete(id);
      await executeParameters();
   } catch (exception) {
      console.error("Parametrni o'chirib bo'lmadi.", exception);
      error.value = "Parametrni o'chirib bo'lmadi.";
   }
}

onMounted(async () => {
   try {
      await executeParameters();
   } catch (exception) {
      console.error("Parametrlarni yuklab bo'lmadi.", exception);
      error.value = "Parametrlarni yuklab bo'lmadi. Sahifani yangilab ko'ring.";
   }
});
</script>
