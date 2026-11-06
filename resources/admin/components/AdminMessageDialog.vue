<template>
   <BaseModal
      :open="open"
      :title="product ? 'Muallifga xabar' : 'Foydalanuvchiga xabar'"
      :description="recipient ? `${recipient.name}${product ? ` · “${product.title}”` : ''}` : ''"
      :show-buttons="false"
      @close="emit('close')"
   >
      <template #icon>
         <component :is="type === 'warning' ? TriangleAlert : MessageSquare" class="size-5" />
      </template>

      <div class="flex flex-col gap-3">
         <!-- Turi: ogohlantirish (sariq) yoki tushuntirish -->
         <div class="grid grid-cols-2 gap-1 p-1 rounded-xl bg-(--z-muted)/60 text-sm font-medium">
            <button
               v-for="item in TYPES"
               :key="item.value"
               type="button"
               class="h-9 rounded-lg cursor-pointer transition"
               :class="type === item.value ? 'bg-(--z-card) text-(--z-foreground) shadow-sm' : 'text-(--z-muted-text)'"
               @click="type = item.value"
            >
               {{ item.label }}
            </button>
         </div>

         <!-- Tez-tez yoziladigan matnlar: bosilsa maydonga tushadi, keyin tahrirlash mumkin -->
         <div class="flex flex-wrap gap-1.5">
            <button
               v-for="template in templates"
               :key="template.label"
               type="button"
               class="h-7 px-2.5 rounded-full border border-(--z-border) text-xs cursor-pointer hover:bg-(--z-muted)/60"
               @click="useTemplate(template)"
            >
               {{ template.label }}
            </button>
         </div>

         <textarea
            v-model="body"
            rows="5"
            maxlength="2000"
            class="admin-input"
            placeholder="Xabar matni: nima noto'g'ri va qanday tuzatish kerak"
         />
         <p class="text-[11px] text-(--z-muted-text) -mt-1.5">
            Bot orqali yuboriladi va ilovada Profil bo'limida ham turadi (menyuda yangi xabar belgisi chiqadi).
         </p>

         <p v-if="error" class="text-sm text-(--z-danger)">{{ error }}</p>

         <div class="flex gap-3">
            <BaseButton severity="glass" class="w-full" type="button" @click="emit('close')">Bekor qilish</BaseButton>
            <BaseButton
               class="w-full"
               type="button"
               :severity="type === 'warning' ? 'danger' : 'primary'"
               :loading="sending"
               :disabled="body.trim().length < 3"
               @click="send"
            >
               <template #icon><Send class="size-4 mr-1.5" /></template>
               Yuborish
            </BaseButton>
         </div>

         <!-- Avval yuborilganlar: takror yozmaslik va o'qilganini bilish uchun -->
         <details v-if="history.length" class="text-xs">
            <summary class="cursor-pointer text-(--z-muted-text) select-none">Avval yuborilgan ({{ history.length }})</summary>
            <ul class="mt-2 flex flex-col gap-2 max-h-56 overflow-y-auto">
               <li v-for="item in history" :key="item.id" class="rounded-lg border border-(--z-border) p-2">
                  <div class="flex items-center justify-between gap-2 text-(--z-muted-text)">
                     <span>
                        {{ item.type === "warning" ? "⚠️ Ogohlantirish" : "ℹ️ Tushuntirish" }}
                        <template v-if="item.product"> · #{{ item.product.id }}</template>
                     </span>
                     <span>{{ formatDateTime(item.created_at) }}</span>
                  </div>
                  <p class="mt-1 whitespace-pre-line text-(--z-foreground)">{{ item.body }}</p>
                  <p class="mt-1 text-[11px] text-(--z-muted-text)">
                     {{ item.telegram_sent ? "Botga yetkazildi" : "Botga yetkazilmadi" }} ·
                     {{ item.read_at ? `Ilovada o'qildi (${formatDateTime(item.read_at)})` : "Ilovada hali ko'rmagan" }}
                  </p>
               </li>
            </ul>
         </details>
      </div>
   </BaseModal>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { MessageSquare, Send, TriangleAlert } from "lucide-vue-next";
