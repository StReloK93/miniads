<template>
   <section class="admin-page">
      <BaseDrawer
         :open="pageData.drawerToggle"
         :title="pageData.title"
         @close="pageData.drawerToggle = false"
      >
         <BaseForm
            :key="formKey"
            :submit="submit"
            :input-configs="inputConfigs"
            @close="pageData.drawerToggle = false"
         >
            <template #inputs>
               <div v-if="pageData.selectedParent" class="admin-parent-hint">
                  <Folder class="size-4" />
                  <span>Ichki kategoriya: <strong>{{ pageData.selectedParent.label }}</strong></span>
               </div>
            </template>
         </BaseForm>
      </BaseDrawer>

      <BaseModal
         :open="pageData.selectedCategory !== null"
         :title="`${pageData.selectedCategory?.label || 'Kategoriya'} parametrlari`"
         description="E'lon formasida ishlatiladigan parametrlarni tanlang va tartiblang."
         :show-buttons="false"
         @close="pageData.selectedCategory = null"
      >
         <template #icon><ListFilter class="size-5" /></template>
         <CategoryParameterDialog
            v-if="pageData.selectedCategory"
            :key="String(pageData.selectedCategory.key)"
            :category="pageData.selectedCategory"
            @close="pageData.selectedCategory = null"
         />
      </BaseModal>

      <BaseModal
         :open="categoryToDelete !== null"
         title="Kategoriyani o'chirish"
         description="Ushbu kategoriya katalogdan olib tashlanadi."
         confirm-text="O'chirish"
         danger
         @close="categoryToDelete = null"
         @confirm="deleteCategory"
      >
         <template #icon><TriangleAlert class="size-5 text-(--z-danger)" /></template>
         <p class="admin-confirm-copy">“{{ categoryToDelete?.label }}” kategoriyasini o'chirmoqchimisiz?</p>
      </BaseModal>

      <header class="admin-page-heading">
         <div>
            <h1>Kategoriyalar</h1>
            <p class="admin-muted">Ichki bo'limlar, e'lon sahifalari va ularning parametrlarini boshqaring.</p>
         </div>
         <BaseButton @click="openCreateForm()" size="sm">
            <template #icon><Plus class="size-4" /></template>
            Kategoriya qo'shish
         </BaseButton>
      </header>

      <p v-if="error" class="admin-alert admin-alert-error">{{ error }}</p>
      <p v-if="loading" class="admin-panel admin-loading">Kategoriyalar yuklanmoqda…</p>
      <BaseTree
         v-else
         :nodes="convertTreeNode"
         :selected-key="pageData.selectedForUpdate"
         :selected-parent-key="pageData.selectedParent?.key"
         @create="openCreateForm"
         @parameters="openParameterModal"
         @edit="openEditForm"
         @delete="categoryToDelete = $event"
         @move="onNodeDrop"
      />
   </section>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref, shallowRef, watch } from "vue";
import { Folder, ListFilter, Plus, TriangleAlert } from "lucide-vue-next";
import BaseButton from "@shared/ui/BaseButton.vue";
import BaseDrawer from "@shared/ui/BaseDrawer.vue";
import BaseModal from "@shared/ui/BaseModal.vue";
import BaseTree from "@shared/ui/BaseTree.vue";
import type { TreeNodeData } from "@shared/ui/BaseTree.vue";
import BaseForm from "@admin/components/BaseForm.vue";
import CategoryRepo from "@shared/entities/Category/CategoryRepo";
import CategoryParameterDialog from "@admin/components/CategoryParameterDialog.vue";
import { categoryInputs } from "@shared/entities/Category/CategoryInputs";
import { findParentId, formatCategories } from "@admin/modules/Helpers";
import { useFetchDecorator } from "@shared/composables/useFetch";

const { data: parentCategories, execute: fetchCategories, isLoading: loading } = useFetchDecorator<any[]>(CategoryRepo.parents);
const error = ref("");
const categoryToDelete = ref<TreeNodeData | null>(null);
const formKey = ref(0);
const convertTreeNode = ref<TreeNodeData[]>([]);
watch(
   () => parentCategories.value,
   () => {
      convertTreeNode.value = formatCategories(parentCategories.value || []);
   },
);

