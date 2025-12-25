import { definePreset, palette } from "@primeuix/themes";
const customPalette = palette("#0ea5e9");
import Aura from "@primeuix/themes/aura";

export const MyPreset = definePreset(Aura, {
   semantic: {
      primary: customPalette, // to'liq {50: ..., 100: ...} object
   },
});

export const MyTheme = {
   theme: {
      preset: MyPreset,
      options: {
         prefix: "p",
         darkModeSelector: ".p-dark",
         cssLayer: false,
      },
   },
};
