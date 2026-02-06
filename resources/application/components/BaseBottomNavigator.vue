<template>
   <nav class="flex justify-around pt-1 pb-safe-bottom bg-slate-50 px-1">
      <RouterLink :to="{ name: 'home' }">
         <BaseButton severity="secondary" variant="text" class="flex-col gap-1.5! text-xs min-w-20">
            <template #icon>
               <HomeIcon class="size-5" />
            </template>
            Bosh sahifa
         </BaseButton>
      </RouterLink>
      <RouterLink :to="{ name: 'favorites' }">
         <BaseButton severity="secondary" variant="text" class="flex-col gap-1.5! text-xs min-w-20">
            <template #icon>
               <HeartIcon class="size-5" />
            </template>
            Sevimlilar
         </BaseButton>
      </RouterLink>
      <RouterLink :to="{ name: 'create-product' }">
         <BaseButton severity="secondary" variant="text" class="flex-col gap-1.5! text-xs min-w-20">
            <template #icon>
               <PlusIcon class="size-5" />
            </template>
            E'lon berish
         </BaseButton>
      </RouterLink>
      <RouterLink :to="{ name: 'profile' }">
         <BaseButton severity="secondary" variant="text" class="flex-col gap-1.5! text-xs min-w-20">
            <template #icon>
               <UserIcon class="size-5" />
            </template>
            Profil
         </BaseButton>
      </RouterLink>
   </nav>
</template>
<script lang="ts" setup>
import { HeartIcon, HomeIcon, PlusIcon, UserIcon } from "@heroicons/vue/24/outline";
</script>
