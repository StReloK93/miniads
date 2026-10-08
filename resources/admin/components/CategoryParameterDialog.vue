<template>
   <form class="category-parameters" @submit.prevent="submit">
      <div v-if="error" class="category-parameters-error">{{ error }}</div>
      <div v-if="isLoadingData" class="category-parameters-loading">Parametrlar yuklanmoqda…</div>
      <template v-else>
         <div v-if="formData.length === 0" class="category-parameters-empty">Avval parametr yarating.</div>
         <article v-for="(item, index) in formData" :key="item.parameter_id" class="category-parameter-row">
            <label class="category-parameter-enabled">
               <input v-model="item.select" type="checkbox" />
               <span class="category-switch" aria-hidden="true"></span>
               <span class="category-parameter-title">{{ index + 1 }}. {{ item.parameter.title || item.parameter.placeholder }}</span>
            </label>
            <label class="category-parameter-required">
               <input v-model="item.is_required" type="checkbox" :disabled="!item.select" />
               <span>Majburiy</span>
            </label>
            <label class="category-order">
               <span>Tartib</span>
               <input v-model.number="item.sort_order" type="number" min="0" max="20" :disabled="!item.select" />
            </label>
         </article>
      </template>
      <footer class="category-parameters-footer">
         <BaseButton type="button" severity="secondary" variant="text" size="sm" :disabled="isLoading" @click="emit('close')">
            Bekor qilish
         </BaseButton>
         <BaseButton type="submit" size="sm" :loading="isLoading" :disabled="isLoadingData || Boolean(error)">Saqlash</BaseButton>
      </footer>
   </form>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import BaseButton from "@shared/ui/BaseButton.vue";
import CategoryParameterRepo from "@shared/entities/CategoryParameter/CategoryParameterRepo";
import ParameterRepo from "@shared/entities/Parameter/ParameterRepo";
import { IParameter } from "@shared/types";

const emit = defineEmits<{ (event: "close"): void }>();
const props = defineProps<{ category: { key: number | string; label: string } }>();
const isLoading = ref(false);
const isLoadingData = ref(false);
const error = ref("");
const formData = ref<
   {
      parameter: IParameter;
      parameter_id: number;
      is_required: boolean;
      sort_order: number;
      select: boolean;
   }[]
>([]);

async function submit() {
   isLoading.value = true;
   error.value = "";
   try {
      const submitData = formData.value
         .filter((value) => value.select)
         .map((value) => ({
            parameter_id: value.parameter_id,
            is_required: value.is_required,
            sort_order: value.sort_order,
         }));
      await CategoryParameterRepo.store(props.category.key, submitData);
      emit("close");
   } catch (exception) {
      console.error("Kategoriya parametrlari saqlanmadi.", exception);
      error.value = "Parametrlarni saqlab bo'lmadi. Qayta urinib ko'ring.";
   } finally {
      isLoading.value = false;
   }
}

onMounted(async () => {
   isLoadingData.value = true;
   error.value = "";
   try {
      const [{ data: parameters }, { data: categoryParameters }] = await Promise.all([
         ParameterRepo.index(),
         CategoryParameterRepo.index(props.category.key),
      ]);
      formData.value = parameters.map((parameter: IParameter) => {
         const current = categoryParameters.find((item: IParameter) => item.id === parameter.id);
         return {
            parameter,
            parameter_id: parameter.id,
            is_required: Boolean(current?.pivot?.is_required),
            sort_order: current?.pivot?.sort_order ?? 0,
            select: Boolean(current),
         };
      });
   } catch (exception) {
      console.error("Kategoriya parametrlari yuklanmadi.", exception);
      error.value = "Parametrlarni yuklab bo'lmadi. Qayta urinib ko'ring.";
   } finally {
      isLoadingData.value = false;
   }
});
</script>

<style scoped>
.category-parameters {
   display: grid;
   gap: 8px;
}

.category-parameter-row {
   display: grid;
   grid-template-columns: minmax(0, 1fr) 90px 90px;
   align-items: center;
   gap: 10px;
   border: 1px solid var(--z-border);
   border-radius: 10px;
   background: var(--z-card);
   padding: 10px;
}

.category-parameter-enabled,
.category-parameter-required,
.category-order {
   display: flex;
   align-items: center;
   gap: 7px;
   color: var(--z-foreground);
   font-size: 11px;
}

.category-parameter-enabled input,
.category-parameter-required input {
   accent-color: var(--z-primary);
}

.category-switch {
   display: none;
}

.category-parameter-title {
   overflow: hidden;
   font-weight: 600;
   text-overflow: ellipsis;
}

.category-order {
   flex-direction: column;
   align-items: flex-start;
   color: var(--z-muted-text);
   font-size: 9px;
}

.category-order input {
   width: 100%;
   height: 31px;
   border: 1px solid var(--z-border);
   border-radius: 7px;
   background: var(--z-field-background);
   padding: 4px 7px;
   color: var(--z-foreground);
   font-size: 11px;
}

.category-parameters-footer {
   position: sticky;
   bottom: -20px;
   display: flex;
   justify-content: flex-end;
   gap: 8px;
   border-top: 1px solid var(--z-border);
   background: var(--z-card);
   padding: 13px 0 0;
}

.category-parameters-error {
   border-radius: 8px;
   background: color-mix(in srgb, var(--z-danger) 10%, transparent);
   padding: 10px;
   color: var(--z-danger);
   font-size: 11px;
}

.category-parameters-loading,
.category-parameters-empty {
   padding: 25px 10px;
   color: var(--z-muted-text);
   font-size: 12px;
   text-align: center;
}

@media (max-width: 520px) {
   .category-parameter-row {
      grid-template-columns: minmax(0, 1fr) 75px;
   }

   .category-order {
      grid-column: 2;
   }
}
</style>
