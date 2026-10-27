<template>
   <ProfileAccount v-if="authStore.user" />
   <GuestLoginPage
      v-else
      :title="$t('nav.profile')"
      show-theme-switcher
      :card-title="$t('auth.profileTitle')"
      :card-description="$t('auth.profileHint')"
   />
</template>

<script setup lang="ts">
import ProfileAccount from "@/features/profile/ProfileAccount.vue";
import GuestLoginPage from "@/features/auth/GuestLoginPage.vue";
import { useAuth } from "@shared/store/useAuth";

const authStore = useAuth();
</script>
