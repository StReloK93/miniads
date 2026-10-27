<template>
   <MyAds v-if="authStore.user" />
   <GuestLoginPage
      v-else
      title="E'lonlarim"
      card-title="E'lonlaringizni boshqaring"
      card-description="Joylagan e'lonlaringizni ko'rish, tahrirlash va o'chirish uchun Telegram orqali kiring."
   />
</template>

<script setup lang="ts">
import MyAds from "@/features/profile/MyAds.vue";
import GuestLoginPage from "@/features/auth/GuestLoginPage.vue";
import { useAuth } from "@shared/store/useAuth";

const authStore = useAuth();
</script>
