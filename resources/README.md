# Frontend tuzilmasi

Ikkita ilova bor: **application** (Telegram Mini App / sayt) va **admin**. Ikkalasi ham **shared** dan foydalanadi.

```
resources/
├── shared/                  ← ikkala ilova uchun umumiy
│   ├── ui/                  ← umumiy UI: Base*, Field*, BackButton... (biznes mantiqsiz)
│   │   └── inputs.ts        ← forma konfiguratsiyasida ishlatiladigan Field* komponentlar ro'yxati
│   ├── entities/<Nom>/      ← API va ma'lumot: <Nom>Repo.ts, <Nom>Inputs.ts, use<Nom>.ts (store)
│   ├── composables/         ← umumiy hook'lar: useFetch, useTheme...
│   ├── store/               ← global holat (faqat auth)
│   ├── modules/             ← yordamchi funksiyalar: formatters, helpers
│   └── types/               ← TypeScript turlari
│
├── application/
│   ├── pages/               ← faqat route sahifalari (router/routes.ts da ko'rsatilganlar)
│   ├── features/            ← biznes bo'laklari, mavzu bo'yicha
│   │   ├── auth/            ← TelegramLoginCard, GuestLoginPage, requireAuth
│   │   ├── product/         ← ProductCard, skeleton'lar, useProductForm
│   │   ├── profile/         ← ProfileAccount, ProfileProductCard
│   │   ├── favorites/       ← FavoriteList
│   │   ├── category/        ← CategorySelector, CategoriesSlider
│   │   └── district/        ← DistrictSwitcher
│   ├── layouts/             ← sahifa karkasi: NavigationPage, FullPage, BottomNavigator, NavigationPageDecorator
│   ├── router/
│   └── modules/             ← faqat shu ilovaga xos ishga tushirish kodi (InitApp)
│
└── admin/                   ← admin panel (pages, components, router, modules)
```

## Yangi fayl qayerga qo'yiladi

1. **Route sahifasimi?** → `application/pages/`. Sahifada mantiq kam bo'lsin: ma'lumot olish va `features/` komponentlarini joylash.
2. **Komponentda biznes tushunchasi bormi** (e'lon, kategoriya, profil, sevimlilar)? → `application/features/<mavzu>/`.
3. **Biznesga bog'liq emasmi** (tugma, modal, input, tab)? → `shared/ui/`. Bu papkadagi komponentlar avtomatik ro'yxatdan o'tadi (`unplugin-vue-components`).
4. **API bilan ishlaydimi?** → `shared/entities/<Nom>/<Nom>Repo.ts`. Komponent ichida to'g'ridan-to'g'ri `api.get(...)` yozilmaydi.
5. **Bir nechta sahifada takrorlanadigan mantiqmi?** → shu feature ichida `use<Nom>.ts` (masalan `features/product/useProductForm.ts`).

## Qoidalar

- **`shared/` hech qachon `@/...` (application) yoki `@admin/...` dan import qilmaydi.** Aks holda admin bundle'iga mini app kodi tushib qoladi. Bog'liqlik yo'nalishi: `pages → features → shared`.
- **Store navigatsiya qilmaydi.** `useAuth` faqat sessiyani boshqaradi; login/logoutdan keyin qayerga o'tishni sahifa yoki `app.ts` hal qiladi.
- **Nomlash:**
  - `Base*` / `Field*` — faqat `shared/ui` dagi umumiy komponentlar.
  - Feature komponentlari — mavzu nomi bilan: `ProductCard`, `DistrictSwitcher`.
  - Sahifalar — `*Page.vue`.
- **Turlar:** yangi kodda `any` ishlatmang. API javobi va so'rov ma'lumoti uchun tur `shared/types` da bo'ladi (masalan, `IProduct`, `IProductPayload`), Repo metodlari shu turlarni qaytaradi.
- **Login talab qiladigan amal:** route uchun `meta: { requiresAuth: true }`, tugma uchun `if (!requireAuth()) return;` (`features/auth/requireAuth.ts`).

## Import alias'lari

| Alias | Papka |
|---|---|
| `@/` | `resources/application/` |
| `@pages/` | `resources/application/pages/` |
| `@admin/` | `resources/admin/` |
| `@shared/` | `resources/shared/` |
