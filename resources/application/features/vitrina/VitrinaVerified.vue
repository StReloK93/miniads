<template>
   <!--
      "Tasdiqlangan vitrina" belgisi: to'ldirilgan ko'k nishon, ichida oq belgi (Telegram/Instagram uslubida).
      O'lchami class bilan beriladi (size-4, size-5...). Barcha vitrina joylarida shu bitta komponent.
      Shakl — lucide "badge-check"; lucide komponentiga stroke berib bo'lmaydi (color bilan almashadi), shuning uchun SVG.
   -->
   <svg class="vitrina-verified shrink-0" viewBox="0 0 24 24" role="img" :aria-label="$t('vitrina.verified')">
      <path
         fill="currentColor"
         d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"
      />
      <path d="m8.5 12 2.5 2.5 4.5-5" fill="none" stroke="white" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" />
   </svg>
</template>

<style scoped>
.vitrina-verified {
   color: #1d9bf0;
}
</style>
