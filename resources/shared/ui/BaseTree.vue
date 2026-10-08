<template>
   <div
      class="grid gap-1.5 rounded-2xl border border-(--z-border) bg-(--z-card) p-3.5"
      @dragover.prevent
      @drop.prevent="dropAtRoot($event)"
   >
      <div v-if="nodes.length === 0" class="flex flex-col items-center justify-center py-10 px-5 text-center">
         <FolderTree class="size-8 text-(--z-muted-text) mb-2" />
         <p class="font-medium text-sm text-(--z-foreground)">Kategoriyalar hali mavjud emas</p>
         <span class="text-xs text-(--z-muted-text)">Yangi kategoriya qo'shish uchun yuqoridagi tugmani bosing</span>
      </div>
      <BaseTreeNode
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
import BaseTreeNode from "@shared/ui/BaseTreeNode.vue";
import { FolderTree } from "lucide-vue-next";

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
