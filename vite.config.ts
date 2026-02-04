import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";
import vue from "@vitejs/plugin-vue";
import { resolve } from "path"; // Path modulini chaqiramiz
import Components from "unplugin-vue-components/vite";
// import { visualizer } from "rollup-plugin-visualizer";
import Icons from "unplugin-icons/vite";

export default defineConfig({
   // build: {
   //    chunkSizeWarningLimit: 1000,
   //    rollupOptions: {
   //       output: {
   //          manualChunks(id) {
   //             if (id.includes("node_modules/primevue")) {
   //                // Faqat adminApp'dan kelayotgan bo'lsa ham,
   //                // Vite uni shared deb o'ylamasligi uchun nomini aniq qilamiz
   //                return "admin-primevue";
   //             }
   //          },
   //       },
   //    },
   // },
   plugins: [
      Icons({
         compiler: "vue3",
      }),
      // visualizer({
      //    open: true, // Build tugagach brauzerda avtomatik ochiladi
      //    filename: "stats.html", // Natija shu faylga yoziladi
      //    gzipSize: true, // Gzip siqishdan keyingi hajmni ko'rsatadi
      //    brotliSize: true, // Brotli siqishdan keyingi hajmni ko'rsatadi
      // }),
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
