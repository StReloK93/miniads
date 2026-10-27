<template>
   <FavoriteList v-if="authStore.user" />
   <GuestLoginPage
      v-else
      :title="$t('common.favorites')"
      :card-title="$t('auth.favoritesTitle')"
      :card-description="$t('auth.favoritesHint')"
   />
</template>

<script setup lang="ts">
import FavoriteList from "@/features/favorites/FavoriteList.vue";
import GuestLoginPage from "@/features/auth/GuestLoginPage.vue";
import { useAuth } from "@shared/store/useAuth";

const authStore = useAuth();
</script>
