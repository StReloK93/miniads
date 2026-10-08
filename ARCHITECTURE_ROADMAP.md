# Miniads arxitekturasini kengayishga tayyorlash yo'l xaritasi

## Maqsad va yondashuv

Miniads hozir Laravel 12 API/backend va Vue 3 + TypeScript + Pinia frontenddan iborat bitta ilova. Bu kichik va o'rta yuklama uchun yaxshi boshlang'ich nuqta. Maqsad — mavjud ilovani birdaniga qayta yozish yoki mikroservislarga bo'lish emas; funksiyalarni bosqichma-bosqich ishonchli, o'lchanadigan va kerak bo'lganda gorizontal kengayadigan **modulli monolit**ga olib borish.

Quyidagi kuzatuvlar tekshirilgan kod yo'llariga asoslangan. Bu hujjatning o'zi arxitektura refaktori emas: kod o'zgartirilgani yo'q, bajarilishi kerak bo'lgan ishlar ketma-ketligi va qabul mezonlari yozildi.

## Hozir ko'ringan asosiy kamchiliklar

| Yo'nalish | Hozirgi holat / xavf | Qayerda ko'rinadi |
|---|---|---|
| API va validatsiya | E'lon yaratish/yangilash so'rovlari servisga umumiy `Request` va `$request->all()` orqali uzatiladi. Kiritiladigan ma'lumot uchun alohida, aniq kontrakt va validatsiya chegarasi ko'rinmaydi. | `ProductController`, `ProductService` |
| Xatolarni boshqarish | API 5xx javoblari endi ichki exception matnini oshkor qilmaydi; Laravel exception handler xatoni loglaydi va mijozga umumiy xabar hamda `INTERNAL_SERVER_ERROR` kodini qaytaradi. | `bootstrap/app.php`, `ProductController` |
| So'rovning vazifasi ko'pligi | E'lon yaratish yo'li bazaga yozish, rasmni qayta ishlash va Telegram'ga yuborishni bir HTTP so'rovida bajaradi. Tashqi servis sekinlashsa foydalanuvchi so'rovi ham sekinlashadi yoki qisman bajarilishi mumkin. | `ProductController`, `ProductService`, `TelegramProductService` |
| Atomarlik va fayllar | E'lon, parametrlar va rasmlar bir nechta amalda saqlanadi; rasm ombori `public/storage` yo'liga bevosita bog'langan. Xatoda DB yozuvlari va fayllar o'zaro mos kelmasligi, serverlar ko'payganda faylning boshqa instansiyada topilmasligi mumkin. | `ProductService` |
| API javoblari | Turli controllerlarda Eloquent modeli bevosita qaytariladi va mahsulot ro'yxatlari `get()` bilan to'liq olinadi. Javob shakli modelning ichki tuzilishiga bog'lanadi, katta ro'yxatlar esa ortiqcha ma'lumot va xotira sarflaydi. | `ProductController`, `CategoryController` |
| Konfiguratsiya | Telegram bot tokeni middleware va auth servisida `env()` orqali to'g'ridan-to'g'ri olinadi. Konfiguratsiya Laravel config qatlamidan o'qilishi kerak, ayniqsa config cache ishlatiladigan deployda. | `TelegramAuth`, `AuthService` |
| Frontend API va autentifikatsiya | API client bor, ammo token va user holati store ichida takror-takror o'rnatiladi; access token `localStorage`da saqlanadi. Bu auth logikasini tarqatadi va XSS yuz bersa token xavf ostida qoladi. | `useFetch.ts`, `useAuth.ts` |
| Frontend xatolari | Ilova ishga tushishidagi umumiy `catch` xatoni yutadi; ayrim auth xatolari faqat konsolga yoziladi. Foydalanuvchi uchun izchil xato holati hamda ishlab chiqarishdagi kuzatuv yetishmaydi. | `app.ts`, `useAuth.ts` |
| Avtomatlashtirilgan tekshiruv | Ko'rinadigan testlar hozircha Laravel'ning namunaviy testlari. Muhim auth, e'lon, qidiruv va frontend oqimlari uchun regresion himoyasi yo'q yoki hali kiritilmagan. | `tests/` |

> Jadvaldagi “ko'rinmaydi” yoki “yo'q” degan xulosalar faqat ko'rib chiqilgan kod va fayllarga taalluqli. Ishni boshlashdan oldin joriy branch, production sozlamalari va mavjud monitoring bilan solishtiring.

