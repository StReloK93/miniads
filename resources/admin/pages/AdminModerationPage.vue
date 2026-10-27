<template>
   <section class="admin-page">
      <header class="admin-page-heading">
         <div>
            <h1>Moderatsiya</h1>
            <p class="admin-muted">
               Taqiqlangan so'zlar: e'lon matnida topilsa, e'lon o'chirilmaydi — "Shikoyat" navbatiga tushadi va admin chatiga xabar
               ketadi.
            </p>
         </div>
         <RouterLink :to="{ name: 'admin-products', query: { status: 'reported' } }">
            <BaseButton size="sm" severity="secondary">
               <template #icon><Flag class="size-4" /></template>
               Shikoyatlar navbati
            </BaseButton>
         </RouterLink>
      </header>

      <p v-if="error" class="admin-alert admin-alert-error">{{ error }}</p>
      <p v-if="notice" class="admin-alert admin-alert-success">{{ notice }}</p>

      <section class="admin-panel p-4 grid gap-3">
         <div>
            <label for="banned-words" class="admin-label">Taqiqlangan so'zlar</label>
            <p class="admin-hint">
               Har qatorda bitta so'z yoki ibora. Kirill/lotin farqi yo'q. 4 harfdan uzunlari qo'shimchalari bilan ham topiladi
               ("qurol" → "qurollar"), qisqalari faqat butun so'z sifatida.
            </p>
         </div>
         <textarea
            id="banned-words"
            v-model="words"
            rows="12"
            class="admin-input py-2.5 font-mono text-sm! leading-relaxed resize-y"
            placeholder="qurol&#10;nasha&#10;soxta diplom"
            :disabled="loading"
         />
         <div class="flex items-center justify-between gap-3">
            <span class="text-xs text-(--z-muted-text) tabular-nums">{{ wordCount }} ta so'z</span>
            <BaseButton size="sm" :loading="saving" :disabled="loading || !changed" @click="save">
               <template #icon><Save class="size-4" /></template>
               Saqlash
            </BaseButton>
         </div>
      </section>
   </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { Flag, Save } from "lucide-vue-next";
import AdminRepo from "@admin/entities/AdminRepo";
import BaseButton from "@shared/ui/BaseButton.vue";

const words = ref("");
const savedWords = ref("");
const loading = ref(true);
const saving = ref(false);
const error = ref("");
const notice = ref("");

const changed = computed(() => words.value.trim() !== savedWords.value.trim());
const wordCount = computed(() => words.value.split("\n").filter((line) => line.trim().length >= 2).length);

async function save() {
   saving.value = true;
   error.value = "";
   notice.value = "";
   try {
      const { data } = await AdminRepo.updateModeration(words.value);
      savedWords.value = words.value;
      notice.value = data.message;
   } catch (exception: any) {
      error.value = exception?.response?.data?.message || "Saqlab bo'lmadi.";
   } finally {
      saving.value = false;
   }
}

onMounted(async () => {
   try {
      const { data } = await AdminRepo.moderation();
      words.value = savedWords.value = data.banned_words ?? "";
   } catch (exception) {
      console.error("Moderatsiya sozlamalari yuklanmadi.", exception);
      error.value = "Sozlamalarni yuklab bo'lmadi.";
   } finally {
      loading.value = false;
   }
});
</script>
