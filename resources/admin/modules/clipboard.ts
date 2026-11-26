/**
 * Buferdan o'qish (admin panel). Brauzerlar buferni faqat ruxsat bilan beradi:
 * - tugma bosilganda (readText) — telefon "Qo'yish" so'rashi mumkin;
 * - ruxsat avval berilgan bo'lsa (Chrome) — sahifaga qaytganda jim o'qib, taklif ko'rsatamiz.
 * Ruxsat bo'lmasa null qaytadi — foydalanuvchi Ctrl+V / "Qo'yish" bilan joylaydi.
 */

export const clipboardSupported = typeof navigator !== "undefined" && !!navigator.clipboard?.readText;

export async function readClipboardText(): Promise<string | null> {
   if (!clipboardSupported) return null;
   try {
      return await navigator.clipboard.readText();
   } catch {
      return null;
   }
}

/** Buferdagi rasmlar (skrinshot, nusxalangan rasm) */
export async function readClipboardImages(): Promise<File[]> {
   if (typeof navigator === "undefined" || !navigator.clipboard?.read) return [];
   try {
      const files: File[] = [];
      for (const item of await navigator.clipboard.read()) {
         const type = item.types.find((value) => value.startsWith("image/"));
         if (type) files.push(new File([await item.getType(type)], "clipboard.png", { type }));
      }
      return files;
   } catch {
      return [];
   }
}

/** Ruxsat avval berilganmi — so'ramasdan o'qish mumkinmi */
export async function canReadSilently(): Promise<boolean> {
   try {
      const status = await navigator.permissions.query({ name: "clipboard-read" as PermissionName });
      return status.state === "granted";
   } catch {
      return false;
   }
}

export type PostLink = { url: string; channel: string; post: number };

/** Matndagi ochiq kanal post havolalari: t.me/kanal/123, t.me/s/kanal/123, https://t.me/kanal/123?single */
export function parsePostLinks(text: string): PostLink[] {
   const found = new Map<string, PostLink>();
   for (const match of text.matchAll(/(?:https?:\/\/)?(?:www\.)?t(?:elegram)?\.me\/(?:s\/)?([A-Za-z][A-Za-z0-9_]{3,})\/(\d+)/gi)) {
      const channel = match[1];
      const post = Number(match[2]);
      const url = `https://t.me/${channel}/${post}`;
      found.set(url.toLowerCase(), { url, channel, post });
   }
   return [...found.values()];
}
