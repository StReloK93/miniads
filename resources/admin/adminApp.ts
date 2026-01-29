import { createApp } from "vue";
import { createPinia } from "pinia";
import App from "@admin/AdminApp.vue";
import router from "@admin/router";
// import { useAuth } from "./store/useAuth";
// import { initTheme } from "./modules/theme";

// initTheme();
const app = createApp(App);

app.use(createPinia()); // Pinia birinchi bo'lishi shart (Store ishlatish uchun)
app.use(router);
app.mount("#app");
