<template>
   <!-- E'lon ustidan shikoyat: sabab (bitta bosish) + ixtiyoriy izoh. Admin panelda "Shikoyat" bo'limiga tushadi -->
   <BaseDrawer :open="open" title="Shikoyat qilish" description="Nima noto'g'ri? Admin ko'rib chiqadi." @close="emit('close')">
      <div v-if="sent" class="py-6 flex flex-col items-center text-center">
         <div class="size-12 mb-3 rounded-full grid place-items-center bg-emerald-500/10 text-emerald-600">
            <CheckCheck class="size-6" />
         </div>
         <p class="font-semibold">Shikoyat yuborildi</p>
         <p class="text-sm text-(--z-muted-text) mt-1">Rahmat! E'lonlar tozaligi uchun yordam berdingiz.</p>
      </div>

      <div v-else class="flex flex-col gap-2">
         <button
            v-for="item in reasons"
            :key="item.id"
            type="button"
            class="report-option"
            :class="{ 'is-active': reason === item.id }"
            @click="reason = item.id"
         >
            <span class="report-radio" />
            {{ item.label }}
         </button>

         <textarea
            v-model="comment"
            rows="3"
            maxlength="500"
            placeholder="Izoh (ixtiyoriy): nimasi noto'g'ri?"
            class="field mt-2 py-2.5 resize-none"
         />
         <p v-if="error" class="text-sm text-(--z-danger)" role="alert">{{ error }}</p>
      </div>

      <template #footer>
         <BaseButton v-if="sent" severity="secondary" class="w-full" @click="emit('close')">Yopish</BaseButton>
         <BaseButton v-else class="w-full" :disabled="!reason" :loading="sending" @click="send">
            <template #icon><Flag class="size-4" /></template>
            Yuborish
         </BaseButton>
      </template>
   </BaseDrawer>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import { CheckCheck, Flag } from "lucide-vue-next";
import BaseDrawer from "@shared/ui/BaseDrawer.vue";
import BaseButton from "@shared/ui/BaseButton.vue";
import ProductRepo from "@shared/entities/Product/ProductRepo";

const props = defineProps<{ open: boolean; productId: number }>();
const emit = defineEmits<{ (e: "close"): void }>();

// Ro'yxat serverdan (bitta manba); birinchi ochilishda bir marta yuklanadi
const reasons = ref<{ id: string; label: string }[]>([]);
const reason = ref<string | null>(null);
const comment = ref("");
const sending = ref(false);
const sent = ref(false);
const error = ref("");

watch(
   () => props.open,
   async (open) => {
      if (!open) return;
      reason.value = null;
      comment.value = "";
      sent.value = false;
      error.value = "";
      if (!reasons.value.length) {
         reasons.value = (await ProductRepo.reportReasons()).data;
      }
   },
);

async function send() {
   if (!reason.value) return;
   sending.value = true;
   error.value = "";
   try {
      await ProductRepo.report(props.productId, { reason: reason.value, comment: comment.value.trim() || undefined });
      sent.value = true;
   } catch (exception: any) {
      error.value = exception?.response?.data?.message || "Yuborib bo'lmadi. Qayta urinib ko'ring.";
   } finally {
      sending.value = false;
   }
}
</script>

<style scoped>
.report-option {
   display: flex;
   min-height: 2.875rem;
   align-items: center;
   gap: 0.75rem;
   border: 1px solid var(--z-glass-border);
   border-radius: var(--z-rounded);
   background: var(--z-card);
   padding: 0 0.875rem;
   color: var(--z-foreground);
   font-size: 0.875rem;
   font-weight: 500;
   text-align: left;
   cursor: pointer;
   transition: border-color var(--duration) var(--ease-soft);

   &.is-active {
      border-color: var(--z-primary);
   }
}

.report-radio {
   width: 1.125rem;
   height: 1.125rem;
   flex-shrink: 0;
   border: 1.5px solid var(--z-muted-text);
   border-radius: 50%;
   transition: border var(--duration) var(--ease-soft);

   .is-active & {
      border: 5px solid var(--z-primary);
   }
}
</style>
