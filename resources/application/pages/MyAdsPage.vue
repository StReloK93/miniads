<template>
   <MyAds v-if="authStore.user" />
   <GuestLoginPage
      v-else
      :title="$t('nav.myAds')"
      :card-title="$t('auth.benefitCabinet')"
      :card-description="$t('auth.myAdsHint')"
   />
</template>

<script setup lang="ts">
import MyAds from "@/features/profile/MyAds.vue";
import GuestLoginPage from "@/features/auth/GuestLoginPage.vue";
import { useAuth } from "@shared/store/useAuth";

const authStore = useAuth();
</script>
