<template>
   <!-- Faqat brauzerda: Telegram mini app'da tepada Telegram'ning o'z "orqaga" tugmasi bor -->
   <button
      v-if="showAppBackButton"
      type="button"
      class="-ml-1.5 p-1.5 shrink-0 rounded-full active:bg-(--z-muted) cursor-pointer"
      :aria-label="$t('common.back')"
      @click="goBack(router, fallback)"
   >
      <ArrowLeft class="size-5" />
   </button>
</template>

<script setup lang="ts">
import { ArrowLeft } from "lucide-vue-next";
import { useRouter, type RouteLocationRaw } from "vue-router";
import { goBack, showAppBackButton } from "@/modules/navigation";

/** fallback — ilova tarixsiz ochilganda (havola orqali to'g'ridan-to'g'ri) qayerga qaytadi */
withDefaults(defineProps<{ fallback?: RouteLocationRaw }>(), { fallback: () => ({ name: "home" }) });

const router = useRouter();
</script>
