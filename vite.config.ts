import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";
import vue from "@vitejs/plugin-vue";
import Components from "unplugin-vue-components/vite";
import { PrimeVueResolver } from "@primevue/auto-import-resolver";
import { resolve } from "path"; // Path modulini chaqiramiz

export default defineConfig({
   build: {
      minify: false, // Kodni qisqartirmaydi (Eng ko'p vaqtni shu tejaydi)
      cssMinify: false, // CSS-ni qisqartirmaydi
      sourcemap: false, // .map fayllarini yaratmaydi
      target: "esnext", // Eski brauzerlar uchun kodni o'zgartirib o'tirmaydi
      modulePreload: false, // Preload skriptlarini o'chiradi
      reportCompressedSize: false, // Build oxirida hajmni hisoblamaydi (tezroq tugaydi)
      rollupOptions: {
         // Keraksiz yuklamalarni cheklash
      },
   },
   plugins: [
      vue(),
      Components({
         resolvers: [PrimeVueResolver()],
      }),
      laravel({
         input: ["resources/css/app.css", "resources/ts/app.ts"],
         refresh: true,
      }),
      tailwindcss(),
   ],
   server: {
      cors: true, // CORS muammosini hal qiladi
   },

   resolve: {
      alias: {
         // '@' belgisini resources/ts papkasiga yo'naltiramiz
         "@": resolve(__dirname, "resources/ts"),
         "@pages": resolve(__dirname, "resources/ts/pages"),
         "@components": resolve(__dirname, "resources/ts/components"),
      },
   },
});
