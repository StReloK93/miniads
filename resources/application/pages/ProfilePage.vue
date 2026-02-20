<template>
   <NavigationPageDecorator>
      <template #header>
         <div>The Header</div>
         <h3 class="flex items-center gap-1 px-1 mb-2 text-secondary text-sm">
            <i class="pi pi-angle-right text-xs!"></i>
            {{ AuthStore.user?.name }}
         </h3>
      </template>
      <template #content> The profile page </template>
   </NavigationPageDecorator>
</template>

<script setup lang="ts">
import { useAuth } from "@shared/store/useAuth";
const AuthStore = useAuth();
import NavigationPageDecorator from "@/components/NavigationPageDecorator.vue";
</script>
