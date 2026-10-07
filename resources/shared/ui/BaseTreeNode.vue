<template>
   <div class="ui-tree-node">
      <div
         class="ui-tree-row"
         :class="{
            'is-selected': node.key === selectedKey || node.key === selectedParentKey,
            'is-drop-target': isDropTarget,
         }"
         :style="{ '--tree-depth': depth }"
         draggable="true"
         @dragstart="onDragStart"
         @dragover.prevent="isDropTarget = !node.is_page"
         @dragleave="isDropTarget = false"
         @drop.stop.prevent="dropOnNode"
      >
         <button class="ui-tree-expand" type="button" :aria-label="expanded ? 'Yig‘ish' : 'Ochish'" @click="expanded = !expanded">
            <ChevronDown v-if="expanded && node.children?.length" class="size-4" />
            <ChevronRight v-else-if="node.children?.length" class="size-4" />
            <span v-else class="size-4" />
         </button>
         <component :is="node.is_page ? FileText : Folder" class="ui-tree-icon size-4" />
         <span class="ui-tree-label">{{ node.label }}</span>
         <span v-if="node.is_page" class="ui-tree-meta">{{ node.listing_duration_days }} kun</span>
         <div class="ui-tree-actions">
            <button
               v-if="node.is_page"
               class="ui-tree-action"
               type="button"
               title="Parametrlarni boshqarish"
               aria-label="Parametrlarni boshqarish"
               @click.stop="emit('parameters', node)"
            ><ListFilter class="size-4" /></button>
            <button
               v-else
               class="ui-tree-action"
               type="button"
               title="Ichki kategoriya qo‘shish"
               aria-label="Ichki kategoriya qo‘shish"
               @click.stop="emit('create', node)"
            ><Plus class="size-4" /></button>
            <button class="ui-tree-action" type="button" title="Tahrirlash" aria-label="Tahrirlash" @click.stop="emit('edit', node)">
               <Pencil class="size-4" />
            </button>
            <button class="ui-tree-action is-danger" type="button" title="O‘chirish" aria-label="O‘chirish" @click.stop="emit('delete', node)">
               <Trash2 class="size-4" />
            </button>
         </div>
      </div>
      <div v-if="expanded && node.children?.length" class="ui-tree-children">
         <TreeNode
            v-for="child in node.children"
            :key="String(child.key)"
            :node="child"
            :depth="depth + 1"
            :selected-key="selectedKey"
            :selected-parent-key="selectedParentKey"
            @create="emit('create', $event)"
            @parameters="emit('parameters', $event)"
            @edit="emit('edit', $event)"
            @delete="emit('delete', $event)"
            @move="emit('move', $event)"
         />
      </div>
   </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { ChevronDown, ChevronRight, FileText, Folder, ListFilter, Pencil, Plus, Trash2 } from "lucide-vue-next";
import type { TreeNodeData } from "@shared/ui/BaseTree.vue";

const props = defineProps<{
   node: TreeNodeData;
   depth: number;
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

const expanded = ref(true);
const isDropTarget = ref(false);

function onDragStart(event: DragEvent) {
   event.dataTransfer?.setData("application/x-miniads-category", JSON.stringify(props.node));
   if (event.dataTransfer) event.dataTransfer.effectAllowed = "move";
}

function dropOnNode(event: DragEvent) {
   isDropTarget.value = false;
   const payload = event.dataTransfer?.getData("application/x-miniads-category");
   if (!payload || props.node.is_page) return;

   const draggedNode = JSON.parse(payload) as TreeNodeData;
   if (draggedNode.key !== props.node.key) {
      emit("move", { node: draggedNode, parent: props.node });
   }
}
</script>

<style scoped>
.ui-tree-row {
   display: flex;
   min-height: 42px;
   align-items: center;
   gap: 8px;
   border: 1px solid transparent;
   border-radius: 9px;
   padding: 3px 7px 3px calc(7px + var(--tree-depth) * 20px);
   cursor: grab;
   transition: background 0.15s ease;
}

.ui-tree-row:hover,
.ui-tree-row.is-selected {
   background: var(--z-muted);
}

.ui-tree-row.is-drop-target {
   border-color: var(--z-primary);
   background: color-mix(in srgb, var(--z-primary) 8%, var(--z-card));
}

.ui-tree-expand,
.ui-tree-action {
   display: grid;
   width: 29px;
   height: 29px;
   flex: 0 0 auto;
   place-items: center;
   border: 0;
   border-radius: 8px;
   background: transparent;
   color: var(--z-muted-text);
   cursor: pointer;
}

.ui-tree-action:hover {
   background: color-mix(in srgb, var(--z-primary) 9%, transparent);
   color: var(--z-foreground);
}

.ui-tree-action.is-danger:hover {
   background: color-mix(in srgb, var(--z-danger) 10%, transparent);
   color: var(--z-danger);
}

.ui-tree-icon {
   flex: 0 0 auto;
   color: var(--z-muted-text);
}

.ui-tree-label {
   overflow: hidden;
   color: var(--z-foreground);
   font-size: 12px;
   font-weight: 600;
   text-overflow: ellipsis;
   white-space: nowrap;
}

.ui-tree-meta {
   color: var(--z-muted-text);
   font-size: 10px;
}

.ui-tree-actions {
   display: flex;
   margin-left: auto;
   opacity: 0.5;
   transition: opacity 0.15s ease;
}

.ui-tree-row:hover .ui-tree-actions,
.ui-tree-row:focus-within .ui-tree-actions {
   opacity: 1;
}

.ui-tree-children {
   display: grid;
   gap: 4px;
}
</style>
