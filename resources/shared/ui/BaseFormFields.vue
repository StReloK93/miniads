<template>
   <!--
      InputConfig ro'yxatidan maydonlar: sarlavha, "(ixtiyoriy)", maydonning o'zi, izoh va xato.
      vee-validate <Form> ichida ishlaydi — BaseForm (sahifa formasi) ham, oynadagi formalar ham shuni ishlatadi.
   -->
   <template v-for="input in inputConfigs" :key="input.name">
      <!-- Ota maydon ichiga joylanadigan qo'shimcha (masalan narx yonidagi valyuta) -->
      <Teleport defer v-if="input.teleport_child_class" :to="`.${input.teleport_child_class}`">
         <main :class="input.class" class="child">
            <component :is="input.component" :name="input.name" v-bind="input.props" />
         </main>
      </Teleport>
      <div v-else :class="input.class">
         <div v-if="input.props?.title" class="flex items-center gap-1.5 mb-1.5 px-0.5 select-none">
            <p class="text-xs font-semibold text-(--z-foreground) tracking-wide">
               {{ input.props?.title }}
            </p>
            <span v-if="input.optional || input.required === false" class="text-[11px] font-normal text-(--z-muted-text)/80">
               ({{ $t("common.optional") }})
            </span>
         </div>
         <main class="relative" :class="input.teleport_parent_class">
            <component
               :is="input.component"
               :name="input.name"
               v-bind="input.props"
               @focusin="scrollParent && centerElement($event.target, scrollParent)"
            />
         </main>

         <p v-if="input.hint" class="text-[11px] text-(--z-muted-text) px-1 mt-1">{{ input.hint }}</p>
         <ErrorMessage :name="input.name" v-slot="{ message }">
            <p class="text-sm text-(--z-danger) mt-1">
               {{ message }}
            </p>
         </ErrorMessage>
      </div>
   </template>
</template>

<script setup lang="ts">
import { ErrorMessage } from "vee-validate";
import type { InputConfig } from "@shared/types";
import { centerElement } from "@shared/modules/helpers";

defineProps<{
   inputConfigs: InputConfig[];
   /** Fokus olgan maydon klaviatura ustida ko'rinsin: shu konteyner aylantiriladi */
   scrollParent?: HTMLElement | null;
}>();
</script>
