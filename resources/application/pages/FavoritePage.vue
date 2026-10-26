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
import FavoriteList from "@/components/FavoriteList.vue";
import GuestLoginPage from "@/components/GuestLoginPage.vue";
import { useAuth } from "@shared/store/useAuth";

const authStore = useAuth();
</script>
