/** Sanalar bilan ishlash (admin panel): mahalliy vaqt bo'yicha YYYY-MM-DD */

const MONTHS = ["yanvar", "fevral", "mart", "aprel", "may", "iyun", "iyul", "avgust", "sentabr", "oktabr", "noyabr", "dekabr"];
const WEEKDAYS = ["Yakshanba", "Dushanba", "Seshanba", "Chorshanba", "Payshanba", "Juma", "Shanba"];
const WEEKDAYS_SHORT = ["Ya", "Du", "Se", "Ch", "Pa", "Ju", "Sh"];

export function toKey(date: Date): string {
   const pad = (value: number) => String(value).padStart(2, "0");
   return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

export function fromKey(key: string): Date {
   const [year, month, day] = key.split("-").map(Number);
   return new Date(year, month - 1, day);
}

export function todayKey(): string {
   return toKey(new Date());
}

export function shiftKey(key: string, days: number): string {
   const date = fromKey(key);
   date.setDate(date.getDate() + days);
   return toKey(date);
}

export function isValidKey(value: unknown): value is string {
   return typeof value === "string" && /^\d{4}-\d{2}-\d{2}$/.test(value) && value <= todayKey();
}

/** "Bugun", "Kecha" yoki "24-noyabr" */
export function relativeLabel(key: string): string {
   if (key === todayKey()) return "Bugun";
   if (key === shiftKey(todayKey(), -1)) return "Kecha";
   const date = fromKey(key);
   const sameYear = date.getFullYear() === new Date().getFullYear();
   return `${date.getDate()}-${MONTHS[date.getMonth()]}${sameYear ? "" : ` ${date.getFullYear()}`}`;
}

/** "Seshanba, 24-noyabr" */
export function longLabel(key: string): string {
   const date = fromKey(key);
   return `${WEEKDAYS[date.getDay()]}, ${date.getDate()}-${MONTHS[date.getMonth()]}`;
}

export function weekdayShort(key: string): string {
   return WEEKDAYS_SHORT[fromKey(key).getDay()];
}

export function dayOfMonth(key: string): number {
   return fromKey(key).getDate();
}
