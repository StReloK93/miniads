<template>
   <form class="admin-form" @submit.prevent="onSubmit">
      <slot name="header" />
      <div class="admin-form-body">
         <p v-if="formError" class="admin-form-error">{{ formError }}</p>
         <slot name="inputs" />
         <AdminField
            v-for="input in inputConfigs"
            :key="input.name"
            :id="input.name"
            :label="input.placeholder"
            :kind="fieldKind(input)"
            :options="fieldOptions(input)"
            :min="fieldNumber(input, 'min')"
            :max="fieldNumber(input, 'max')"
            :on-label="fieldText(input, 'onLabel')"
            :off-label="fieldText(input, 'offLabel')"
            :placeholder="fieldText(input, 'placeholder')"
            :model-value="values[input.name]"
            :error="errors[input.name]"
            @update:model-value="setValue(input.name, $event)"
         />
      </div>
      <footer class="admin-form-footer">
         <BaseButton type="button" severity="secondary" variant="text" size="sm" :disabled="loading" @click="emit('close')">
            Bekor qilish
         </BaseButton>
         <BaseButton type="submit" size="sm" :loading="loading">Saqlash</BaseButton>
      </footer>
   </form>
</template>

<script setup lang="ts">
import { reactive, ref } from "vue";
import { z } from "zod";
import type { InputConfig } from "@shared/types";
import AdminField from "@shared/ui/AdminField.vue";

type AdminFieldKind = "text" | "number" | "select" | "toggle" | "tags" | "file";

const props = defineProps<{
   inputConfigs: InputConfig[];
   submit: (values: Record<string, unknown>) => Promise<void>;
   superRefine?: (values: Record<string, unknown>, ctx: z.RefinementCtx) => void;
}>();
const emit = defineEmits<{ (event: "close"): void }>();
const loading = ref(false);
const formError = ref("");
const values = reactive<Record<string, unknown>>(
   Object.fromEntries(props.inputConfigs.map((input) => [input.name, input.value])),
);
const errors = reactive<Record<string, string>>({});

function fieldKind(input: InputConfig): AdminFieldKind {
   const kind = input.props?.adminKind;

   return kind === "number" || kind === "select" || kind === "toggle" || kind === "tags" || kind === "file"
      ? kind
      : "text";
}

function fieldOptions(input: InputConfig): (string | number)[] {
   const options = input.props?.options;

   return Array.isArray(options) && options.every((option) => typeof option === "string" || typeof option === "number")
      ? options
      : [];
}

function fieldNumber(input: InputConfig, key: "min" | "max"): number | undefined {
   const value = input.props?.[key];

   return typeof value === "number" ? value : undefined;
}

function fieldText(input: InputConfig, key: "onLabel" | "offLabel" | "placeholder"): string | undefined {
   const value = input.props?.[key];

   return typeof value === "string" ? value : undefined;
}

function setValue(name: string, value: unknown) {
   values[name] = value;
   delete errors[name];
}

async function onSubmit() {
   formError.value = "";
   Object.keys(errors).forEach((key) => delete errors[key]);
   const shape = Object.fromEntries(
      props.inputConfigs.filter((input) => input.schema).map((input) => [input.name, input.schema]),
   ) as Record<string, z.ZodTypeAny>;
   const result = z.object(shape).superRefine(props.superRefine ?? (() => {})).safeParse({ ...values });

   if (!result.success) {
      for (const issue of result.error.issues) {
         const key = String(issue.path[0] ?? "");
         if (key && !errors[key]) errors[key] = issue.message;
      }
      return;
   }

   loading.value = true;
   try {
      await props.submit(result.data);
      emit("close");
   } catch (error) {
      console.error("Admin forma saqlanmadi.", error);
      formError.value = "Ma'lumotni saqlab bo'lmadi. Kiritilgan ma'lumotlarni tekshirib, qayta urinib ko'ring.";
   } finally {
      loading.value = false;
   }
}
</script>

<style scoped>
.admin-form {
   display: flex;
   min-height: 100%;
   flex-direction: column;
   gap: 17px;
}

.admin-form-body {
   display: grid;
   gap: 17px;
}

.admin-form-footer {
   position: sticky;
   bottom: 0;
   display: flex;
   justify-content: flex-end;
   gap: 9px;
   margin-top: auto;
   border-top: 1px solid var(--z-border);
   background: var(--z-card);
   padding-top: 14px;
}

.admin-form-error {
   margin: 0;
   border-radius: 9px;
   background: color-mix(in srgb, var(--z-danger) 10%, transparent);
   padding: 10px 12px;
   color: var(--z-danger);
   font-size: 11px;
}
</style>