import AdminRepo, { type AdminMessageItem } from "@admin/entities/AdminRepo";
import BaseButton from "@shared/ui/BaseButton.vue";
import BaseModal from "@shared/ui/BaseModal.vue";
import { formatDateTime } from "@shared/modules/formatters";

type MessageType = "warning" | "info";

const props = defineProps<{
   open: boolean;
   recipient: { id: number; name: string } | null;
   /** Berilsa — shu e'lon haqida (xabarda e'lon nomi va "E'lonni ochish" tugmasi bo'ladi) */
   product?: { id: number; title: string } | null;
}>();

const emit = defineEmits<{
   (e: "close"): void;
   (e: "sent", message: string): void;
}>();

const TYPES: { value: MessageType; label: string }[] = [
   { value: "warning", label: "⚠️ Ogohlantirish" },
   { value: "info", label: "ℹ️ Tushuntirish" },
];

const AD_TEMPLATES: { label: string; type: MessageType; text: string }[] = [
   { label: "Rasm", type: "warning", text: "E'loningizdagi rasmlar mahsulotga mos emas yoki sifatsiz. Iltimos, haqiqiy va aniq rasmlarni qo'ying." },
   { label: "Narx", type: "warning", text: "E'loningizdagi narx noto'g'ri ko'rinadi. Iltimos, haqiqiy narxni yozing yoki bo'sh qoldiring («Kelishiladi»)." },
   { label: "Kategoriya", type: "info", text: "E'loningiz noto'g'ri bo'limga joylangan. Iltimos, mos bo'limga qayta joylang — shunda uni ko'proq odam ko'radi." },
   { label: "Takror", type: "warning", text: "Bir xil e'lonni qayta-qayta joylamang. Eskisini «Uzaytirish» tugmasi bilan lenta boshiga chiqarishingiz mumkin." },
   { label: "Ma'lumot kam", type: "info", text: "E'loningizga batafsilroq ma'lumot qo'shing (holati, o'lchami, manzil) — xaridorlar tezroq bog'lanadi." },
   { label: "Qoidaga zid", type: "warning", text: "E'loningiz platforma qoidalariga zid. Iltimos, uni tuzating, aks holda o'chiriladi." },
];
const USER_TEMPLATES = AD_TEMPLATES.filter((template) => ["Takror", "Qoidaga zid"].includes(template.label));

const templates = computed(() => (props.product ? AD_TEMPLATES : USER_TEMPLATES));

const type = ref<MessageType>("warning");
const body = ref("");
const sending = ref(false);
const error = ref("");
const history = ref<AdminMessageItem[]>([]);

function useTemplate(template: (typeof AD_TEMPLATES)[number]) {
   type.value = template.type;
   body.value = template.text;
}

// Har ochilganda toza forma va shu foydalanuvchiga yozilgan tarix
watch(
   () => [props.open, props.recipient?.id] as const,
   async ([open, recipientId]) => {
      if (!open || !recipientId) return;
      type.value = "warning";
      body.value = "";
      error.value = "";
      history.value = [];
      try {
         history.value = (await AdminRepo.userMessages(recipientId)).data;
      } catch {
         // Tarix yuklanmasa ham xabar yozish mumkin
      }
   },
);

async function send() {
   if (!props.recipient) return;
   sending.value = true;
   error.value = "";
   try {
      const { data } = await AdminRepo.sendMessage({
         ...(props.product ? { product_id: props.product.id } : { user_id: props.recipient.id }),
         type: type.value,
         body: body.value.trim(),
      });
      emit("sent", data.message);
      emit("close");
   } catch (exception: any) {
      error.value = exception?.response?.data?.message || "Xabarni yuborib bo'lmadi.";
   } finally {
      sending.value = false;
   }
}
</script>
