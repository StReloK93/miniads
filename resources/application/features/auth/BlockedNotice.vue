<template>
   <!-- Bloklangan hisob: e'lon joylash/tahrirlash o'rniga tushuntirish (server ham baribir rad etadi) -->
   <div class="h-full flex flex-col items-center justify-center text-center px-6 py-14">
      <div class="size-14 mb-3 rounded-full grid place-items-center bg-(--z-danger)/10 text-(--z-danger)">
         <Ban class="size-6" />
      </div>
      <h3 class="font-semibold mb-1">Hisobingiz bloklangan</h3>
      <p class="text-sm text-(--z-muted-text) max-w-72">
         Administrator e'lon joylash imkoniyatingizni cheklagan. E'lonlarni ko'rish va qidirish ochiq.
      </p>
      <RouterLink :to="{ name: 'home' }" class="mt-5">
         <BaseButton severity="secondary" size="sm">Bosh sahifaga</BaseButton>
      </RouterLink>
   </div>
</template>

<script setup lang="ts">
import { Ban } from "lucide-vue-next";
import BaseButton from "@shared/ui/BaseButton.vue";
</script>
