import { markRaw } from "vue";
import FieldImage from "@shared/ui/FieldImage.vue";
import FieldText from "@shared/ui/FieldText.vue";
import FieldMask from "@shared/ui/FieldMask.vue";
import FieldNumber from "@shared/ui/FieldNumber.vue";
import FieldTextarea from "@shared/ui/FieldTextarea.vue";
import FieldSelect from "@shared/ui/FieldSelect.vue";
import FieldColors from "@shared/ui/FieldColors.vue";
import FieldPhone from "@shared/ui/FieldPhone.vue";

export const Inputs = {
   FieldText: markRaw(FieldText),
   FieldNumber: markRaw(FieldNumber),
   FieldSelect: markRaw(FieldSelect),
   FieldTextarea: markRaw(FieldTextarea),
   FieldImage: markRaw(FieldImage),
   FieldMask: markRaw(FieldMask),
   FieldColors: markRaw(FieldColors),
   FieldPhone: markRaw(FieldPhone),
};
