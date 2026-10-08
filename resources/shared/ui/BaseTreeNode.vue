<template>
   <div>
      <div
         class="group flex min-h-11 items-center gap-2 rounded-xl border border-(--z-border) bg-(--z-card) py-1 pr-2.5 transition-all select-none cursor-pointer hover:bg-(--z-muted) hover:border-(--z-primary)/30"
         :class="{
            'bg-(--z-primary)/8! border-(--z-primary)!': node.key === selectedKey || node.key === selectedParentKey,
            'border-(--z-primary)! bg-(--z-primary)/12! ring-2 ring-(--z-primary)/30': isDropTarget,
         }"
         :style="{ paddingLeft: `${10 + depth * 20}px` }"
         @click="onRowClick"
         @dragover.prevent="isDropTarget = !node.is_page"
         @dragleave="isDropTarget = false"
         @drop.stop.prevent="dropOnNode"
      >
         <!-- Drag Handle -->
         <span
            class="inline-grid h-6 w-4.5 place-items-center text-(--z-muted-text) cursor-grab opacity-40 transition-opacity hover:opacity-100 group-hover:opacity-100"
            draggable="true"
            title="Kategoriyani surish uchun bosing va torting"
            @dragstart="onDragStart"
            @click.stop
         >
            <GripVertical class="size-3.5" />
         </span>

         <!-- Expand/Collapse toggle -->
         <button
            class="grid size-6 shrink-0 place-items-center rounded-md text-(--z-muted-text) transition-colors hover:text-(--z-foreground) cursor-pointer"
            type="button"
            :aria-label="expanded ? 'Yig\'ish' : 'Ochish'"
            @click.stop="toggleExpand"
         >
            <ChevronDown v-if="expanded && node.children?.length" class="size-4 transition-transform duration-150" />
            <ChevronRight v-else-if="node.children?.length" class="size-4 transition-transform duration-150" />
            <span v-else class="size-4 inline-block" />
         </button>

         <!-- Category Icon -->
         <div
            class="grid size-7 shrink-0 place-items-center rounded-lg"
            :class="node.is_page ? 'bg-blue-500/15 text-blue-500' : 'bg-(--z-muted) text-(--z-primary)'"
         >
            <component :is="node.is_page ? FileText : Folder" class="size-4" />
         </div>

         <!-- Title and Badges -->
         <div class="flex min-w-0 flex-1 items-center gap-2">
            <span class="truncate text-xs font-semibold text-(--z-foreground)">{{ node.label }}</span>
            <span v-if="node.is_page" class="rounded-md bg-blue-500/12 px-1.5 py-0.5 text-[10px] font-semibold text-blue-500">
               {{ node.listing_duration_days ? `${node.listing_duration_days} kun` : "E'lon sahifasi" }}
            </span>
            <span v-else-if="node.children?.length" class="rounded-md bg-(--z-muted) px-1.5 py-0.5 text-[10px] font-semibold text-(--z-muted-text)">
               {{ node.children.length }} ta
            </span>
         </div>

         <!-- Action Buttons (Strictly using BaseButton from Shared UI) -->
         <div class="ml-auto flex items-center gap-1.5 opacity-85 transition-opacity group-hover:opacity-100" @click.stop>
            <!-- Parameters button (Only for pages) -->
            <BaseButton
               v-if="node.is_page"
               size="xs"
               severity="secondary"
               icon-only
               title="Parametrlarni boshqarish"
               @click.stop="emit('parameters', node)"
            >
               <template #icon>
                  <ListFilter class="size-3.5" />
               </template>
            </BaseButton>

            <!-- Add child category button (Only for folders) -->
            <BaseButton
               v-else
               size="xs"
               severity="secondary"
               icon-only
               title="Ichki kategoriya qo'shish"
               @click.stop="emit('create', node)"
            >
               <template #icon>
                  <Plus class="size-3.5" />
               </template>
            </BaseButton>

            <!-- Edit button -->
            <BaseButton
               size="xs"
               severity="secondary"
               icon-only
               title="Tahrirlash"
               @click.stop="emit('edit', node)"
            >
               <template #icon>
                  <Pencil class="size-3.5" />
               </template>
            </BaseButton>

            <!-- Delete button -->
            <BaseButton
               size="xs"
               severity="danger"
               icon-only
               title="O'chirish"
               @click.stop="emit('delete', node)"
            >
               <template #icon>
                  <Trash2 class="size-3.5" />
               </template>
            </BaseButton>
         </div>
      </div>

      <!-- Recursive Children -->
      <div v-if="expanded && node.children?.length" class="grid gap-1.25 mt-1.25">
         <BaseTreeNode
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

defineOptions({
   name: "BaseTreeNode",
});
import {
   ChevronDown,
   ChevronRight,
   FileText,
   Folder,
   GripVertical,
   ListFilter,
   Pencil,
   Plus,
   Trash2,
} from "lucide-vue-next";
import BaseButton from "@shared/ui/BaseButton.vue";
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

function toggleExpand() {
   if (props.node.children?.length) {
      expanded.value = !expanded.value;
   }
}

function onRowClick() {
   if (props.node.children?.length) {
      expanded.value = !expanded.value;
   } else {
      emit("edit", props.node);
   }
}

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
