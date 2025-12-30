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
               :loading="loading"
               @click="openForm()"
            />
         </div>
      </div>
      <Tree :value="convertTreeNode" class="w-full md:w-120 bg-transparent!">
         <template #default="slotProps">
            <main class="w-90 flex justify-between items-center">
               <div class="flex gap-3 items-center">
                  <img :src="slotProps.node.image" class="w-6" />
                  <span>
                     {{ slotProps.node.label }}
                  </span>
               </div>
               <Button
                  @click="openForm(slotProps.node.key)"
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
import { computed, ref } from "vue";
import { categoryInputs } from "@/configs/ConfigInputs";
import { formatCategories } from "@/configs/Formatters";
const { data: categories } = CategoryRepo.index();
const visible = ref(false);
const loading = ref(false);
const convertTreeNode = computed(() => formatCategories(categories.value || []));

const openForm = async function (category_id: number | null | string | undefined = null) {
   loading.value = true;
   console.log(category_id);

   await Promise.all(
      categoryInputs.map(async (input) => {
         if (input.generateProps) {
            await input.generateProps();
         }
         input.value = category_id;
         return input;
      }),
   ).finally(() => {
      loading.value = false;
   });

   visible.value = true;
};

async function submitCategoryForm(values) {
   // console.log(values);

   // if (selectedCategory.value) {
   //    values.parent_id = Number(selectedCategory.value.key);
   // }
   await CategoryRepo.store(null, values);
}
</script>
