# Vitrina: holati va keyingi rejalar

Vitrina — biznesning doimiy sahifasi (do'kon, menyu, portfolio, narxnoma, ko'chmas mulk).
E'londan farqi: muddati tugamaydi, ichida bo'limlar va ko'p mahsulot/ish bor, narx ixtiyoriy.

## 1. Tayyor (2026-11-14)

### Backend
- Jadvallar: `vitrinas`, `vitrina_sections`, `vitrina_items` (migratsiyalar `2026_11_14_10000*`).
- Modellar: `Vitrina`, `VitrinaSection`, `VitrinaItem`; rasmlar — `App\Support\VitrinaImage` (webp, `public/storage/vitrinas/...`).
- `VitrinaController`:
  - ommaviy: `GET /api/vitrinas?city_id=&type=`, `GET /api/vitrinas/{slug}`;
  - egasi: `GET|POST /api/my-vitrina`, bo'limlar va mahsulotlar CRUD (`/api/my-vitrina/sections`, `/api/my-vitrina/items`, fayllar uchun POST + multipart);
  - admin: `GET /api/admin/vitrinas?status=`, `PATCH /api/admin/vitrinas/{id}` (status, reject_reason, is_verified).
- Qoidalar: bir foydalanuvchi — bitta vitrina; yangi vitrina `pending`, admin tasdiqlagach ko'rinadi (adminniki darhol `approved`);
  rad etilgan vitrina tahrirlansa qayta `pending`; bo'lim o'chsa mahsulotlari "Boshqa"ga tushadi.
- Narx turlari: `fixed`, `from` ("...dan"), `negotiable`, `none` (portfolio).
- Shahar filtri e'lonlardagidek: shu shahar + butun viloyat (`district_id = null`).
- Testlar: `tests/Feature/VitrinaTest.php`.

