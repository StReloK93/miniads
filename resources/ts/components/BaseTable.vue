<template>
   <DataTable size="small" :value="props.parameters" class="w-full bg-transparent! no-last-border">
      <Column
         v-for="column in props.columns"
         :key="column.field"
         :field="column.field"
         :header="column.header"
      >
         <template #body="slotProps">
            <template v-if="column.formatter">
               <main class="flex gap-1">
                  <Chip
                     v-for="value in column.formatter(slotProps.data[column.field])"
                     :label="value"
                     class="py-0.5! text-gray-600! dark:text-gray-100! rounded! capitalize"
                  />
               </main>
            </template>

            <template v-else>
               {{ slotProps.data[column.field] }}
            </template>
         </template>
      </Column>
      <Column body-style="text-align:center;width:6rem">
         <template #body="{ data }">
            <Button
               icon="pi pi-pencil"
               text
               severity="info"
               rounded
               @click="emit('edit', data.id)"
            />
            <!-- :loading="props.editButtonLoading == data.id" -->
            <Button
               icon="pi pi-trash"
               text
               rounded
               severity="danger"
               @click="confirmDelete($event, data.id)"
            />
            <!-- :loading="props.deleteButtonLoading == data.id" -->
         </template>
      </Column>
   </DataTable>
</template>

<script setup lang="ts">
import { useConfirm } from "primevue/useconfirm";
const confirm = useConfirm();
const emit = defineEmits<{
   (e: "edit", id: string | number): void;
   (e: "delete", id: string | number): void;
}>();
const props = defineProps<{
   parameters: any[];
   columns: { field: string; header: string; formatter?: (value: any) => any[] }[];
}>();

const confirmDelete = (event, id: number) => {
   confirm.require({
      target: event.currentTarget,
      message: "Aniq o'chirmoqchimisiz - Malumot qayta tiklanmaydi?",
      icon: "pi pi-exclamation-triangle",
      rejectProps: {
         label: "Bekor qilish",
         severity: "secondary",
         outlined: true,
      },
      acceptProps: {
         label: "O'chirish",
         severity: "danger",
      },
      accept: () => {
         emit("delete", id);
      },
   });
};
</script>
