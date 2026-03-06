<template>
   <button
      :type="type"
      :disabled="disabled || loading"
      :class="{ 'rounded-full!': props.rounded }"
      class="ui-button inline-flex items-center justify-center gap-2 transition active:scale-[0.95] active:opacity-75 disabled:opacity-60 disabled:cursor-not-allowed"
      :data-size="size"
      :data-severity="severity"
      :data-variant="variant"
      :data-icon-only="iconOnly || undefined"
   >
      <!-- LOADING -->
      <LoaderCircle v-if="loading" class="ui-button__spinner size-4 animate-spin" />

      <!-- ICON -->
      <component v-else-if="$slots.icon" :is="$slots.icon" class="" />

      <!-- LABEL -->
      <span v-if="!iconOnly" class="ui-button__label inline-flex items-center gap-2">
         <slot />
      </span>
   </button>
</template>

<script setup lang="ts">
import { LoaderCircle } from "lucide-vue-next";

type Size = "sm" | "md" | "lg";
type Severity = "primary" | "secondary" | "success" | "danger" | "light" | "glass";
type Variant = "default" | "text";

const props = withDefaults(
   defineProps<{
      rounded?: boolean;
      size?: Size;
      severity?: Severity;
      variant?: Variant;
      loading?: boolean;
      disabled?: boolean;
      iconOnly?: boolean;
      type?: "button" | "submit" | "reset";
   }>(),
   {
      rounded: false,
      size: "md",
      severity: "primary",
      variant: "default",
      loading: false,
      disabled: false,
      iconOnly: false,
      type: "button",
   },
);
</script>
<style scoped lang="scss">
.ui-button {
   /* ===== default tokens (md) ===== */
   --btn-px: var(--space-md);
   --btn-py: var(--space-md);
   --btn-radius: var(--z-rounded);
   --btn-bg: var(--z-primary);
   --btn-bg-hover: var(--z-primary-hover);
   --btn-text: #fff;

   border-radius: var(--btn-radius);
   background: var(--btn-bg);
   color: var(--btn-text);
   font-weight: 500;

   padding-inline: var(--btn-px);
   height: 45px;
   // &:hover:not(:disabled) {
   //    background: var(--btn-bg-hover);
   // }
}

/* ================= SIZE ================= */
.ui-button[data-size="sm"] {
   --btn-px: var(--space-sm);
   --btn-py: var(--space-sm);
   font-size: 0.875rem;
}

.ui-button[data-size="lg"] {
   --btn-px: var(--space-lg);
   --btn-py: var(--space-lg);
   font-size: 1rem;
}

/* ================= ICON ONLY ================= */
.ui-button[data-icon-only] {
   padding-inline: var(--btn-py);
   aspect-ratio: 1 / 1;

   .ui-button__label {
      display: none;
   }
}

/* ================= SEVERITY ================= */
.ui-button[data-severity="primary"] {
   --btn-bg: var(--z-primary);
   --btn-bg-hover: var(--z-primary-hover);
   --btn-text: #fff;
}

.ui-button[data-severity="secondary"] {
   --btn-bg: var(--z-secondary);
   --btn-bg-hover: var(--z-border);
   --btn-text: var(--z-primary);
}

.ui-button[data-severity="glass"] {
   --btn-bg: color-mix(in oklab, var(--color-stone-100) 60%, transparent);
   --btn-bg-hover: var(--z-border);
   --btn-text: var(--z-primary);
   backdrop-filter: blur(var(--blur-sm));
}

.ui-button[data-severity="success"] {
   --btn-bg: #16a34a;
   --btn-bg-hover: #15803d;
   --btn-text: #fff;
}

.ui-button[data-severity="danger"] {
   --btn-bg: var(--z-danger);
   --btn-bg-hover: #b91c1c;
   --btn-text: #fff;
}

.ui-button[data-severity="light"] {
   --btn-bg: #fff;
   --btn-bg-hover: var(--color-surface-muted);
   --btn-text: #333;
}

.ui-button[data-variant="text"] {
   --btn-bg: transparent;
   --btn-bg-hover: var(--color-border);
}

.ui-button[data-variant="text"][data-severity="primary"] {
   --btn-text: var(--z-primary);
   --btn-bg-hover: color-mix(in srgb, var(--z-primary) 12%, transparent);
}

.ui-button[data-variant="text"][data-severity="danger"] {
   --btn-text: var(--z-danger);
   --btn-bg-hover: color-mix(in srgb, var(--z-danger) 12%, transparent);
}

.ui-button[data-variant="text"][data-severity="success"] {
   --btn-text: #16a34a;
   --btn-bg-hover: color-mix(in srgb, #16a34a 12%, transparent);
}
/* ================= ICON / SPINNER ================= */
.ui-button__icon,
.ui-button__spinner {
   flex-shrink: 0;
}
</style>
