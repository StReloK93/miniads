<template>
   <section>
      <Form @submit="submit">
         <main v-for="(value, index) in formData" class="flex items-center gap-2 mb-2 select-none">
            <div class="w-1/4 text-secondary">
               {{ index + 1 }} - {{ value.parameter.placeholder }}
            </div>
            <ToggleButton
               size="small"
               v-model="value.select"
               onLabel="On"
               offLabel="Off"
               onIcon="pi pi-lock"
               offIcon="pi pi-lock-open"
               class="w-30"
               aria-label="Do you confirm"
            />

            <div class="w-1/4">
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
            <div class="w-1/4">
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
         <main class="flex justify-end mt-5">
            <Button type="submit" size="small" severity="contrast">Saqlash</Button>
         </main>
      </Form>
   </section>
</template>

<script setup lang="ts">
import CategoryParameterRepo from "@/repositories/CategoryParameterRepo";
import ParameterRepo from "@/repositories/ParameterRepo";
import { IParameter } from "@/types";
import { ref, onMounted } from "vue";

function submit() {
   const submitData = formData.value
      .filter((value) => value.select)
      .map((value) => ({
         parameter_id: value.parameter_id,
         is_required: value.is_required,
         sort_order: value.sort_order,
      }));

   CategoryParameterRepo.store(props.category_id, submitData);
}

const props = defineProps<{
   category_id: string | number;
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
   await getData();

   const { data } = await CategoryParameterRepo.index(props.category_id);
   console.log(data);
});
</script>
