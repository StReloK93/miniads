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
      <div class="admin-panel admin-table-panel">
         <p v-if="loading" class="admin-loading">Parametrlar yuklanmoqda…</p>
         <BaseTable
            v-else
            :parameters="parameters || []"
            :columns="parameterColumns"
            @edit="openEditForm"
            @delete="deleteParameter"
         />
      </div>
   </section>
</template>

<script setup lang="ts">
import { onMounted, ref, shallowRef } from "vue";
import { Plus } from "lucide-vue-next";
import BaseButton from "@shared/ui/BaseButton.vue";
import BaseDrawer from "@shared/ui/BaseDrawer.vue";
import BaseForm from "@admin/components/BaseForm.vue";
import BaseTable from "@admin/components/BaseTable.vue";
import ParameterRepo from "@shared/entities/Parameter/ParameterRepo";
import { useFetchDecorator } from "@shared/composables/useFetch";
import { parameterInputs, superRefine, parameterColumns } from "@shared/entities/Parameter/ParameterInputs";
import { IParameter } from "@shared/types";

const { data: parameters, execute: executeParameters, isLoading: loading } =
   useFetchDecorator<IParameter[]>(ParameterRepo.index);
const inputConfigs = shallowRef(parameterInputs);
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
