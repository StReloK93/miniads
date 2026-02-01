<script setup lang="ts">
import TreeNode from "@shared/ui/TreeNode.vue";
import type { ITreeNode as Node } from "@shared/types";
import { ref, toRaw } from "vue";

const props = defineProps<{
   modelValue: Node[];
}>();

const hoverRoot = ref(false);
function onDragLeave() {
   hoverRoot.value = false;
}
const emit = defineEmits<{
   (e: "update:modelValue", value: Node[]): void;
   (e: "change", value: { draggedId: number; targetId: number | null }): void;
}>();

function removeNode(nodes: Node[], id: number): Node | null {
   for (let i = 0; i < nodes.length; i++) {
      if (nodes[i].id === id) {
         return nodes.splice(i, 1)[0];
      }
      if (nodes[i].children) {
         const found = removeNode(nodes[i].children!, id);
         if (found) return found;
      }
   }
   return null;
}

function insertNode(nodes: Node[], targetId: number, node: Node): boolean {
   for (const n of nodes) {
      if (n.id === targetId) {
         n.children ||= [];
         n.children.push(node);
         return true;
      }
      if (n.children && insertNode(n.children, targetId, node)) {
         return true;
      }
   }
   return false;
}

function onMove(payload: { draggedId: number; targetId: number | null }) {
   if (payload.draggedId === payload.targetId) return;
   const raw = toRaw(props.modelValue);

   // ❌ o‘z farzandiga tashlashni block qilamiz
   if (payload.targetId && isDescendant(raw, payload.draggedId, payload.targetId)) {
      return;
   }

   const copy = structuredClone(raw);

   const dragged = removeNode(copy, payload.draggedId);
   if (!dragged) return;

   if (payload.targetId === null) {
      copy.push(dragged);
   } else {
      insertNode(copy, payload.targetId, dragged);
   }

   emit("update:modelValue", copy);
   emit("change", payload);
}

function onRootDrop(e: DragEvent) {
   e.preventDefault();
   const draggedId = Number(e.dataTransfer?.getData("text/plain"));

   if (isNaN(draggedId)) return;
   hoverRoot.value = false;

   onMove({ draggedId, targetId: null });
}

function onRootDragOver(e: DragEvent) {
   e.preventDefault();
   if (hoverRoot.value) return;
   hoverRoot.value = true;
}

function isDescendant(nodes: Node[], draggedId: number, targetId: number): boolean {
   for (const node of nodes) {
      if (node.id === draggedId) {
         return contains(node.children ?? [], targetId);
      }

      if (node.children && isDescendant(node.children, draggedId, targetId)) {
         return true;
      }
   }
   return false;
}

function contains(nodes: Node[], searchId: number): boolean {
   for (const node of nodes) {
      if (node.id === searchId) return true;
      if (node.children && contains(node.children, searchId)) return true;
   }
   return false;
}
</script>

<template>
   <div
      class="tree"
      :class="{
         'drop-zone--active': hoverRoot,
      }"
      @dragover="onRootDragOver"
      @drop="onRootDrop"
      @dragleave="onDragLeave"
   >
      <TreeNode v-for="node in modelValue" :key="node.id" :node="node" @move="onMove" />
   </div>
</template>
