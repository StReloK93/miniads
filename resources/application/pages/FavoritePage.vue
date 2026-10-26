<template>
   <FavoriteList v-if="authStore.user" />
   <GuestLoginPage
      v-else
      title="Sevimlilar"
      card-title="Sevimlilarni saqlash uchun kiring"
      card-description="Yoqqan e'lonlarni yurakcha bilan belgilang va istalgan vaqtda shu yerda toping."
   />
</template>

<script setup lang="ts">
import FavoriteList from "@/features/favorites/FavoriteList.vue";
import GuestLoginPage from "@/features/auth/GuestLoginPage.vue";
import { useAuth } from "@shared/store/useAuth";

const authStore = useAuth();
</script>