## Amalga oshirish tartibi

### 0-bosqich — poydevor va o'lchovlar

**Maqsad:** o'zgarishlar xavfsiz kiritilishi va hozirgi tizim bilan taqqoslanishi uchun minimum poydevor yaratish.

1. Asosiy foydalanuvchi oqimlarini yozib oling: Telegram login, e'lon yaratish/tahrirlash, qidiruv, sevimlilar, admin kategoriyalari.
2. API va frontend uchun test strategiyasini belgilang; eng muhim oqimlardan feature testlarni boshlang.
3. CI'da backend testlari, PHP format tekshiruvi, frontend type-check/build va lintni ishga tushiring.
4. Production bazasi va rasmlari uchun tiklab ko'rilgan backup, deploydan oldingi migratsiya tekshiruvi va rollback tartibini belgilang.
5. Javob vaqti, 5xx xatolar, queue kutish vaqti, DB sekin so'rovlari kabi boshlang'ich metrikalarni yig'ing.

**Qabul mezoni:** har bir keyingi refaktor CI'da tekshiriladi; muhim oqimlarning hozirgi xulqi test bilan mustahkamlangan; deploy va tiklash qadamlari hujjatlashtirilgan.

### 1-bosqich — backend API chegaralarini mustahkamlash

**Maqsad:** kiruvchi ma'lumot, javob va xato formatini aniq va barqaror qilish.

1. Yaratish, tahrirlash va boshqa yozuvchi amallar uchun Laravel `FormRequest`lar yarating. Faqat validatsiyadan o'tgan maydonlarni servisga uzating; `$request->all()`ni ommaviy yozish uchun ishlatmang.
2. E'lon egasi va admin huquqlarini `Policy`/authorization orqali ifodalang. Tekshiruvlar controller ichida takrorlanmasin.
3. `API Resource`lar bilan tashqi JSON shaklini Eloquent modeldan ajrating. API'da qaysi maydonlar ko'rinishi aniq bo'lsin.
4. Ro'yxatlar uchun limitli pagination va ruxsat etilgan filter/sort parametrlarini kiriting. Search, latest va “mening e'lonlarim” natijalariga bir xil limit qo'llang.
5. Xatolarni Laravel'ning standart exception handleri orqali log qiling; mijozga barqaror xato kodi/xabari va mos HTTP status bering. Xom exception yoki stack trace'ni API javobiga chiqarmang.
6. API marshrutlarini resurs/feature bo'yicha guruhlang; breaking change zarur bo'lgandagina versiyalashni (`/api/v1`) kiriting.
7. Token, URL, sana va statuslar uchun frontend va backend bir xil tushunadigan kontrakt belgilang; OpenAPI hujjatini qo'lda yoki generator bilan yuriting.

**Qabul mezoni:** yaroqsiz kirish ma'lum 4xx javob oladi; mijozga ichki exception tafsiloti chiqmaydi; ro'yxat so'rovlari sahifalanadi; Resource va API kontraktiga feature testlar qo'shiladi.

### 2-bosqich — backendni modulli monolitga ajratish

**Maqsad:** mas'uliyatlarni bo'lish, lekin hozircha bitta deploy va bitta ilova sifatida qolish.

1. Kodni biznes sohalari bo'yicha bosqichma-bosqich guruhlang: `Identity` (Telegram login), `Catalog` (kategoriya/parametr), `Listings` (e'lon/rasm), `Favorites`, `Search`, `Integrations/Telegram`.
2. Har bir modulda HTTP adapterlari (Controller/Request/Resource), use case yoki servislar, model va testlar aniq ajratilsin. Bu uchun yangi framework yoki katta abstraksiyalar shart emas — amaldagi Laravel patternlarini saqlang.
3. Controller faqat HTTP so'rov/javobini boshqarsin. Biznes qoidalari va bir nechta modelni o'zgartiradigan use case servisda bo'lsin.
4. Fayl saqlash va Telegram yuborish kabi tashqi bog'liqliklarni Laravel `Storage`/interfeys yoki mavjud servis adapterlari orqali chaqiring; domen qoidalarini vendor API chaqirig'iga bog'lamang.
5. `env()` faqat `config/*.php` ichida ishlasin. Telegram tokeni kabi maxfiy qiymatlar `config('services.telegram...')` orqali o'qilsin.
6. Eski route va JSON javoblarini birdan buzmasdan, kodni modullar bo'yicha testlar bilan ketma-ket ajrating.

