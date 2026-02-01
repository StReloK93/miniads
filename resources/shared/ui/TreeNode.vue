<template>
   <div class="tree-node">
      <Disclosure v-slot="{ open }">
         <div
            :draggable="node.draggable"
            @dragstart="onDragStart"
            @dragover="onDragOver"
            @dragleave="onDragLeave"
            @drop="onDrop"
         >
            <DisclosureButton
               :class="{
                  'drop-zone--active': node.droppable && isDragOver,
                  'drop-zone--passive': !node.droppable && isDragOver,
               }"
               class="tree-node__row text-sm font-medium w-full flex border border-(--color-border) items-center gap-(--space-md)"
            >
               <FolderIcon class="size-4" v-if="node.is_page == false" />
               <DocumentIcon class="size-4" v-else />
               <ChevronDownIcon v-if="open && node.children?.length" class="size-4" />
               <ChevronRightIcon v-else-if="!open && node.children?.length" class="size-4" />
               <div v-else class="size-2 rounded-full bg-(--color-surface) border border-(--color-primary)" />
               <span class="flex items-center gap-2"> {{ node.title }}</span>
            </DisclosureButton>
         </div>

         <DisclosurePanel v-if="node.children?.length">
            <div class="tree-node__children">
               <TreeNode v-for="child in node.children" :key="child.id" :node="child" @move="emit('move', $event)" />
            </div>
         </DisclosurePanel>
      </Disclosure>
   </div>
</template>
<script setup lang="ts">
import { ChevronDownIcon, ChevronRightIcon, DocumentIcon, FolderIcon } from "@heroicons/vue/24/outline";
import { ref } from "vue";
import { Disclosure, DisclosureButton, DisclosurePanel } from "@headlessui/vue";
import type { ITreeNode } from "@shared/types";

const props = defineProps<{
   node: ITreeNode;
}>();

const emit = defineEmits<{
   (e: "move", payload: { draggedId: number; targetId: number }): void;
}>();

const isDragOver = ref(false);

function onDragStart(e: DragEvent) {
   if (!props.node.draggable) {
      e.preventDefault();
      return;
   }
   e.dataTransfer?.setData("text/plain", String(props.node.id));
}

function onDragOver(e: DragEvent) {
   e.preventDefault();
   e.stopPropagation();
   isDragOver.value = true;
   if (!props.node.droppable) return;
}

function onDragLeave() {
   isDragOver.value = false;
}

function onDrop(e: DragEvent) {
   e.preventDefault();
   e.stopPropagation();
   if (props.node.droppable == false) return (isDragOver.value = false);

   const draggedId = Number(e.dataTransfer?.getData("text/plain"));

   if (isNaN(draggedId)) return;

   emit("move", {
      draggedId,
      targetId: props.node.id,
   });

   isDragOver.value = false;
}
</script>
