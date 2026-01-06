<template>
   <main class="w-90 flex justify-between items-center select-none">
      <div class="flex gap-3 items-center py-2">
         <img
            v-if="props.node.image"
            :src="props.node.image"
            draggable="false"
            class="w-6 pointer-events-none"
         />
         <span class="font-medium">
            {{ props.node.label }}
         </span>
      </div>

      <div class="flex items-center gap-1">
         <Button
            @click.stop="$emit('create', props.node.key)"
            icon="pi pi-plus"
            severity="secondary"
            size="small"
            rounded
            variant="text"
         />
         <Button
            @click.stop="$emit('edit', props.node)"
            icon="pi pi-pencil"
            severity="secondary"
            size="small"
            :loading="props.updateLoading === props.node.key"
            rounded
            variant="text"
         />
         <Button icon="pi pi-trash" severity="danger" size="small" rounded variant="text" />
      </div>
   </main>
</template>

<script setup lang="ts">
import { TreeNode } from "primevue/treenode";
const props = defineProps<{
   node: TreeNode;
   updateLoading: number | string | null;
}>();
</script>
