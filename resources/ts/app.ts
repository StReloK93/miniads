import "primeicons/primeicons.css";

import { createApp } from "vue";
import App from "./App.vue";
import PrimeVue from "primevue/config";
import { initTheme } from "./components/features/theme";
import router from "./router";
import { MyTheme } from "./configs/PrimeVueTheme";
initTheme();

const app = createApp(App);
app.use(PrimeVue, MyTheme);
app.use(router);
app.mount("#app");
