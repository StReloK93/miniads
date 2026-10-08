<template>
   <button
      :type="type"
      :disabled="disabled || loading"
      class="inline-flex items-center justify-center gap-2 font-medium transition cursor-pointer select-none active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed disabled:pointer-events-none"
      :class="[
         sizeClasses,
         variantClasses,
         { 'rounded-full!': rounded },
      ]"
   >
      <!-- LOADING -->
      <LoaderCircle v-if="loading" class="size-4 shrink-0 animate-spin" />

      <!-- ICON -->
      <component v-else-if="$slots.icon" :is="$slots.icon" class="shrink-0" />

      <!-- LABEL -->
      <span v-if="!iconOnly" class="inline-flex items-center gap-2">
         <slot />
      </span>
   </button>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { LoaderCircle } from "lucide-vue-next";

type Size = "xs" | "sm" | "md" | "lg";
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

const sizeClasses = computed(() => {
   if (props.iconOnly) {
      switch (props.size) {
         case "xs":
            return "size-7 p-0 rounded-md";
         case "sm":
            return "size-9 p-0 rounded-lg";
         case "lg":
            return "size-12.5 p-0 rounded-(--z-rounded)";
         default:
            return "size-11 p-0 rounded-(--z-rounded)";
      }
   }
   switch (props.size) {
      case "xs":
         return "h-7 px-2 text-xs rounded-md";
      case "sm":
         return "h-9 px-3 text-xs rounded-lg";
      case "lg":
         return "h-12.5 px-5 text-base rounded-(--z-rounded)";
      default:
         return "h-11 px-4 text-sm rounded-(--z-rounded)";
   }
});

const variantClasses = computed(() => {
   if (props.variant === "text") {
      switch (props.severity) {
         case "primary":
            return "bg-transparent text-(--z-primary) hover:bg-(--z-primary)/12";
         case "danger":
            return "bg-transparent text-(--z-danger) hover:bg-(--z-danger)/12";
         case "success":
            return "bg-transparent text-emerald-600 hover:bg-emerald-600/12";
         case "light":
            return "bg-transparent text-slate-800 hover:bg-slate-100";
         case "glass":
            return "bg-transparent text-(--z-foreground) hover:bg-white/10";
         default:
            return "bg-transparent text-(--z-foreground) hover:bg-(--z-muted)";
      }
   }
   switch (props.severity) {
      case "secondary":
         return "bg-(--z-secondary) text-(--z-foreground) hover:bg-(--z-border)";
      case "success":
         return "bg-emerald-600 text-white hover:bg-emerald-700";
      case "danger":
         return "bg-(--z-danger) text-white hover:bg-red-700";
      case "glass":
         return "bg-(--z-card)/70 text-(--z-foreground) backdrop-blur-md hover:bg-(--z-muted)";
      case "light":
         return "bg-white text-slate-800 hover:bg-slate-100";
      default:
         return "bg-(--z-primary) text-(--z-primary-foreground) hover:bg-(--z-primary-hover)";
   }
});
</script>
