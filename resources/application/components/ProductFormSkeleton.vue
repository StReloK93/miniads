<template>
   <main class="h-full grid grid-rows-[auto_1fr]">
      <aside class="flex flex-col justify-between">
         <main class="pb-4 border-b border-(--z-border) -mx-4 px-4">
            <div class="skeleton w-22 h-5 mb-4"></div>
            <div class="skeleton w-44 h-3 mb-4"></div>
            <div class="flex gap-2 mb-1">
               <span class="skeleton w-18 h-4 inline-block"></span>
               <span class="skeleton w-18 h-4 inline-block"></span>
            </div>
         </main>
         <main class="relative grow">
            <article class="absolute inset-0 overflow-y-auto no-scrollbar py-3">
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
         <main class="border-t border-(--z-border) h-28 p-4 pb-0 flex flex-col items-center -mx-4">
            <div class="skeleton h-12 rounded-2xl! mb-3 w-full"></div>
            <p class="skeleton h-3 w-4/5 mb-1"></p>
            <p class="skeleton h-3 w-2/5 mb-1"></p>
         </main>
      </aside>
   </main>
</template>
