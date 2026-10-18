<template>
   <main class="h-full flex flex-col justify-between">
      <aside class="pb-3 border-b border-(--z-border) -mx-4 px-4 shrink-0">
         <div class="flex justify-between items-center mb-1">
            <div class="skeleton w-28 h-6"></div>
            <div class="skeleton w-10 h-4"></div>
         </div>
         <div class="mt-2.5 mb-2.5 flex flex-col gap-1.5">
            <div class="grid grid-cols-2 gap-1.5 w-full">
               <div class="h-1 bg-(--z-primary) rounded-full"></div>
               <div class="h-1 bg-(--z-primary) rounded-full"></div>
            </div>
            <div class="skeleton w-24 h-3.5"></div>
         </div>
         <div class="flex gap-2">
            <span class="skeleton w-20 h-4 inline-block"></span>
            <span class="skeleton w-20 h-4 inline-block"></span>
         </div>
      </aside>
      <main class="relative grow -mx-4">
         <article class="absolute inset-0 overflow-y-auto no-scrollbar px-4 py-3">
            <div class="skeleton w-16 h-3 mb-2"></div>
            <div class="skeleton aspect-video mb-4"></div>

            <div class="skeleton w-16 h-3 mb-1"></div>
            <div class="skeleton h-12 mb-4.5"></div>

            <div class="skeleton w-16 h-3 mb-1"></div>
            <div class="skeleton h-12 mb-4.5"></div>

            <div class="skeleton w-16 h-3 mb-1"></div>
            <div class="skeleton h-17 mb-4"></div>

            <div class="skeleton w-16 h-3 mb-1"></div>
            <div class="skeleton h-12 mb-4"></div>
         </article>
      </main>
      <footer class="border-t border-(--z-border) h-28 p-4 pb-0 flex flex-col items-center -mx-4 shrink-0">
         <div class="skeleton h-12 rounded-2xl! mb-3 w-full"></div>
         <!-- <p class="skeleton h-3 w-4/5 mb-1"></p>
         <p class="skeleton h-3 w-2/5 mb-1"></p> -->
      </footer>
   </main>
</template>
