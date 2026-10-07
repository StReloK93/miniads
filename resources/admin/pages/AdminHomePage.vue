<template>
   <section class="admin-page">
      <header class="admin-page-heading">
         <div>
            <p class="admin-eyebrow">UMUMIY KO‘RSATKICHLAR</p>
            <h1>Umumiy ko‘rinish</h1>
            <p class="admin-muted">E’lonlar, foydalanuvchilar va katalog holati.</p>
         </div>
         <RouterLink class="admin-button admin-button-primary" :to="{ name: 'admin-products' }">
            E’lonlarni boshqarish
         </RouterLink>
      </header>

      <p v-if="error" class="admin-alert admin-alert-error">{{ error }}</p>
      <div v-if="loading" class="admin-panel admin-loading">Ma’lumotlar yuklanmoqda…</div>
      <template v-else-if="dashboard">
         <div class="admin-stats-grid">
            <article v-for="card in statCards" :key="card.key" class="admin-stat-card">
               <span class="admin-stat-label">{{ card.label }}</span>
               <strong>{{ dashboard.stats[card.key] ?? 0 }}</strong>
               <span class="admin-stat-detail">{{ card.detail }}</span>
            </article>
         </div>

         <section class="admin-panel">
            <div class="admin-section-heading">
               <div>
                  <h2>So‘nggi e’lonlar</h2>
                  <p class="admin-muted">Oxirgi kiritilgan e’lonlar ro‘yxati.</p>
               </div>
               <RouterLink class="admin-text-link" :to="{ name: 'admin-products' }">Barchasini ko‘rish</RouterLink>
            </div>
            <div class="admin-table-wrap">
               <table class="admin-table">
                  <thead>
                     <tr>
                        <th>E’lon</th>
                        <th>Muallif</th>
                        <th>Kategoriya</th>
                        <th>Narx</th>
                        <th>Holati</th>
                     </tr>
                  </thead>
                  <tbody>
                     <tr v-for="product in dashboard.recent_products" :key="product.id">
                        <td>
                           <strong>{{ product.title }}</strong>
                           <small>#{{ product.id }} · {{ formatDate(product.created_at) }}</small>
                        </td>
                        <td>{{ product.user?.name || "—" }}</td>
                        <td>{{ product.category?.name || "—" }}</td>
                        <td>{{ formatPrice(product.price) }}</td>
                        <td>
                           <span class="admin-badge" :class="product.deleted_at ? 'admin-badge-muted' : product.is_active ? 'admin-badge-success' : 'admin-badge-warning'">
                              {{ product.deleted_at ? "Arxivlangan" : product.is_active ? "Faol" : "Muddati tugagan" }}
                           </span>
                        </td>
                     </tr>
                     <tr v-if="dashboard.recent_products.length === 0">
                        <td colspan="5" class="admin-empty-cell">Hozircha e’lonlar yo‘q.</td>
                     </tr>
                  </tbody>
               </table>
            </div>
         </section>
      </template>
   </section>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import AdminRepo from "@admin/entities/AdminRepo";

type Dashboard = {
   stats: Record<string, number>;
   recent_products: Array<Record<string, any>>;
};

const dashboard = ref<Dashboard | null>(null);
const loading = ref(true);
const error = ref("");
const statCards = [
   { key: "products", label: "Jami e’lonlar", detail: "Arxivdagilar bundan mustasno" },
   { key: "active_products", label: "Faol e’lonlar", detail: "Hozir joylangan" },
   { key: "expired_products", label: "Muddati tugagan", detail: "Yangilash talab qilinadi" },
   { key: "deleted_products", label: "Arxivlangan e’lonlar", detail: "Tiklash mumkin" },
   { key: "users", label: "Foydalanuvchilar", detail: "Ro‘yxatdan o‘tganlar" },
   { key: "categories", label: "Kategoriyalar", detail: "Faol katalog bo‘limlari" },
   { key: "admins", label: "Administratorlar", detail: "Admin huquqiga ega" },
];

function formatPrice(value: number | null) {
   return value === null ? "Kelishiladi" : `${new Intl.NumberFormat("uz-UZ").format(value)} so‘m`;
}

function formatDate(value: string) {
   return new Intl.DateTimeFormat("uz-UZ", { dateStyle: "medium" }).format(new Date(value));
}

onMounted(async () => {
   try {
      const { data } = await AdminRepo.dashboard();
      dashboard.value = data;
   } catch (exception) {
      console.error("Admin statistikasi yuklanmadi.", exception);
      error.value = "Statistikani yuklab bo‘lmadi. Sahifani yangilab ko‘ring.";
   } finally {
      loading.value = false;
   }
});
</script>
