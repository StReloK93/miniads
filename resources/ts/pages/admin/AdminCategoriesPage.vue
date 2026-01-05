<template>
   <div>
      <div class="flex justify-center">
         <Drawer class="headless-drawer" v-model:visible="visible" :show-close-icon="false">
            <template #header>
               <h3
                  class="m-0 px-5 py-1.5 text-center text-xl font-semibold w-full border-b border-surface-200 mb-2 uppercase bg-surface-100"
               >
                  {{ title }}
               </h3>
            </template>
            <BaseForm @close="visible = false" :submit="submit" :inputConfigs="inputs" />
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
      <Tree
         :value="convertTreeNode"
         draggableNodes
         droppableNodes
         @node-drop="onNodeDrop"
         class="w-full md:w-120 bg-transparent!"
      >
         <template #default="slotProps">
            <main class="w-90 flex justify-between items-center select-none">
               <div class="flex gap-3 items-center py-2">
                  <img
                     v-if="slotProps.node.image"
                     :src="slotProps.node.image"
                     draggable="false"
                     class="w-6 pointer-events-none"
                  />
                  <span class="font-medium">
                     {{ slotProps.node.label }}
                  </span>
               </div>

               <div class="flex items-center gap-1">
                  <Button
                     @click.stop="openCreateForm(slotProps.node.key)"
                     :loading="createLoadingId === slotProps.node.key"
                     icon="pi pi-plus"
                     severity="secondary"
                     size="small"
                     rounded
                     variant="text"
                  />
                  <Button
                     @click.stop="openEditForm(slotProps.node)"
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
               </div>
            </main>
         </template>
      </Tree>
      <Tree
         v-model:value="databases"
         draggableNodes
         droppableNodes
         class="w-full md:w-120 bg-transparent!"
      >
      </Tree>
   </div>
</template>

<script setup lang="ts">
import BaseForm from "@/components/BaseForm.vue";
import CategoryRepo from "@/repositories/CategoryRepo";
import { computed, ref, shallowRef } from "vue";
import { categoryInputs } from "@/configs/ConfigInputs";
import { formatCategories } from "@/configs/Formatters";
const { data: categories, fetchData: fetchCategories } = CategoryRepo.parents();
const visible = ref(false);
const convertTreeNode = computed(() => formatCategories(categories.value || []));

const createLoadingId = ref<string | null>(null);
const updateLoadingId = ref<string | null>(null);
const inputs = shallowRef(categoryInputs);

var submit: (values: unknown) => Promise<void>;
const text = {
   add: "Yangi kategoriya qo'shish",
   edit: "Kategoriyani tahrirlash",
};

const title = ref("");
const openCreateForm = async function (parent_id: null | string = null) {
   submit = async (values) => await createCategory(values);
   title.value = text.add;
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

async function openEditForm(node: any) {
   if (node.parent_id != null) {
      inputs.value = categoryInputs.filter((input) => input.name !== "image");
   } else {
      inputs.value = categoryInputs.filter((input) => input.name !== "parent_id");
   }
   updateLoadingId.value = node.key;
   title.value = text.edit;

   submit = async (values) => await updateCategory(node.key, values);

   CategoryRepo.show(node.key, ({ data }) => {
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
   fetchCategories();
}

async function updateCategory(id: string, values) {
   await CategoryRepo.update(id, values);
   fetchCategories();
}

// Drag and Drop hodisasi sodir bo'lganda
const onNodeDrop = (event) => {
   // PrimeVue avtomatik ravishda categories.value ni yangilaydi
   // event.value - bu yangilangan to'liq daraxt strukturasi
   categories.value = event.value;

   console.log("Yangi struktura:", categories.value);

   // Bu yerda backend-ga (Laravel) yangi strukturani yuborish kerak
   saveStructureToBackend(categories.value);
};

const saveStructureToBackend = async (data) => {
   try {
      // Laravel-ga hamma strukturani yoki faqat o'zgarganini yuboring
      // await axios.post('/api/categories/reorder', { tree: data });
      console.log("Backend-ga saqlandi");
   } catch (error) {
      console.error("Xatolik:", error);
   }
};

const databases = ref([
   {
      key: "0",
      label: ".github",
      data: ".github folder",
      icon: "pi pi-fw pi-folder",
      children: [
         {
            key: "0-0",
            label: "workflows",
            data: "workflows folder",
            icon: "pi pi-fw pi-folder",
            children: [
               {
                  key: "0-0-0",
                  label: "node.js.yml",
                  data: "node.js.yml file",
                  icon: "pi pi-fw pi-file",
               },
            ],
         },
      ],
   },
   {
      key: "1",
      label: ".vscode",
      data: ".vscode folder",
      icon: "pi pi-fw pi-folder",
      children: [
         {
            key: "1-0",
            label: "extensions.json",
            data: "extensions.json file",
            icon: "pi pi-fw pi-file",
         },
      ],
   },
   {
      key: "2",
      label: "public",
      data: "public folder",
      icon: "pi pi-fw pi-folder",
      children: [
         {
            key: "2-0",
            label: "vite.svg",
            data: "vite.svg file",
            icon: "pi pi-fw pi-file",
         },
      ],
   },
   {
      key: "3",
      label: "src",
      data: "src folder",
      icon: "pi pi-fw pi-folder",
      children: [
         {
            key: "3-0",
            label: "assets",
            data: "assets folder",
            icon: "pi pi-fw pi-folder",
            children: [
               {
                  key: "3-0-0",
                  label: "vue.svg",
                  data: "vue.svg file",
                  icon: "pi pi-fw pi-file",
               },
            ],
         },
         {
            key: "3-1",
            label: "components",
            data: "components folder",
            icon: "pi pi-fw pi-folder",
            children: [
               {
                  key: "3-1-0",
                  label: "HelloWorld.vue",
                  data: "HelloWorld.vue file",
                  icon: "pi pi-fw pi-file",
               },
            ],
         },
         {
            key: "3-2",
            label: "App.vue",
            data: "App.vue file",
            icon: "pi pi-fw pi-file",
         },
         {
            key: "3-3",
            label: "main.js",
            data: "main.js file",
            icon: "pi pi-fw pi-file",
         },
         {
            key: "3-4",
            label: "style.css",
            data: "style.css file",
            icon: "pi pi-fw pi-file",
         },
      ],
   },
   {
      key: "4",
      label: "index.html",
      data: "index.html file",
      icon: "pi pi-fw pi-file",
   },
   {
      key: "5",
      label: "package.json",
      data: "package.json file",
      icon: "pi pi-fw pi-file",
   },
   {
      key: "6",
      label: "vite.config.js",
      data: "vite.config.js file",
      icon: "pi pi-fw pi-file",
   },
]);
</script>
