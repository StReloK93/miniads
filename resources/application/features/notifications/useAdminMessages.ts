import { computed, ref, watch } from "vue";
import { api } from "@shared/composables/useFetch";
import { useAuth } from "@shared/store/useAuth";

export type AdminMessage = {
   id: number;
   type: "warning" | "info";
   body: string;
   read_at: string | null;
   created_at: string;
   product: { id: number; title: string } | null;
};

/**
 * Administratsiyadan kelgan xabarlar — bitta umumiy holat: pastki menyudagi belgi va Profil'dagi ro'yxat.
 * Ekranni to'smaydi: o'qilmaganlar faqat "Profil" ikonkasida son bo'lib turadi.
 */
const messages = ref<AdminMessage[]>([]);
let started = false;

async function load() {
   try {
      const { data } = await api.get<AdminMessage[]>("messages");
      messages.value = data;
   } catch {
      // Yuklanmasa ilova odatdagidek ishlayveradi
   }
}

/** Profil'da ko'rsatilganlar o'qildi: belgi yo'qoladi, ro'yxatda "yangi" belgisi shu ochilishda qoladi */
async function markAllRead() {
   const ids = messages.value.filter((message) => !message.read_at).map((message) => message.id);
   if (!ids.length) return;
   const now = new Date().toISOString();
   messages.value = messages.value.map((message) => (message.read_at ? message : { ...message, read_at: now }));
   try {
      await api.post("messages/read", { ids });
   } catch {
      // Keyingi ochilishda yana "yangi" bo'lib ko'rinadi
   }
}

export function useAdminMessages() {
   // Kirgan foydalanuvchi aniqlanganda (Telegram yoki sessiya tiklanganda) bir marta yuklaymiz
   if (!started) {
      started = true;
      const authStore = useAuth();
      watch(
         () => authStore.user?.id,
         (id) => {
            if (id) load();
            else messages.value = [];
         },
         { immediate: true },
      );
   }

   return {
      messages,
      unreadCount: computed(() => messages.value.filter((message) => !message.read_at).length),
      load,
      markAllRead,
   };
}
