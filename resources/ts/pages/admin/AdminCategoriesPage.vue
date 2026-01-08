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
                  class="px-5 py-1.5 text-center font-semibold w-full border-b border-surface-200 mb-2"
               >
                  {{ pageData.title }}
               </h3>
            </template>
            <main class="h-full">
               <BaseForm
                  @close="pageData.drawerToggle = false"
                  :submit="submit"
                  :inputConfigs="inputConfigs"
               >
                  <div
                     v-if="pageData.selectedParent"
                     class="flex gap-3 items-center p-1.5 bg-surface-100 rounded border border-surface-300"
                  >
                     <img
                        v-if="pageData.selectedParent.image"
                        :src="pageData.selectedParent.image"
                        draggable="false"
                        class="w-6 pointer-events-none dark:invert"
                     />
                     <span class="font-medium">
                        {{ pageData.selectedParent.label }}
                     </span>
                  </div>
               </BaseForm>
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
      <Tree
         v-model:value="convertTreeNode"
         draggableNodes
         droppableNodes
         @node-drop="onNodeDrop"
         class="w-full bg-tertiary p-4! rounded-xl border border-secondary"
      >
         <template #default="slotProps">
            <TreeNodeItem
               :selectedForUpdate="pageData.selectedForUpdate"
               :selectedParent="pageData.selectedParent"
               :key="slotProps.node.key"
               :node="slotProps.node"
               :update-loading="pageData.updateLoading"
               @create="openCreateForm"
               @edit="openEditForm"
            />
         </template>
      </Tree>
   </div>
</template>

<script setup lang="ts">
import TreeNodeItem from "@/components/ui/TreeNodeItem.vue";
import BaseForm from "@/components/BaseForm.vue";
import CategoryRepo from "@/repositories/CategoryRepo";
import { reactive, shallowRef } from "vue";
import { categoryInputs } from "@/configs/CategoryInputs";
import { findParentId } from "@/modules/Helpers";
import { TreeNode } from "primevue/treenode";
const { fetchData: fetchCategories, convertTreeNode } = CategoryRepo.parents();
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

const text = {
   add: "Yangi kategoriya qo'shish",
   edit: "Kategoriyani tahrirlash",
};

function closeDrawer() {
   pageData.selectedForUpdate = null;
   pageData.selectedParent = null;
}

const openCreateForm = async function (node: TreeNode | null = null) {
   submit = async (values) => await createCategory(node?.key || null, values);
   pageData.title = text.add;
   pageData.selectedParent = node;

   await Promise.all(
      inputConfigs.value.map(async (input) => {
         if (input.generateProps) await input.generateProps();
         input.value = undefined;
         return input;
      }),
   ).finally(() => {
      pageData.drawerToggle = true;
   });
};

async function openEditForm(node: any) {
   pageData.selectedForUpdate = node.key;
   pageData.updateLoading = node.key;
   pageData.title = text.edit;

   submit = async (values) => await updateCategory(node.key, values);

   CategoryRepo.show(node.key, ({ data }) => {
      inputConfigs.value.map(async (input) => {
         if (input.generateProps) await input.generateProps();
         input.value = data[input.name];
         return input;
      });

      pageData.updateLoading = null;
      pageData.drawerToggle = true;
   });
}

async function createCategory(parent_id: number | string | null, values) {
   await CategoryRepo.store(parent_id, values);
   fetchCategories();
}

async function updateCategory(id: string, values) {
   await CategoryRepo.update(id, values);
   fetchCategories();
}

// Drag and Drop hodisasi sodir bo'lganda
const onNodeDrop = (event) => {
   var newTree = event.value;
   var dragNodeKey = event.dragNode.key;
   var newParent = findParentId(newTree, dragNodeKey);
   CategoryRepo.changeParent(dragNodeKey, newParent ? newParent.key : null);
};
</script>
