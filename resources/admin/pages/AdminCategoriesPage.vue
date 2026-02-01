<template>
   <section>
      <BaseButton severity="secondary" class="mb-4">Yangi</BaseButton>
      <TreeView v-model="tree" @change="changeTree" />
      <BaseDialog v-model="open"> </BaseDialog>

      <BaseDialog v-model="open">
         <template #title> Kategoriya qo‘shish </template>

         <main class="h-140 w-120"></main>
      </BaseDialog>
      <BaseForm :submit="submitForm" @close="open = false" :input-configs="categoryInputs" />
      <BaseButton @click="open = true" class="mt-4">Test Dialog</BaseButton>
   </section>
</template>

<script setup lang="ts">
import BaseForm from "@shared/ui/BaseForm.vue";

import CategoryRepo from "@/entities/Category/CategoryRepo";
import { useFetchDecorator } from "@/modules/useFetch";
import { ITreeNode } from "@shared/types";
import { ref, onMounted, watch } from "vue";
import { formatCategories } from "@/modules/Formatters";
import { categoryInputs } from "@/entities/Category/CategoryInputs";
const { data: parentCategories, execute: fetchCategories } = useFetchDecorator(CategoryRepo.parents);

function changeTree(value: { draggedId: number; targetId: number | null }) {
   CategoryRepo.changeParent(value.draggedId, value.targetId);
}

const open = ref(false);

async function submitForm(values: any) {
   console.log(values, "submit qilindi");

   // formni submit qilish
}
// function newTreeItem() {
//    tree.value.push({
//       id: Math.random().toString(),
//       title: "sadsad",
//       droppable: true,
//       draggable: true,
//    });
// }

const tree = ref<ITreeNode[]>([]);
watch(
   () => parentCategories.value,
   () => {
      tree.value = formatCategories(parentCategories.value || []);
   },
);

onMounted(() => {
   fetchCategories();
});
</script>

<!--

<script setup lang="ts">
import CategoryRepo from "@/entities/Category/CategoryRepo";
import { computed, onMounted, reactive, ref, shallowRef, watch } from "vue";
import { categoryInputs } from "@/entities/Category/CategoryInputs";
import { findParentId } from "@/modules/Helpers";
import CategoryParameterDialog from "@/entities/CategoryParameter/CategoryParameterDialog.vue";
import { formatCategories } from "@/modules/Formatters";
import { useFetchDecorator } from "@/modules/useFetch";

const { data: parentCategories, execute: fetchCategories } = useFetchDecorator(CategoryRepo.parents);
var submit: (values: any) => Promise<void>;

const convertTreeNode = ref([]);
watch(
   () => parentCategories.value,
   () => {
      convertTreeNode.value = formatCategories(parentCategories.value || []);
   },
);
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
</script> -->
