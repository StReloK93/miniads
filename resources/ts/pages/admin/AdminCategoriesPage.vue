<template>
   <div>
      <div class="flex justify-center">
         <Drawer class="headless-drawer" v-model:visible="visible" :show-close-icon="false">
            <BaseForm
               @close="visible = false"
               :submit="submit"
               :inputConfigs="inputs"
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
               :loading="createLoadingId === '0'"
               @click="openCreateForm()"
            />
         </div>
      </div>
      <Tree :value="convertTreeNode" class="w-full md:w-120 bg-transparent!">
         <template #default="slotProps">
            <main class="w-90 flex justify-between items-center">
               <div class="flex gap-3 items-center py-2">
                  <img :src="slotProps.node.image" class="w-6" />
                  <span>
                     {{ slotProps.node.label }}
                  </span>
               </div>
               <main class="flex items-center">
                  <Button
                     @click="openCreateForm(slotProps.node.key)"
                     :loading="createLoadingId === slotProps.node.key"
                     icon="pi pi-plus"
                     severity="secondary"
                     size="small"
                     rounded
                     variant="text"
                  />
                  <Button
                     @click="openEditForm(slotProps.node.key)"
                     icon="pi pi-pencil"
                     severity="secondary"
                     size="small"
                     :loading="updateLoadingId === slotProps.node.key"
                     rounded
                     variant="text"
                  />
                  <Button
                     icon="pi pi-trash"
                     severity="danger"
                     size="small"
                     rounded
                     variant="text"
                  />
               </main>
            </main>
         </template>
      </Tree>
   </div>
</template>

<script setup lang="ts">
import BaseForm from "@/components/BaseForm.vue";
import CategoryRepo from "@/repositories/CategoryRepo";
import { computed, ref, shallowRef } from "vue";
import { categoryInputs } from "@/configs/ConfigInputs";
import { formatCategories } from "@/configs/Formatters";
const { data: categories } = CategoryRepo.parents();
const visible = ref(false);
const convertTreeNode = computed(() => formatCategories(categories.value || []));

const createLoadingId = ref<string | null>(null);
const updateLoadingId = ref<string | null>(null);
const inputs = shallowRef(categoryInputs);

var submit: (values: unknown) => Promise<void>;

const openCreateForm = async function (parent_id: null | string = null) {
   submit = async (values) => await createCategory(values);
   createLoadingId.value = parent_id == null ? "0" : parent_id;

   if (parent_id != null) {
      inputs.value = categoryInputs.filter((input) => input.name !== "image");
   } else {
      inputs.value = categoryInputs.filter((input) => input.name !== "parent_id");
   }

   await Promise.all(
      inputs.value.map(async (input) => {
         if (input.generateProps) await input.generateProps();
         if (input.name !== "parent_id") return (input.value = undefined);
         input.value = parent_id;
         return input;
      }),
   ).finally(() => {
      createLoadingId.value = null;
      visible.value = true;
   });
};

async function openEditForm(id: string) {
   updateLoadingId.value = id;
   submit = async (values) => await updateCategory(id, values);

   CategoryRepo.show(id, ({ data }) => {
      inputs.value.map(async (input) => {
         if (input.generateProps) await input.generateProps();
         input.value = data[input.name];
         return input;
      });

      updateLoadingId.value = null;
      visible.value = true;
   });
}

async function createCategory(values) {
   await CategoryRepo.store(null, values);
}

async function updateCategory(id: string, values) {
   await CategoryRepo.update(id, values);
}
</script>
