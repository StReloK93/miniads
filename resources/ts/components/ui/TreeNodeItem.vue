<template>
   <main
      class="flex justify-between items-center select-none pl-3 pr-1 rounded-sm"
      :class="{
         'bg-sky-100':
            props.selectedForUpdate == props.node.key ||
            props.selectedParent?.key == props.node.key,
      }"
   >
      <div class="flex gap-3 items-center py-2">
         <img
            v-if="props.node.image"
            :src="props.node.image"
            draggable="false"
            class="w-6 pointer-events-none dark:invert"
         />
         <span class="font-medium"> {{ props.node.label }}</span>
      </div>

      <div class="flex items-center gap-1">
         <Button
            @click.stop="$emit('create', props.node)"
            icon="pi pi-plus"
            size="small"
            rounded
            variant="text"
         />
         <Button
            type="button"
            icon="pi pi-ellipsis-v"
            @click="toggle"
            rounded
            severity="secondary"
            size="small"
            variant="text"
            aria-haspopup="true"
            aria-controls="overlay_menu"
         />
         <Menu ref="menu" id="overlay_menu" :model="items" :popup="true" />
      </div>
   </main>
</template>

<script setup lang="ts">
import { TreeNode } from "primevue/treenode";
import { ref } from "vue";

const emit = defineEmits(["create", "edit"]);
const props = defineProps<{
   node: TreeNode;
   updateLoading: number | string | null;
   selectedForUpdate: number | string | null;
   selectedParent: TreeNode | null;
}>();

const menu = ref();
const toggle = (event) => {
   menu.value.toggle(event);
};

const items = ref([
   {
      label: "Amallar",
      items: [
         {
            label: "O'zgartirish",
            icon: "pi pi-pencil",
            command: () => emit("edit", props.node),
         },
         {
            label: "O'chirish",
            icon: "pi pi-trash",
         },
      ],
   },
]);
</script>
