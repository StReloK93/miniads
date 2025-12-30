<template>
   <div>
      <div class="flex justify-center">
         <Drawer class="headless-drawer" v-model:visible="visible" :show-close-icon="false">
            <BaseForm
               @close="visible = false"
               :submit="submitCategoryForm"
               :inputConfigs="categoryInputs"
               class="pt-5"
            />
         </Drawer>
         <div class="flex justify-between items-center w-full">
            <h3>Kategoriyalar</h3>
            <Button
               icon="pi pi-plus"
               severity="secondary"
               size="small"
               rounded
               variant="text"
               @click="visible = true"
            />
         </div>
      </div>
      <Tree :value="convertTreeNode" class="w-full md:w-120 bg-transparent!">
         <template #default="slotProps">
            <main class="w-90 flex justify-between items-center">
               <span>
                  {{ slotProps.node.label }}
               </span>
               <Button
                  @click="addNewCategory(slotProps.node)"
                  icon="pi pi-plus"
                  severity="secondary"
                  size="small"
                  rounded
                  variant="text"
               />
            </main>
         </template>
      </Tree>
   </div>
</template>

<script setup lang="ts">
import BaseForm from "@/components/BaseForm.vue";
import CategoryRepo from "@/repositories/CategoryRepo";
import { computed, Ref, ref } from "vue";
import { TreeNode } from "primevue/treenode";
import { categoryInputs } from "@/configs/ConfigInputs";
import { formatCategories } from "@/configs/Formatters";
const { data: categories } = CategoryRepo.index();
const visible = ref(false);
const selectedCategory: Ref<TreeNode | null> = ref(null);

const convertTreeNode = computed(() => formatCategories(categories.value || []));

function addNewCategory(node) {
   selectedCategory.value = node;
   console.log(node);
}

async function submitCategoryForm(values) {
   console.log(values);

   if (selectedCategory.value) {
      values.parent_id = Number(selectedCategory.value.key);
   }
   // await CategoryRepo.store(values);
}
</script>
