import { definePreset, palette } from "@primeuix/themes";
const customPalette = palette("#0ea5e9");
import Aura from "@primeuix/themes/aura";

export const MyTheme = {
   theme: {
      preset: definePreset(Aura, {
         semantic: {
            primary: customPalette,
         },
      }),
      options: {
         prefix: "p",
         darkModeSelector: ".dark",
         cssLayer: false,
      },
   },
};
