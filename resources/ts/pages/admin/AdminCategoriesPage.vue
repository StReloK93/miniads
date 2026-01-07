<template>
   <div>
      <div class="flex justify-center">
         <Drawer
            class="headless-drawer"
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
               />
            </main>
         </Drawer>
         <div class="flex justify-between items-center w-full px-3 py-2">
            <h3>Kategoriyalar</h3>
            <Button
               icon="pi pi-plus"
               severity="secondary"
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
         class="w-full bg-transparent!"
      >
         <template #default="slotProps">
            <TreeNodeItem
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
import { categoryInputs } from "@/configs/ConfigInputs";
import { findParentId } from "@/modules/Helpers";
const { fetchData: fetchCategories, convertTreeNode } = CategoryRepo.parents();
var submit: (values: unknown) => Promise<void>;

const pageData = reactive<{
   drawerToggle: boolean;
   updateLoading: string | null;
   title: string;
}>({
   drawerToggle: false,
   updateLoading: null,
   title: "",
});

const inputConfigs = shallowRef(categoryInputs);

const text = {
   add: "Yangi kategoriya qo'shish",
   edit: "Kategoriyani tahrirlash",
};

const openCreateForm = async function (parent_id: null | number = null) {
   submit = async (values) => await createCategory(parent_id, values);
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
};

async function openEditForm(node: any) {
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

async function createCategory(parent_id: number | null, values) {
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
   const changes = {
      currentNode: event.dragNode,
      parentNode: newParent,
   };
   console.log(changes);
   CategoryRepo.changeParent(dragNodeKey, newParent ? newParent.key : null);
};
</script>
