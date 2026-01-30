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
            <!-- toggle -->
            <DisclosureButton
               :class="{
                  'drop-zone--active': node.droppable && isDragOver,
                  'drop-zone--passive': !node.droppable && isDragOver,
               }"
               class="tree-node__row text-sm font-medium w-full flex items-center gap-(--space-md)"
            >
               <ChevronDownIcon v-if="open && node.children?.length" class="size-4" />
               <ChevronRightIcon v-else-if="!open && node.children?.length" class="size-4" />
               <div v-else class="size-2 rounded-full bg-(--color-surface) border border-(--color-primary)" />
               <span>{{ node.title }}</span>
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
import { ChevronDownIcon, ChevronRightIcon } from "@heroicons/vue/24/outline";
import { ref } from "vue";
import { Disclosure, DisclosureButton, DisclosurePanel } from "@headlessui/vue";
import type { TreeNode } from "@shared/types";

const props = defineProps<{
   node: TreeNode;
}>();

const emit = defineEmits<{
   (e: "move", payload: { draggedId: string; targetId: string }): void;
}>();

const isDragOver = ref(false);

function onDragStart(e: DragEvent) {
   if (!props.node.draggable) {
      e.preventDefault();
      return;
   }
   e.dataTransfer?.setData("text/plain", props.node.id);
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
   if (!props.node.droppable) return (isDragOver.value = false);

   const draggedId = e.dataTransfer?.getData("text/plain");
   if (!draggedId) return;

   emit("move", {
      draggedId,
      targetId: props.node.id,
   });

   isDragOver.value = false;
}
</script>
