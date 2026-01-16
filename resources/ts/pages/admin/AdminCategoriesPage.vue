<template>
   <div>
      <div class="flex justify-center">
         <Dialog
            v-model:visible="isVisible"
            modal
            class="w-3/4 headless-dialog"
            header=""
            :closable="false"
         >
            <template #header>
               <h3
                  class="text-center font-semibold text-lg py-3 w-full bg-secondary rounded-t-2xl border-b border-input"
               >
                  {{ pageData.selectedCategory?.label }} - parametrlari
               </h3>
            </template>
            <CategoryParameterDialog
               :category="pageData.selectedCategory!"
               @close="isVisible = false"
            />
         </Dialog>
         <Drawer
            class="headless-drawer"
            @hide="closeDrawer"
            v-model:visible="pageData.drawerToggle"
            :show-close-icon="false"
         >
            <main class="h-full -mx-5">
               <BaseForm
                  @close="pageData.drawerToggle = false"
                  :submit="submit"
                  :inputConfigs="inputConfigs"
               >
                  <template #header>
                     <h3
                        class="py-2.5 text-center font-semibold w-full bg-secondary border-b border-input"
                     >
                        {{ pageData.title }}
                     </h3>
                  </template>
                  <template #inputs>
                     <div
                        v-if="pageData.selectedParent"
                        class="flex gap-3 items-center p-1.5 mb-4 bg-secondary rounded border border-input"
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
                  </template>
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
               @paramaters="openParameterModal"
               @edit="openEditForm"
            />
         </template>
      </Tree>
   </div>
</template>

<script setup lang="ts">
import TreeNodeItem from "@/components/ui/TreeNodeItem.vue";
import BaseForm from "@/components/BaseForm.vue";
import CategoryRepo from "@/entities/Category/CategoryRepo";
import { computed, reactive, shallowRef } from "vue";
import { categoryInputs } from "@/entities/Category/CategoryInputs";
import { findParentId } from "@/modules/Helpers";
import { TreeNode } from "primevue/treenode";
import CategoryParameterDialog from "@/entities/CategoryParameter/CategoryParameterDialog.vue";
const { fetchData: fetchCategories, convertTreeNode } = CategoryRepo.parents();
var submit: (values: any) => Promise<void>;

const pageData = reactive<{
   drawerToggle: boolean;
   updateLoading: string | null;
   title: string;
   selectedForUpdate: string | null;
   selectedParent: TreeNode | null;
   selectedCategory: TreeNode | null;
}>({
   drawerToggle: false,
   updateLoading: null,
   title: "",
   selectedForUpdate: null,
   selectedParent: null,
   selectedCategory: null,
});

// Dialog ko'rinishini boshqaruvchi computed
const isVisible = computed({
   get: () => !!pageData.selectedCategory, // Agar null bo'lmasa true qaytaradi
   set: (value) => {
      if (!value) pageData.selectedCategory = null; // Modal yopilganda datani null qiladi
   },
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

const openParameterModal = async function (node: TreeNode) {
   pageData.selectedCategory = node;
};

const openCreateForm = async function (node: TreeNode | null = null) {
   submit = async (values) => {
      await CategoryRepo.store(node?.key || null, values);
      fetchCategories();
   };
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

async function openEditForm(node: TreeNode) {
   pageData.selectedForUpdate = node.key;
   pageData.updateLoading = node.key;
   pageData.title = text.edit;

   submit = async (values) => {
      await CategoryRepo.update(node.key, values);
      fetchCategories();
   };

   const { data: category } = await CategoryRepo.show(node.key);
   inputConfigs.value.map(async (input) => {
      if (input.generateProps) await input.generateProps();
      input.value = category[input.name];
      return input;
   });

   pageData.updateLoading = null;
   pageData.drawerToggle = true;
}

// Drag and Drop hodisasi sodir bo'lganda
const onNodeDrop = (event) => {
   var newTree = event.value;
   var dragNodeKey = event.dragNode.key;
   var newParent = findParentId(newTree, dragNodeKey);
   CategoryRepo.changeParent(dragNodeKey, newParent ? newParent.key : null);
};
</script>
