<template>
   <div>
      <div class="flex justify-center">
         <Drawer
            class="headless-drawer"
            @hide="closeDrawer"
            v-model:visible="pageData.drawerToggle"
            :show-close-icon="false"
         >
            <template #header>
               <h3
                  class="px-5 py-1.5 text-center font-semibold w-full border border-secondary mb-2"
               >
                  {{ pageData.title }}
               </h3>
            </template>
            <main class="h-full">
               <BaseForm
                  @close="pageData.drawerToggle = false"
                  :submit="submit"
                  :inputConfigs="inputConfigs"
               />
            </main>
         </Drawer>
         <div class="flex justify-between items-center w-full px-3 py-2">
            <h3>Kategoriyalar</h3>
            <Button
               icon="pi pi-plus"
               variant="text"
               size="small"
               rounded
               @click="openCreateForm()"
            />
         </div>
      </div>
   </div>
</template>

<script setup lang="ts">
import BaseForm from "@/components/BaseForm.vue";
import { reactive, ref, shallowRef } from "vue";
import { categoryInputs } from "@/configs/ParameterInputs";
import { TreeNode } from "primevue/treenode";
var submit: (values: unknown) => Promise<void>;

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

const inputConfigs = shallowRef(categoryInputs);

async function openCreateForm() {
   submit = async (values) => await createParameter(values);
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

const text = {
   add: "Yangi parameter qo'shish",
   edit: "Kategoriyani tahrirlash",
};

function closeDrawer() {
   pageData.selectedForUpdate = null;
   pageData.selectedParent = null;
}

async function createParameter(values) {
   console.log(values);
}
</script>
