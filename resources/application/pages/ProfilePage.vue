<template>
   <ProfileAccount v-if="authStore.user" />
   <GuestLoginPage
      v-else
      title="Profil"
      show-theme-switcher
      card-title="Profilingizga kiring"
      card-description="E'lonlaringizni ko'rish va boshqarish uchun Telegram orqali kiring."
   />
</template>

<script setup lang="ts">
import ProfileAccount from "@/features/profile/ProfileAccount.vue";
import GuestLoginPage from "@/features/auth/GuestLoginPage.vue";
import { useAuth } from "@shared/store/useAuth";

const authStore = useAuth();
</script>
