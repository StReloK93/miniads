<template>
   <div class="admin-table-wrap">
      <table class="admin-table">
         <thead>
            <tr>
               <th v-for="column in columns" :key="column.field">{{ column.header }}</th>
               <th class="admin-actions-heading">Amallar</th>
            </tr>
         </thead>
         <tbody>
            <tr v-for="row in parameters" :key="row.id">
               <td v-for="column in columns" :key="column.field">
                  <div v-if="column.formatter" class="admin-chip-list">
                     <span v-for="(value, index) in column.formatter(row[column.field])" :key="`${value}-${index}`" class="admin-chip">
                        {{ value }}
                     </span>
                     <span v-if="!column.formatter(row[column.field])?.length" class="admin-cell-subtitle">—</span>
                  </div>
                  <span v-else>{{ row[column.field] ?? "—" }}</span>
               </td>
               <td class="admin-actions">
                  <button class="admin-icon-button" type="button" :aria-label="`${row.id} ni tahrirlash`" title="Tahrirlash" @click="emit('edit', row.id)">
                     <Pencil class="size-4" />
                  </button>
                  <button class="admin-icon-button admin-danger" type="button" :aria-label="`${row.id} ni o‘chirish`" title="O‘chirish" @click="confirmDelete(row)">
                     <Trash2 class="size-4" />
                  </button>
               </td>
            </tr>
            <tr v-if="parameters.length === 0">
               <td :colspan="columns.length + 1" class="admin-empty-cell">Ma’lumot topilmadi.</td>
            </tr>
         </tbody>
      </table>

      <BaseModal
         :open="deleteTarget !== null"
         title="O‘chirishni tasdiqlang"
         description="O‘chirilgan ma’lumotdan foydalanib bo‘lmaydi."
         confirm-text="O‘chirish"
         cancel-text="Bekor qilish"
         danger
         @close="deleteTarget = null"
         @confirm="deleteConfirmed"
      >
         <template #icon><TriangleAlert class="size-5 text-(--z-danger)" /></template>
         <p class="admin-confirm-copy">Ushbu yozuvni o‘chirmoqchimisiz?</p>
      </BaseModal>
   </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { Pencil, Trash2, TriangleAlert } from "lucide-vue-next";
import BaseModal from "@shared/ui/BaseModal.vue";

const emit = defineEmits<{
   (event: "edit", id: string | number): void;
   (event: "delete", id: string | number): void;
}>();
defineProps<{
   parameters: Record<string, any>[];
   columns: { field: string; header: string; formatter?: (value: any) => any[] }[];
}>();
const deleteTarget = ref<Record<string, any> | null>(null);

function confirmDelete(row: Record<string, any>) {
   deleteTarget.value = row;
}

function deleteConfirmed() {
   if (deleteTarget.value) emit("delete", deleteTarget.value.id);
   deleteTarget.value = null;
}
</script>

<style scoped>
.admin-chip-list {
   display: flex;
   flex-wrap: wrap;
   gap: 4px;
}

.admin-chip {
   border-radius: 20px;
   background: var(--z-muted);
   padding: 3px 7px;
   color: var(--z-muted-text);
   font-size: 9px;
}

.admin-confirm-copy {
   color: var(--z-muted-text);
   font-size: 13px;
}
</style>
