import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";
import vue from "@vitejs/plugin-vue";
import Components from "unplugin-vue-components/vite";
import { PrimeVueResolver } from "@primevue/auto-import-resolver";
import { resolve } from "path"; // Path modulini chaqiramiz

export default defineConfig({
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
      watch: {
         ignored: ["**/storage/framework/views/**"],
      },
      // host: "0.0.0.0", // Hamma IP-lardan ulanishga ruxsat
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
