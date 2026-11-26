/**
 * Faollik hodisalari va ilova sahifalarining tushunarli nomlari (Faollik sahifasi va foydalanuvchi yo'li uchun bitta joy).
 */

// Ilova route nomlari → tushunarli nom
export const PAGES: Record<string, string> = {
   home: "Bosh sahifa",
   categories: "Kategoriyalar ro'yxati",
   category: "Kategoriya ichi",
   "product-id": "E'lon sahifasi",
   favorites: "Sevimlilar",
   profile: "Profil",
   "my-ads": "Mening e'lonlarim",
   "create-select-category": "E'lon qo'yish: kategoriya",
   "create-select-city": "E'lon qo'yish: shahar",
   "create-product": "E'lon qo'yish: forma",
   "edit-product": "E'lonni tahrirlash",
   login: "Kirish sahifasi",
   vitrinas: "Vitrinalar",
   vitrina: "Vitrina sahifasi",
   "my-vitrina": "Mening vitrinam",
   help: "Yordam",
};

export const SOURCES: Record<string, string> = {
   direct: "Telegram: bot / menyu orqali",
   product: "E'lon havolasi (kanal, ulashish)",
   create: "\"E'lon qo'yish\" tugmasi (kanal)",
   // Kanaldagi qadalgan jonli post: bo'lim tugmalari (📱 Telefonlar, 🏠 Uylar...).
   // 2026-11-26 gacha kanal postlaridagi "Shunga o'xshash" tugmasi ham cat_ edi — eski postlardan kelganlar ham shu yerda
   cat: "Kanal: bo'lim tugmasi (jonli post)",
   cats: "Kanal: bo'lim tugmasi (jonli post)",
   like: "Kanal: «Shunga o'xshash» tugmasi",
   ss: "Kuzatuv xabari (bot)",
   my_ads: "E'lon muddati xabari (bot)",
   admin_reported: "Admin xabari (shikoyat)",
   browser: "Brauzer (sayt)",
   v: "Vitrina havolasi",
};

// Forma rad etilganda xato bergan maydonlar
const FIELDS: Record<string, string> = {
   title: "sarlavha",
   description: "tavsif",
   price: "narx",
   price_type_id: "narx turi",
   phone: "telefon",
   images: "rasmlar",
   category_id: "kategoriya",
   district_id: "shahar",
   parameters: "parametrlar",
   text: "matn",
};

export const pageLabel = (ref: string) => PAGES[ref] ?? ref;
export const sourceLabel = (ref: string) => SOURCES[ref] ?? ref;

/** "price,images" → "narx, rasmlar"; 403/429 — bloklangan / limit */
export function failedFieldsLabel(ref: string | null) {
   if (ref === "403") return "ruxsat yo'q (bloklangan)";
   if (ref === "429") return "limit tugagan";
   if (!ref || ref === "422") return "forma xato";
   return ref
      .split(",")
      .map((field) => FIELDS[field] ?? field)
      .join(", ");
}

export type JourneyTone = "default" | "success" | "danger" | "muted";

/** Yo'ldagi bitta hodisa: sarlavha, tafsilot va rang */
export function describeEvent(event: { e: string; r: string | null; label?: string | null }): {
   title: string;
   detail: string;
   tone: JourneyTone;
} {
   const ref = event.r ?? "";
   const name = event.label ?? (ref ? `#${ref}` : "");

   switch (event.e) {
      case "app_open":
         return { title: "Ilovani ochdi", detail: sourceLabel(ref), tone: "muted" };
      case "page":
         return { title: pageLabel(ref), detail: "", tone: "default" };
      case "product_view":
         return { title: "E'lonni ko'rdi", detail: name, tone: "default" };
      case "category_open":
         return { title: "Kategoriyani ochdi", detail: name, tone: "default" };
      case "contact_phone":
         return { title: "Sotuvchiga qo'ng'iroq", detail: ref.startsWith("v") ? "vitrina" : name, tone: "success" };
      case "contact_telegram":
         return { title: "Sotuvchiga Telegramda yozdi", detail: ref.startsWith("v") ? "vitrina" : name, tone: "success" };
      case "ad_created":
         return { title: "E'lon joyladi", detail: name, tone: "success" };
      case "ad_created_ai":
         return { title: "Matn bilan (AI) to'ldirib joyladi", detail: name, tone: "success" };
      case "ad_edited":
         return { title: "E'lonni tahrirladi", detail: name, tone: "default" };
      case "ad_activated":
         return { title: "E'lonni faollashtirdi / uzaytirdi", detail: name, tone: "success" };
      case "ad_deactivated":
         return { title: "E'lonni to'xtatdi", detail: name, tone: "muted" };
      case "ad_failed":
         return { title: "E'lon joylanmadi", detail: `Xato: ${failedFieldsLabel(event.r)}`, tone: "danger" };
      case "ai_draft":
         return { title: "Matndan (AI) qoralama oldi", detail: "", tone: "default" };
      case "ai_draft_failed":
         return { title: "AI qoralama bo'lmadi", detail: failedFieldsLabel(event.r), tone: "danger" };
      case "client_error":
         return { title: "Ilovada xato", detail: ref, tone: "danger" };
      case "district_change":
         return { title: "Shahar tanladi", detail: name, tone: "default" };
      case "favorite_add":
         return { title: "Sevimliga qo'shdi", detail: "", tone: "default" };
      case "saved_search_add":
         return { title: "Qidiruvni kuzatuvga qo'ydi", detail: "", tone: "default" };
      case "report":
         return { title: "Shikoyat qildi", detail: name, tone: "danger" };
      default:
         return { title: event.e, detail: ref, tone: "muted" };
   }
}