**Qabul mezoni:** modullar orasidagi bog'liqlik yo'nalishi aniq bo'lsin; controller biznes qoidalarini o'zida saqlamasin; mavjud klientlar uchun kontrakt testlari o'tishi kerak.

### 3-bosqich — tranzaksiya, queue va fayl ombori

**Maqsad:** uzoq yoki tashqi ishlarni HTTP javobidan chiqarish va qisman muvaffaqiyatsizliklarni nazorat qilish.

1. E'lon va unga tegishli DB yozuvlarini `DB::transaction()` bilan atomar qiling. Rasmni saqlash/o'chirishdagi muvaffaqiyatsizlik uchun kompensatsiya yoki qayta tiklash qadamini belgilang.
2. Telegram publish'ni queue job'ga ko'chiring; faqat DB commitdan keyin queue'ga yuboring. Retry/backoff, failed jobs, log va takroriy jobni xavfsiz qayta ishlashni (idempotency) qo'shing.
3. Telegram yuborish muvaffaqiyatsiz bo'lsa, e'lon yaratish so'rovini bekor qilmang. Holatni kuzatish va qayta yuborish uchun job natijasi/Telegram message identifikatorini saqlang.
4. Mahsulot rasmlarini `Storage` diskidan foydalanib saqlang. Local diskni hozircha ishlatish mumkin, keyinchalik S3-mos object storage'ga o'tish konfiguratsiya bilan hal bo'lsin.
5. Katta yoki ko'p rasm uchun validatsiya, o'lcham cheklovi, WebP/thumbnail ishlovi va tozalash siyosatini belgilang.
6. Queue backendini deployment hajmiga mos tanlang (odatda Redis); workerlar soni, timeout va retry siyosatini metrikalar asosida belgilang.

**Qabul mezoni:** sekin Telegram javobi e'lon API javobini ushlab turmaydi; bir e'lon uchun takroriy Telegram xabari yaratilmaydi; fayl yo'li bitta web-instansiyaga qattiq bog'liq emas.

### 4-bosqich — ma'lumotlar bazasi va qidiruvni yuklamaga tayyorlash

**Maqsad:** ma'lumot ko'payganda so'rovlarni o'lchab optimallashtirish.

1. DB slow query log/APM'dan foydalanib, `EXPLAIN` natijalari orqali haqiqiy sekin joylarni aniqlang. Indeksni taxmin bilan emas, tez-tez ishlatiladigan `WHERE`, `JOIN`, `ORDER BY` va foreign key'lar asosida qo'shing.
2. E'lonlar uchun status/district/category/date kombinatsiyalarini o'lchab, kerak bo'lgan kompozit indekslarni alohida migratsiya bilan qo'shing. Katta jadvalda indeks yaratish usulini production DB'ga mos tanlang.
3. N+1 so'rovlarni aniqlang; faqat kerakli relationlarni eager-load qiling. Modeldagi global `$with` ro'yxatini endpoint ehtiyojiga qarab ko'rib chiqing.
4. Page raqamiga asoslangan pagination yetarli bo'lmagan oqimlarda cursor paginationni ko'rib chiqing.
5. Kategoriya kabi sekin o'zgaradigan ma'lumotlar uchun cache key, TTL, invalidation va yangilash mas'ulini belgilang. Cache ishlamaganida tizim asosiy ma'lumotlar bazasidan to'g'ri ishlashi kerak.
6. Laravel Scout qidiruv haydovchisi, reindex tartibi va DB bilan qidiruv indeksi o'rtasidagi kechikish/kelishmovchilikni hujjatlashtiring. Qidiruv hajmi talab qilmaguncha yangi search infratuzilmasi qo'shmang.

**Qabul mezoni:** asosiy endpointlar uchun representative dataset'da p95 javob vaqti, query soni va DB yuklamasi o'lchanadi; indekslar va qidiruv sinxronizatsiyasi migratsiya/test bilan tekshiriladi.

### 5-bosqich — frontend modullari, turlar va holatlar

**Maqsad:** frontend o'sishini boshqariladigan qilish va backend API bilan driftni kamaytirish.