const pageData = reactive<{
   drawerToggle: boolean;
   title: string;
   selectedForUpdate: string | number | null;
   selectedParent: TreeNodeData | null;
   selectedCategory: TreeNodeData | null;
}>({
   drawerToggle: false,
   title: "",
   selectedForUpdate: null,
   selectedParent: null,
   selectedCategory: null,
});

const inputConfigs = shallowRef(categoryInputs);
const submit = ref<(values: Record<string, unknown>) => Promise<void>>(async () => {
   throw new Error("Kategoriya formasi saqlashga tayyor emas.");
});

function resetInputs() {
   inputConfigs.value.forEach((input) => {
      input.value = undefined;
   });
   formKey.value += 1;
}

async function openCreateForm(parent: TreeNodeData | null = null) {
   error.value = "";
   pageData.title = parent ? "Ichki kategoriya qo'shish" : "Yangi kategoriya qo'shish";
   pageData.selectedParent = parent;
   pageData.selectedForUpdate = null;
   resetInputs();
   submit.value = async (values) => {
      await CategoryRepo.store(parent?.key ?? null, values as { name: string; image?: File | string });
      await fetchCategories();
   };
   pageData.drawerToggle = true;
}

async function openEditForm(node: TreeNodeData) {
   error.value = "";
   pageData.selectedForUpdate = node.key;
   pageData.selectedParent = null;
   pageData.title = "Kategoriyani tahrirlash";
   try {
      const { data: category } = await CategoryRepo.show(node.key);
      inputConfigs.value.forEach((input) => {
         input.value = category[input.name];
      });
      formKey.value += 1;
      submit.value = async (values) => {
         await CategoryRepo.update(String(node.key), values as { name: string; image?: File | string });
         await fetchCategories();
      };
      pageData.drawerToggle = true;
   } catch (exception) {
      console.error("Kategoriya ma'lumotini yuklab bo'lmadi.", exception);
      error.value = "Kategoriyani tahrirlash uchun ma'lumotni yuklab bo'lmadi.";
   }
}

async function onNodeDrop(payload: { node: TreeNodeData; parent: TreeNodeData | null }) {
   const newParentId = payload.parent?.key ?? null;
   if (payload.node.key === newParentId) return;
   if (payload.parent && containsCategory(payload.node, payload.parent.key)) {
      error.value = "Kategoriyani uning ichki bo'limi ichiga ko'chirib bo'lmaydi.";
      return;
   }

   const currentParent = findParentId(convertTreeNode.value, payload.node.key);
   if (currentParent?.key === newParentId || (!currentParent && newParentId === null)) return;

   error.value = "";
   try {
      await CategoryRepo.changeParent(Number(payload.node.key), newParentId);
      await fetchCategories();
   } catch (exception) {
      console.error("Kategoriya joylashuvini yangilab bo'lmadi.", exception);
      error.value = "Kategoriya joylashuvini o'zgartirib bo'lmadi.";
      await fetchCategories();
   }
}

function containsCategory(node: TreeNodeData, targetKey: string | number): boolean {
   return node.key === targetKey || Boolean(node.children?.some((child) => containsCategory(child, targetKey)));
}

async function deleteCategory() {
   if (!categoryToDelete.value) return;
   const id = categoryToDelete.value.key;
   categoryToDelete.value = null;
   try {
      await CategoryRepo.delete(id);
      await fetchCategories();
   } catch (exception) {
      console.error("Kategoriyani o'chirib bo'lmadi.", exception);
      error.value = "Kategoriyani o'chirib bo'lmadi.";
   }
}

function openParameterModal(node: TreeNodeData) {
   pageData.selectedCategory = node;
}

onMounted(async () => {
   try {
      await fetchCategories();
   } catch (exception) {
      console.error("Kategoriyalarni yuklab bo'lmadi.", exception);
      error.value = "Kategoriyalarni yuklab bo'lmadi. Sahifani yangilab ko'ring.";
   }
});
</script>
