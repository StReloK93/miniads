<template>
   <NavigationPageDecorator>
      <template #header>
         <article class="pb-4 h-32 border-b border-(--z-border)">
            <FieldText placeholder="Nima qidiryabsiz? Uy , Avto, Mebel!" name="search" />
         </article>
      </template>
      <template #content>
         <aside class="h-200">Go Scroll</aside>
      </template>
   </NavigationPageDecorator>
</template>

<script setup lang="ts">
import NavigationPageDecorator from "@/components/NavigationPageDecorator.vue";
</script>
