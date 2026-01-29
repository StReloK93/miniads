import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";
import vue from "@vitejs/plugin-vue";
import { resolve } from "path"; // Path modulini chaqiramiz
import Components from "unplugin-vue-components/vite";
import Icons from "unplugin-icons/vite";
import IconsResolver from "unplugin-icons/resolver";

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
      laravel({
         input: [
            "resources/application/app.css",
            "resources/application/app.ts",
            "resources/admin/adminApp.css",
            "resources/admin/adminApp.ts",
         ],
         refresh: true,
      }),
      tailwindcss(),

      Components({
         // 🔹 UI componentlar (BaseButton, Accordion, etc.)
         dirs: ["resources/shared/ui"],
         extensions: ["vue"],
         deep: true,
         dts: "components.d.ts",

         // 🔹 iconlarni component sifatida auto-import qilish
         resolvers: [
            IconsResolver({
               prefix: "", // <SunIcon /> bo‘lsin
               extension: "vue",
            }),
         ],
      }),
      Icons({
         compiler: "vue3",
         autoInstall: true,
      }),
   ],
   server: {
      cors: true, // CORS muammosini hal qiladi
   },

   resolve: {
      alias: {
         // '@' belgisini resources/ts papkasiga yo'naltiramiz
         "@": resolve(__dirname, "resources/application"),
         "@pages": resolve(__dirname, "resources/application/pages"),
         "@components": resolve(__dirname, "resources/application/components"),

         "@admin": resolve(__dirname, "resources/admin"),
         "@shared": resolve(__dirname, "resources/shared"),
      },
   },
});
