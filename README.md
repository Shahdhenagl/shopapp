# MODIST — E-commerce Backend + Admin Dashboard

مشروع متكامل لمتجر **MODIST**:

| المجلد | الوصف | التشغيل |
|---|---|---|
| [`api/`](./api) | **Laravel 11** REST API (Sanctum, Paymob, i18n عربي/إنجليزي, Clean Architecture + SOLID) | محتاج PHP 8.3 + Composer + MySQL |
| [`dashboard/`](./dashboard) | **React + Vite + TypeScript** لوحة تحكم أدمن متكاملة | محتاج Node فقط — **يشتغل فورًا** |
| [`MODIST-Laravel-Backend-Plan.md`](./MODIST-Laravel-Backend-Plan.md) | البلان المعماري الكامل والعقد (API contract) | — |

الاتنين بيتكلموا نفس العقد: `/api/v1` (auth بترجع `{token,user}` flat، والباقي `{data:...}`).

---

## 1) الداش بورد (يشتغل دلوقتي — Mock Mode)

مش محتاج باك إند. بيشتغل ببيانات وهمية مطابقة للـ seeders.

```bash
cd dashboard
npm install
npm run dev          # http://localhost:5173
```

**تسجيل الدخول التجريبي:** `admin@modist.test` / `password`

المميزات: لوحة إحصائيات + رسومات، إدارة منتجات (CRUD كامل بحقول عربي/إنجليزي + ألوان `#AARRGGBB` + مقاسات)، كاتيجوريز، أوردرات، برومو كود، يوزرز، تبديل لغة EN/AR مع RTL، وثيم فاتح/غامق.

**للتحويل للباك إند الحقيقي:** انسخ `.env.example` إلى `.env` وحط `VITE_USE_MOCK=false` (البروكسي `/api → http://localhost:8000` مظبوط بالفعل).

---

## 2) الباك إند (Laravel — محتاج تنصيب أول مرة)

### المتطلبات على ويندوز
1. **PHP 8.3+** — [windows.php.net/download](https://windows.php.net/download) (فعّل امتدادات `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`).
2. **Composer** — [getcomposer.org](https://getcomposer.org/download/).
3. **MySQL 8** (أو استخدم SQLite للتجربة السريعة).

### التشغيل
```bash
cd api
composer install
copy .env.example .env
php artisan key:generate
# عدّل .env: DB_DATABASE / DB_USERNAME / DB_PASSWORD  (و PAYMOB_* للدفع)
php artisan migrate --seed
php artisan serve            # http://localhost:8000
```

- الـ API base: `http://localhost:8000/api/v1`
- الاختبارات: `php artisan test`  ·  التنسيق: `vendor/bin/pint`  ·  التحليل: `vendor/bin/phpstan analyse`

---

## 3) الربط بين الاتنين

1. شغّل الباك إند (`php artisan serve`) على المنفذ 8000.
2. في `dashboard/.env` حط `VITE_USE_MOCK=false`.
3. شغّل `npm run dev` — الداش بورد هيكلّم الـ API الحقيقي من غير أي تعديل في الكود.

> نفس العقد ده بيخدم تطبيق الموبايل (Flutter) برضه — راجع البلان للتفاصيل والـ switchover.

---

## بنية المشروع باختصار

```
SHOP/
├─ api/          Laravel 11 — Domain/ Infrastructure/ Http/ (4 طبقات Clean Architecture)
├─ dashboard/    React + Vite + TS — api/ components/ pages/ store/ mock/
└─ MODIST-Laravel-Backend-Plan.md
```
