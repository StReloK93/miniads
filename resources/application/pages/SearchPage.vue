<template>
   <NavigationPageDecorator>
      <template #header>
         <h3 class="font-bold text-xl">Qidiruv</h3>
         <article class="py-4 border-b border-(--z-border) -mx-4 px-4 flex gap-4">
            <FieldText placeholder="Nima qidiryabsiz? Uy , Avto, Mebel!" name="search" />
            <BaseButton icon-only>
               <template #icon>
                  <SlidersHorizontal class="size-5" />
               </template>
            </BaseButton>
         </article>
      </template>
      <template #content>
         <aside>
            <article class="flex justify-between items-center">
               <h3 class="title text-xs">Oxirgi qidiruvlar</h3>
               <button class="text-xs font-semibold">Tozalash</button>
            </article>
            <main class="flex gap-2 flex-wrap mt-3">
               <div
                  class="bg-(--z-muted) text-(--z-foreground) rounded-full text-sm inline-flex gap-1 items-center pl-4"
               >
                  Marojniy
                  <button class="h-8 w-8 inline-flex items-center justify-center">
                     <X class="size-3" />
                  </button>
               </div>
               <div
                  class="bg-(--z-muted) text-(--z-foreground) rounded-full text-sm inline-flex gap-1 items-center pl-4"
               >
                  Samalyot
                  <button class="h-8 w-8 inline-flex items-center justify-center">
                     <X class="size-3" />
                  </button>
               </div>
               <div
                  class="bg-(--z-muted) text-(--z-foreground) rounded-full text-sm inline-flex gap-1 items-center pl-4"
               >
                  Tapichka
                  <button class="h-8 w-8 inline-flex items-center justify-center">
                     <X class="size-3" />
                  </button>
               </div>
            </main>
         </aside>
      </template>
   </NavigationPageDecorator>
</template>

<script setup lang="ts">
import NavigationPageDecorator from "@/components/NavigationPageDecorator.vue";
import { Search, SlidersHorizontal, X } from "lucide-vue-next";
</script>