1. Hozirgi `application`, `admin`, `shared` bo'linishini saqlab, ichki kodni feature/domain bo'yicha tartiblang: masalan `features/listings`, `features/search`, `features/auth`, `features/favorites`, `features/categories`.
2. API chaqiruvlarini bitta HTTP client'da jamlang: base URL, auth header/cookie, timeout, response parsing, error mapping va telemetry bir joyda bo'lsin. Auth holatini barcha component/store'larga yoymang.
3. Backend Resource/OpenAPI'dan API turlarini yaratish imkoniyatini tanlang; bu imkonsiz bo'lsa ham `IProduct` kabi turlarni haqiqiy JSON kontraktiga moslang, yangi kodda `any` qo'shmang.
4. Pinia store'dan domen holati uchun foydalaning. Vaqtinchalik form holati, server keshi va global user holatini aralashtirmang; komponentlarda data-fetch logikasini takrorlamang.
5. Foydalanuvchiga ko'rinadigan error/loading/empty/retry holatlarini bir xil qiling. Xatolarni bo'sh `catch` bilan yutmang.
6. Route-level lazy loading'ni saqlang, production bundle hajmini o'lchang va og'ir admin/UI kutubxonalarini foydalanuvchi ilovasining dastlabki yuklanishiga qo'shmang.
7. Token saqlash usulini ongli ravishda tanlang: cookie asosidagi HttpOnly/Secure/SameSite yoki BFF XSS yuz berganda localStorage'dagi token o'g'irlanishi xavfini kamaytiradi, lekin CSRF himoyasi va qo'shimcha sozlamalarni talab qiladi. Telegram Mini App autentifikatsiyasi hamda deployment domeniga mosligini tekshirmasdan usulni almashtirmang; qarorni tahdidlar modeli, token muddati/bekor qilinishi va integratsion testlar bilan tasdiqlang.
8. Muhim form, auth, search va e'lon oqimlariga unit/component testlarni qo'shing; API kontrakt testlari bilan birga CI'da ishga tushiring.

**Qabul mezoni:** turlar va API javoblari bir-biriga mos; auth va xato boshqaruvi yagona; route chunklari buildda ajralgan; muhim ekranlar loading/error/empty holatlarini to'g'ri ko'rsatadi.

### 6-bosqich — production kuzatuvi va gorizontal kengaytirish

**Maqsad:** bir nechta app/worker instansiyasini ishonchli ishlatish.

1. Ilovani stateless qiling: session/cache/queue umumiy Redis yoki mos xizmatda; foydalanuvchi yuklagan rasmlar shared object storage'da; hech bir muhim holat bitta instansiya diskida qolmasin.
2. Nginx/load balancer ortida bir nechta PHP app instansiyasini ishga tushiring. Health/readiness endpoint, graceful deploy va worker restart tartibini sozlang.
3. Structured log, request/correlation ID, exception tracking, APM va biznes metrikalarini ulang. Maxfiy token va shaxsiy ma'lumotlarni logga yozmang.
4. Rate limitni login, qidiruv, upload va yozuvchi endpointlarda qo'llang; limitni real trafik va suiiste'mol holatlariga qarab moslang.
5. DB backup/restore mashqlarini o'tkazing, migratsiya vaqtini, ulanishlar hovuzi va DB ulanishlari limitini kuzating. Read replica'ni faqat o'qish yuklamasi bo'yicha o'lchovlar zarur deb ko'rsatgandan keyin qo'shing.
6. Queue lag, failed jobs, tashqi API xatolari, DB connection saturation, p95/p99 va frontend JS xatolari uchun ogohlantirishlar o'rnating.
7. O'sish paytida faqat bitta aniqlangan to'siqni bartaraf eting: app replica, queue worker, object storage, cache, keyin DB/search. Barcha qatlamlarni birdan almashtirmang.

**Qabul mezoni:** app instansiyasi ishdan chiqsa foydalanuvchi rasmlari va holati yo'qolmaydi; queue'ni alohida kengaytirish mumkin; tiklash va uzilish bo'yicha ogohlantirishlar hamda runbook sinovdan o'tgan.

### 7-bosqich — xizmatlarni ajratish kerakligini o'lchovlar asosida hal qilish

