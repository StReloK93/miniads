<template>
   <button
      :type="type"
      :disabled="disabled || loading"
      :class="{ 'rounded-full!': props.rounded }"
      class="ui-button inline-flex items-center justify-center gap-2 transition active:scale-[0.97] disabled:opacity-60 disabled:cursor-not-allowed"
      :data-size="size"
      :data-severity="severity"
      :data-icon-only="iconOnly || undefined"
   >
      <!-- LOADING -->
      <ArrowPathIcon v-if="loading" class="ui-button__spinner size-5 animate-spin" />

      <!-- ICON -->
      <component v-else-if="$slots.icon" :is="$slots.icon" />

      <!-- LABEL -->
      <span v-if="!iconOnly" class="ui-button__label">
         <slot />
      </span>
   </button>
</template>

<script setup lang="ts">
import { ArrowPathIcon } from "@heroicons/vue/24/outline";

type Size = "sm" | "md" | "lg";
type Severity = "primary" | "secondary" | "success" | "danger";

const props = withDefaults(
   defineProps<{
      rounded?: boolean;
      size?: Size;
      severity?: Severity;
      loading?: boolean;
      disabled?: boolean;
      iconOnly?: boolean;
      type?: "button" | "submit" | "reset";
   }>(),
   {
      rounded: false,
      size: "md",
      severity: "primary",
      loading: false,
      disabled: false,
      iconOnly: false,
      type: "button",
   },
);
</script>
