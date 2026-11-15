import { t } from "@shared/i18n";

/** Server xatosidan foydalanuvchiga ko'rsatiladigan matn: validatsiyaning birinchi xatosi yoki umumiy xabar */
export function errorMessage(error: unknown): string {
   const data = (error as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } })?.response?.data;
   const firstError = data?.errors ? Object.values(data.errors)[0]?.[0] : null;
   return firstError || data?.message || t("vitrina.form.saveFailed");
}
