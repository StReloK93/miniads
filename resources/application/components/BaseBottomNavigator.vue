<template>
   <nav
      class="flex justify-around p-1 bg-linear-to-r from-stone-100/60 to-slate-100/60 backdrop-blur-sm border rounded-xl border-stone-200/70"
   >
      <RouterLink :to="{ name: 'home' }">
         <BaseButton
            :severity="$route.name == 'home' ? 'primary' : 'secondary'"
            variant="text"
            class="flex-col gap-1! text-xs min-w-18 p-1! font-black!"
         >
            <template #icon>
               <House class="size-4" stroke-width="3" />
            </template>
            Asosiy
         </BaseButton>
      </RouterLink>
      <RouterLink :to="{ name: 'favorites' }">
         <BaseButton
            :severity="$route.name == 'favorites' ? 'primary' : 'secondary'"
            variant="text"
            class="flex-col gap-1! text-xs min-w-18 p-1! font-black!"
         >
            <template #icon>
               <Heart class="size-4" stroke-width="3" />
            </template>
            Sevimlilar
         </BaseButton>
      </RouterLink>

      <RouterLink :to="{ name: 'search' }">
         <BaseButton
            :severity="$route.name == 'search' ? 'primary' : 'secondary'"
            variant="text"
            class="flex-col gap-1! text-xs min-w-18 p-1! font-black!"
         >
            <template #icon>
               <Search class="size-4" stroke-width="3" />
            </template>
            Qidiruv
         </BaseButton>
      </RouterLink>
      <RouterLink :to="{ name: 'create-product' }">
         <BaseButton
            :severity="$route.name == 'create-product' ? 'primary' : 'secondary'"
            variant="text"
            class="flex-col gap-1! text-xs min-w-18 p-1! font-black!"
         >
            <template #icon>
               <Plus class="size-4" stroke-width="3" />
            </template>
            E'lon berish
         </BaseButton>
      </RouterLink>
   </nav>
</template>
<script lang="ts" setup>
import { House, Plus, Heart, Search } from "lucide-vue-next";
</script>
