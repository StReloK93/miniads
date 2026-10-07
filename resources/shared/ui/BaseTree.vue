<template>
   <div class="ui-tree" @dragover.prevent @drop.prevent="dropAtRoot($event)">
      <p v-if="nodes.length === 0" class="ui-tree-empty">Kategoriyalar hali qo‘shilmagan.</p>
      <TreeNode
         v-for="node in nodes"
         :key="String(node.key)"
         :node="node"
         :depth="0"
         :selected-key="selectedKey"
         :selected-parent-key="selectedParentKey"
         @create="emit('create', $event)"
         @parameters="emit('parameters', $event)"
         @edit="emit('edit', $event)"
         @delete="emit('delete', $event)"
         @move="emit('move', $event)"
      />
   </div>
</template>

<script setup lang="ts">
import TreeNode from "@shared/ui/BaseTreeNode.vue";

export type TreeNodeData = {
   key: string | number;
   label: string;
   children?: TreeNodeData[];
   is_page?: boolean;
   listing_duration_days?: number;
   image?: string;
};

defineProps<{
   nodes: TreeNodeData[];
   selectedKey?: string | number | null;
   selectedParentKey?: string | number | null;
}>();
const emit = defineEmits<{
   (event: "create", node: TreeNodeData): void;
   (event: "parameters", node: TreeNodeData): void;
   (event: "edit", node: TreeNodeData): void;
   (event: "delete", node: TreeNodeData): void;
   (event: "move", payload: { node: TreeNodeData; parent: TreeNodeData | null }): void;
}>();

function dropAtRoot(event: DragEvent) {
   const payload = event.dataTransfer?.getData("application/x-miniads-category");
   if (payload) {
      emit("move", { node: JSON.parse(payload) as TreeNodeData, parent: null });
   }
}
</script>

<style scoped>
.ui-tree {
   display: grid;
   gap: 5px;
   border: 1px solid var(--z-border);
   border-radius: 14px;
   background: var(--z-card);
   padding: 12px;
}

.ui-tree-empty {
   margin: 0;
   padding: 25px;
   color: var(--z-muted-text);
   font-size: 13px;
   text-align: center;
}
</style>