### Frontend (mini app)
- Bosh sahifa: `VitrinaStrip` (stories kabi qator, tanlangan shahar bo'yicha).
- Sahifalar: `VitrinasPage` (katalog, turi bo'yicha filtr), `VitrinaPage` (`/v/:slug`), `MyVitrinaPage` (egasi uchun tahrirlash).
- Komponentlar: `resources/application/features/vitrina/` (`vitrinaKit.ts` — turlar, narx yozuvi, bog'lanish).
- Bo'limda rasm bo'lsa — kartalar to'ri, bo'lmasa — ixcham ro'yxat.
- Ulashish: `t.me/<bot>?startapp=v_<slug>` (`app.ts`).
- Profil → "Mening vitrinam"; admin panel → "Vitrinalar".

### Sinov ma'lumotlari
- `DemoVitrinaSeeder` — 7 ta vitrina (bittasi tasdiq kutmoqda), GD bilan yasalgan demo rasmlar.
- `DemoProductSeeder` — ~120 ta e'lon. Ikkalasi ham faqat `APP_ENV=local` da `db:seed` bilan ishlaydi.

## 2. Keyingi qadamlar (ustuvorlik bo'yicha)

1. **Qidiruvda "Vitrinalardan" bloki** — "divan" qidirilsa e'lonlar bilan birga vitrina mahsulotlari ham chiqsin
   (`ProductSearchService` mantiqi bilan, shahar filtri hisobga olinsin).
2. **Tasdiq/rad xabari** — admin tasdiqlasa yoki rad etsa, egasiga bot xabar yuborsin (`TelegramNotifier`).
3. **Mahsulotni e'lon qilib chiqarish** — vitrina mahsulotidan bir bosishda e'lon; e'lon kartasida "… vitrinasidan" belgisi,
   e'lon sahifasida "Sotuvchining vitrinasi bor" havolasi.
4. **Egasi uchun statistika** — ko'rishlar, qo'ng'iroq/Telegram bosishlar (`track(..., "v<id>")` allaqachon yozilyapti).
5. **Mahsulotlar tartibini o'zgartirish** (drag & drop) — `position` ustuni tayyor.
6. **Pullik imkoniyatlar** (keyinroq): bepul — 20 tagacha mahsulot; Pro — cheksiz, ro'yxatda yuqorida, statistika.

## 3. Reja: vitrina egasining Telegram kanaliga avtomatik post

Maqsad: vitrina egasi o'z kanalini ulaydi, bot vitrinadagi mahsulotlarni o'sha kanalga joylaydi, yangilaydi va olib tashlaydi.

### Kanalni ulash — foydalanuvchidan ID ham, link ham so'ralmaydi
- Ochiq kanalga `@username` bilan ham yozsa bo'ladi, lekin username o'zgarishi mumkin. Yopiq kanalga faqat raqamli ID bilan.
  Shuning uchun har doim raqamli `chat_id` saqlanadi.
- **Asosiy yo'l — `request_chat` tugmasi** (Bot API `KeyboardButtonRequestChat`):
  1. "Mening vitrinam" → "Kanalni ulash" → bot chati `startapp`/`/start connect_channel` bilan ochiladi.
  2. Bot `request_chat` tugmasini yuboradi: `chat_is_channel: true`,
     `bot_administrator_rights: {can_post_messages, can_edit_messages, can_delete_messages, can_pin_messages}`,
     `user_administrator_rights` — xuddi shu huquqlar (foydalanuvchi o'zi admin bo'lgan kanallargina chiqadi).
  3. Telegram kanal tanlash oynasini ochadi va botni shu huquqlar bilan admin qilishni o'zi taklif qiladi.
  4. Bot `chat_shared` xabarida `chat_id` oladi → vitrinaga yoziladi.
- **Zaxira yo'l — `my_chat_member` update**: botni kanalga qo'lda admin qilishsa, Telegram kim qo'shganini (`from.id`) yuboradi →
  `users.telegram_user_id` bo'yicha topib, uning vitrinasiga bog'lanadi.
- **Xavfsizlik**: bog'lashdan oldin `getChatMember(chat_id, user_telegram_id)` — status `creator` yoki `administrator` bo'lishi shart
  (begona kanalni o'z vitrinasiga ulab bo'lmasin). Botning o'z huquqlari ham `getChatMember(chat_id, bot_id)` bilan tekshiriladi.

### Bot kanalda nima qila oladi
| Amal | Holati |
|---|---|
| Post (rasm, albom, matn, tugma) | Bo'ladi (`can_post_messages`) |
| O'z postini tahrirlash (matn, narx, rasm) | Bo'ladi, vaqt chegarasi yo'q |
| Postni o'chirish | Telegram hujjatidagi umumiy cheklov: yuborilgandan keyin 48 soat ichida. Eski postlarda ishonchsiz |
| Pin qilish | Bo'ladi (`can_pin_messages`) |
| Albomdagi rasmlar sonini o'zgartirish | Bo'lmaydi (faqat bitta rasmni `editMessageMedia` bilan almashtirish) |

Shuning uchun eski post o'chirilmaydi — **tahrirlanadi**: "❌ Sotildi" / "Hozir yo'q" belgisi, tugma olib tashlanadi.

### Ma'lumotlar
- `vitrinas`: `channel_chat_id` (bigint, null), `channel_title`, `channel_username`, `channel_connected_at`, `channel_status` (`ok` | `no_rights` | `removed`).
- `vitrina_items`: `post_to_channel` (bool) — "kanalga ham chiqarilsin".
- Yangi jadval `vitrina_item_channel_posts`: `vitrina_item_id`, `chat_id`, `message_ids` (json — albomda bir nechta), `posted_at`.
  O'z kanalimiz uchun mavjud `product_telegram_ids` bilan bir xil g'oya.

### Sinxronlash
- Mavjud `SyncProductToTelegram` job va `TelegramProductService` mexanizmini qayta ishlatish: alohida `SyncVitrinaItemToChannel` job
  (`PUBLISH` / `UPDATE` / `UNAVAILABLE` / `DELETE`), navbat orqali, qayta urinish bilan.
- Post matni: nom, narx (`itemPrice` mantiqi serverda), qisqa tavsif, tugma "Vitrinada ko'rish" → `startapp=v_<slug>`.
- Hodisalar: mahsulot qo'shildi → post; narx/matn/rasm o'zgardi → tahrir; "Hozir yo'q" → belgi; o'chirildi → 48 soat ichida o'chirish,
  aks holda "Sotildi" deb tahrirlash.
- Ixtiyoriy: kanalda pin qilingan "Bizning vitrina" posti — bo'limlar ro'yxati va tugma.
- Telegram cheklovlari: bitta kanalga ~20 xabar/daqiqa — job'lar navbatda, `429 retry_after` hurmat qilinadi.

### Uzilishni kuzatish
- `my_chat_member` da bot `left` / `kicked` bo'lsa yoki huquqi olinsa → `channel_status` yangilanadi,
  vitrina sahifasida "Kanal uzildi", egasiga bot xabar yozadi.

### Qabul mezonlari
- Foydalanuvchi ID/link kiritmasdan kanalni 1 daqiqada ulaydi.
- Begona kanalni ulab bo'lmaydi (testlangan).
- Mahsulot qo'shish/tahrirlash/o'chirish kanalda 1 daqiqa ichida aks etadi; Telegram xatosi foydalanuvchi so'rovini sekinlashtirmaydi.
- Bot adminlikdan olinsa — tizim buzilmaydi, holat ko'rinadi.
