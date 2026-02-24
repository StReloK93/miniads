<template>
   <Form @submit="submit" class="mt-4">
      <template v-if="isLoadingData == false" class="">
         <main
            v-for="(value, index) in formData"
            :key="index"
            class="flex items-center gap-2 mb-1 select-none bg-tertiary p-0.5 rounded-md border border-secondary"
         >
            <main class="w-2/5 flex items-center gap-5">
               <ToggleButton
                  size="small"
                  v-model="value.select"
                  onLabel="On"
                  offLabel="Off"
                  onIcon="pi pi-lock"
                  offIcon="pi pi-lock-open"
                  class="w-20"
               />
               <div class="w-1/2 text-tertiary">{{ index + 1 }} - {{ value.parameter.title }}</div>
            </main>
            <main class="flex w-3/5 items-center" :class="{ 'opacity-40 dark:opacity-25': !value.select }">
               <div class="w-1/2">
                  <div class="flex items-center gap-2">
                     <Checkbox
                        binary
                        :disabled="!value.select"
                        v-model="value.is_required"
                        :inputId="`${value.parameter.id + value.parameter.type}`"
                        :name="`${value.parameter.id + value.parameter.type}`"
                     />
                     <label :for="`${value.parameter.id + value.parameter.type}`"> Majrubiy </label>
                  </div>
               </div>
               <div class="w-1/2">
                  <InputNumber
                     size="small"
                     :disabled="!value.select"
                     v-model="value.sort_order"
                     inputId="horizontal-buttons"
                     showButtons
                     buttonLayout="horizontal"
                     :step="1"
                     :max="20"
                     :min="0"
                     fluid
                  >
                     <template #incrementbuttonicon>
                        <span class="pi pi-plus" />
                     </template>
                     <template #decrementbuttonicon>
                        <span class="pi pi-minus" />
                     </template>
                  </InputNumber>
               </div>
            </main>
         </main>
      </template>
      <Skeleton v-else class="w-full" width="100%" height="300px" border-radius="8px" />
      <main class="flex justify-end mt-5">
         <Button
            type="button"
            size="small"
            severity="secondary"
            class="mr-3"
            label="Bekor qilish"
            @click="emit('close')"
         />
         <Button
            type="submit"
            size="small"
            severity="contrast"
            class="w-26"
            :loading="isLoading"
            label="Saqlash"
            icon="pi pi-circle"
         />
      </main>
   </Form>
</template>

<script setup lang="ts">
import { Form } from "@primevue/forms";
import { Button, Checkbox, InputNumber, Skeleton, ToggleButton } from "primevue";
import CategoryParameterRepo from "@shared/entities/CategoryParameter/CategoryParameterRepo";
import ParameterRepo from "@shared/entities/Parameter/ParameterRepo";
import { IParameter } from "@shared/types";
import { TreeNode } from "primevue/treenode";
import { ref, onMounted } from "vue";

const emit = defineEmits(["close"]);

const isLoading = ref(false);
const isLoadingData = ref(false);
async function submit() {
   isLoading.value = true;
   const submitData = formData.value
      .filter((value) => value.select)
      .map((value) => ({
         parameter_id: value.parameter_id,
         is_required: value.is_required,
         sort_order: value.sort_order,
      }));

   await CategoryParameterRepo.store(props.category.key, submitData).then(() => {
      isLoading.value = false;
      emit("close");
   });
}

const props = defineProps<{
   category: TreeNode;
}>();

const formData = ref<
   {
      parameter: IParameter;
      parameter_id: number;
      is_required: boolean;
      sort_order: number;
      select: boolean;
   }[]
>([]);
const paramaters = ref<IParameter[]>([]);

async function getData() {
   const { data } = await ParameterRepo.index();
   paramaters.value = data;

   const formatting = data.map((parameter) => ({
      select: false,

      parameter: parameter,
      parameter_id: parameter.id,
      is_required: false,
      sort_order: 0,
   }));

   formData.value = formatting;
}

onMounted(async () => {
   isLoadingData.value = true;

   await getData();

   const { data } = await CategoryParameterRepo.index(props.category.key);

   data.forEach((categoryParameter) => {
      const pivot = categoryParameter.pivot;

      const target = formData.value.findIndex((item) => item.parameter_id === pivot.parameter_id);
      if (target !== -1) {
         formData.value[target].select = true;
         formData.value[target].is_required = pivot.is_required;
         formData.value[target].sort_order = pivot.sort_order;
      }
   });
   isLoadingData.value = false;
});
</script>
