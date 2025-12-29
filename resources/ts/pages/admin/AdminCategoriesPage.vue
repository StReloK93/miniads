<template>
   <div>
      <div class="card flex justify-center">
         <Drawer
            class="headless-drawer"
            v-model:visible="visible"
            header=""
            :show-close-icon="false"
         >
            <BaseForm
               :submit="submitCategoryForm"
               :inputConfigs="categoryInputs"
               class="pt-5"
            ></BaseForm>
         </Drawer>
         <Button icon="pi pi-arrow-right" @click="visible = true" />
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
const { data: categories } = CategoryRepo.index();
const visible = ref(false);
const selectedCategory: Ref<TreeNode | null> = ref(null);
const formatCategories = (categories) => {
   return categories.map((item) => {
      const node = {
         key: String(item.id), // Key doim String bo'lishi kerak
         label: item.name, // Sizdagi 'name' yoki 'title'
         data: item, // Ob'ektning hamma ma'lumotini saqlab qo'yish foydali
         children: [], // Bolalar uchun bo'sh massiv
      };
      if (item.children && item.children.length > 0) {
         node.children = formatCategories(item.children);
      }
      return node;
   });
};

const convertTreeNode = computed(() => formatCategories(categories.value || []));
function addNewCategory(node) {
   selectedCategory.value = node;
   console.log(node);
}

async function submitCategoryForm(values) {
   if (selectedCategory.value) {
      values.parent_id = Number(selectedCategory.value.key);
   }
   // await CategoryRepo.store(values);
}
</script>
