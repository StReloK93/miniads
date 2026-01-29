<template>
   <Drawer
      v-model:visible="openModalButton"
      position="bottom"
      :class="[props.fullScreen ? 'h-full!' : 'h-4/5! max-h-[85%]!']"
      class="headless-drawer"
      :show-close-icon="false"
   >
      <div class="pb-safe-bottom h-full -mx-5" :class="[props.fullScreen ? 'pt-safe-top!' : '']">
         <slot></slot>
      </div>
   </Drawer>
</template>

<script setup lang="ts">
const openModalButton = defineModel<boolean>({ default: false });

const props = withDefaults(
   defineProps<{
      fullScreen?: boolean;
   }>(),
   {
      fullScreen: false,
   },
);
</script>
