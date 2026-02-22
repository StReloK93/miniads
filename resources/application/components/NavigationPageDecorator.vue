<template>
   <section class="pt-[calc(var(--safe-area-top)+var(--spacing)*4)] bg-white flex flex-col h-full">
      <main class="px-3">
         <slot name="header"></slot>
      </main>
      <main class="relative w-full grow">
         <aside
            class="absolute px-3 inset-0 overflow-y-auto no-scrollbar pt-4 pb-[calc(var(--safe-area-bottom)+var(--spacing)*20)]"
         >
            <slot name="content"></slot>
         </aside>
      </main>
      <div
         class="absolute inset-0 top-auto pointer-events-none h-20 bg-linear-to-t from-white/80 via-white/70 to-transparent z-50"
      />
   </section>
</template>
