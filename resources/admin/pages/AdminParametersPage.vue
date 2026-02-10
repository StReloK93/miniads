<template>
   <div>
      <div class="flex justify-center flex-col">
         <Drawer
            class="headless-drawer"
            @hide="closeDrawer"
            v-model:visible="pageData.drawerToggle"
            :show-close-icon="false"
         >
            <template #header>
               <h3 class="px-5 py-1.5 text-center font-semibold w-full border border-secondary mb-2">
                  {{ pageData.title }}
               </h3>
            </template>
            <main class="h-full -mx-5">
               <BaseForm
                  @close="pageData.drawerToggle = false"
                  :submit="submit"
                  :superRefine
                  :inputConfigs="inputConfigs"
               />
            </main>
         </Drawer>
         <div class="flex justify-between items-center w-full px-3 py-2">
            <h3>Kategoriyalar</h3>
            <Button icon="pi pi-plus" variant="text" size="small" rounded @click="openCreateForm()" />
         </div>
         <main class="p-6 rounded-xl bg-tertiary border-secondary border">
            <BaseTable :parameters="parameters || []" :columns="parameterColumns" @edit="openEditForm" />
         </main>
      </div>
   </div>
</template>

<script setup lang="ts">
import { Button, Drawer } from "primevue";
import BaseTable from "@admin/components/BaseTable.vue";
import BaseForm from "@admin/components/BaseForm.vue";
import ParameterRepo from "@shared/entities/Parameter/ParameterRepo";
import { onMounted, reactive, shallowRef } from "vue";
import { useFetchDecorator } from "@shared/composables/useFetch";
import { parameterInputs, superRefine, parameterColumns } from "@shared/entities/Parameter/ParameterInputs";
import { TreeNode } from "primevue/treenode";
import { IParameter } from "@shared/types";
var submit: (values: any) => Promise<void>;

const pageData = reactive<{
   drawerToggle: boolean;
   updateLoading: string | null;
   title: string;
   selectedForUpdate: string | null;
   selectedParent: TreeNode | null;
}>({
   drawerToggle: false,
   updateLoading: null,
   title: "",
   selectedForUpdate: null,
   selectedParent: null,
});

const { data: parameters, execute: executeParameters } = useFetchDecorator<IParameter[]>(ParameterRepo.index);

const inputConfigs = shallowRef(parameterInputs);

async function openCreateForm() {
   submit = async (values: IParameter) => {
      await ParameterRepo.store(values);
      executeParameters();
   };
   pageData.title = text.add;

   await Promise.all(
      inputConfigs.value.map(async (input) => {
         if (input.generateProps) await input.generateProps();
         input.value = undefined;
         return input;
      }),
   ).finally(() => {
      pageData.drawerToggle = true;
   });
}

async function openEditForm(id: string | number) {
   submit = async (values) => {
      await ParameterRepo.update(id, values);
      executeParameters();
   };
   pageData.title = text.edit;

   const { data: parameter } = await ParameterRepo.show(id);

   await Promise.all(
      inputConfigs.value.map(async (input) => {
         if (input.generateProps) await input.generateProps();
         input.value = parameter ? parameter[input.name] : undefined;
         return input;
      }),
   ).finally(() => {
      pageData.drawerToggle = true;
   });
}

const text = {
   add: "Yangi parameter qo'shish",
   edit: "Parameterni tahrirlash",
};

function closeDrawer() {
   pageData.selectedForUpdate = null;
   pageData.selectedParent = null;
}

onMounted(() => {
   executeParameters();
});
</script>
