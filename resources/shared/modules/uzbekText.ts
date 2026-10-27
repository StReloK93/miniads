/**
 * Serverdagi App\Support\UzbekText bilan bir xil qoida (frontenddagi qidiruvlar uchun):
 * kirill → lotin, tutuq belgilari olib tashlanadi, kichik harf. "O‘g‘il", "ogil", "Ўғил" → "ogil".
 */
const CYRILLIC: Record<string, string> = {
   ш: "sh", щ: "sh", ч: "ch", ё: "yo", ю: "yu", я: "ya", ц: "ts",
   ў: "o", қ: "q", ғ: "g", ҳ: "h",
   а: "a", б: "b", в: "v", г: "g", д: "d", е: "e", ж: "j", з: "z", и: "i", й: "y",
   к: "k", л: "l", м: "m", н: "n", о: "o", п: "p", р: "r", с: "s", т: "t", у: "u",
   ф: "f", х: "x", ы: "i", э: "e", ъ: "", ь: "",
};

export function normalizeUzbek(text: string | null | undefined): string {
   if (!text) return "";
   return text
      .toLowerCase()
      .replace(/[а-яёўқғҳ]/g, (char) => CYRILLIC[char] ?? char)
      .replace(/['ʻʼ’‘`´]/g, "")
      .replace(/[^a-z0-9]+/g, " ")
      .trim();
}

/** Barcha so'zlar matnda bormi (so'z boshidan): "kvart 3" → "3 xonali kvartira" */
export function matchesUzbek(query: string, text: string | null | undefined): boolean {
   const terms = normalizeUzbek(query).split(" ").filter(Boolean);
   if (!terms.length) return true;
   const haystack = ` ${normalizeUzbek(text)}`;
   return terms.every((term) => haystack.includes(` ${term}`));
}