Mikroservisni birinchi qadam sifatida tanlamang. Faqat bir domen uchun alohida deploy tezligi, mustaqil jamoa, resurs profili yoki nosozlik chegarasi zarur bo'lsa, uni ajratishni baholang. Avval modul ichidagi aniq interfeys va ma'lumot egaligini belgilang; keyin event/API kontrakti, monitoring, qayta urinish/idempotency, deploy va ma'lumot migratsiyasi xarajatini hisoblang.

**Ajratish mezoni:** aniq operatsion yoki jamoaviy muammo ko'rsatilgan, modulli monolit uni iqtisodiy hal qila olmasligi isbotlangan, xizmatlararo nosozlik va kuzatuv xarajatlari qabul qilingan bo'lishi kerak.

## Nega aynan shu tartib?

1. **Avval o'lchov va test:** regressiya va hozirgi ishlash darajasi ma'lum bo'lmasa, “tezlashdi” yoki “xavfsizroq bo'ldi” deb tasdiqlab bo'lmaydi.
2. **Keyin API chegaralari:** frontend va backend bir xil shartnomada ishlashi modullashtirish va mustaqil o'zgarishlar uchun shart.
3. **So'ng modullar va async ishlar:** mas'uliyat aniq bo'lgach, uzoq tashqi amallarni queue'ga ko'chirish osonroq va xavfsizroq.
4. **Keyin indeks/cache:** optimallashtirish real bottleneck metrikalariga tayanadi; erta cache noto'g'ri ma'lumot va murakkablik keltirishi mumkin.
5. **Oxirida infratuzilmani ko'paytirish:** bir nechta instansiya shared state va shared file storage bo'lmasdan to'g'ri ishlamaydi.
6. **Mikroservis — yakuniy imkoniyat, majburiy maqsad emas:** tarmoq, kuzatuv, deploy va ma'lumotlararo kelishuv xarajatini faqat isbotlangan ehtiyoj oqlaydi.

## Asos bo'ladigan tamoyil va qoidalar

- **Separation of Concerns / Single Responsibility (SOLID-SRP):** controller HTTPni, use case biznes jarayonini, adapter tashqi servisni boshqaradi.
- **Dependency Inversion (SOLID-DIP):** biznes oqimi Telegram SDK yoki lokal diskga to'g'ridan-to'g'ri bog'lanmaydi; tashqi tizim adapter orqali chaqiriladi.
- **DRY va yagona haqiqat manbai:** API kontrakti, auth siyosati, xato formati va konfiguratsiya bir joyda belgilanadi.
- **Modular Monolith / High Cohesion, Low Coupling:** avval bitta deploy ichida chegaralar; jamoa va yuklama isbotlamaguncha servislarni ajratmaslik.
- **Fail safely / idempotency:** tashqi integratsiya vaqtincha ishlamasa core e'lon saqlanadi; qayta urinish dublikat natija bermaydi.
- **Database transaction va backward-compatible migration:** DB'dagi bog'liq yozuvlar atomar; production migratsiyalari ma'lumotni yo'qotmaydigan va ortga qaytish rejali bo'ladi.
- **OWASP secure defaults:** input validation, least privilege, secret management, rate limit, safe error response va token saqlash tahdid modeli bilan hal qilinadi.
- **12-Factor App:** konfiguratsiya muhitdan, log stdout/markaziy tizimga, ilova instansiyalari almashtiriladigan bo'lishi kerak.
- **Measure before optimize:** indeks, cache, replica va servis ajratish profiling/production metrikasiga asoslanadi.

## Birinchi amaliy sprint uchun tavsiya

1. 0-bosqichdan CI va muhim oqimlar uchun testlarni tayyorlash.
2. `ProductStoreRequest`/`ProductUpdateRequest`, `ProductResource` va pagination kontraktini e'lon API'siga kiritish.
3. Controller'dagi umumiy exception javoblarini olib tashlab, exception handler va logging'dan foydalanish; response'dan xom `error`ni olib tashlash.
4. Telegram publish'ni transaction commit'dan keyin ishlaydigan idempotent queue job'ga ko'chirish.
5. Frontend API client va auth state'ni birlashtirish; token saqlash usulini Mini App deployment'i va threat model asosida hal qilmaguncha o'zgartirmaslik.

Bu ishlar birinchi navbatdagi taklif; har birini alohida kichik PR, mos test va bosqichli deploy bilan bajaring.
