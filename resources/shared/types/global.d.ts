export {};

declare global {
   var ENV: {
      TELEGRAM_BOT_NAME: string;
      TELEGRAM_BOT_ID: string;
   };

   // telegram-widget.js (brauzerdagi Telegram login) yuklangandan keyin paydo bo'ladi
   interface Window {
      Telegram?: {
         Login?: {
            auth(
               options: { bot_id: string; request_access?: "write"; lang?: string },
               callback: (user: Record<string, unknown> | false) => void,
            ): void;
         };
      };
   }
}
