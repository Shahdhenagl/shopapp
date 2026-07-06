# رفع MODIST على Hostinger — shopapp.pixelmindeg.com

دليل رفع الـ **Laravel API** + **لوحة الأدمن (Dashboard)** على استضافة Hostinger المشتركة (hPanel)، على نفس السابدومين:

- `https://shopapp.pixelmindeg.com/api/v1` → الـ API (التطبيق يكلّمه)
- `https://shopapp.pixelmindeg.com/dashboard` → لوحة الأدمن (مبنية جاهزة جوه `public/dashboard`)
- `https://shopapp.pixelmindeg.com/up` → فحص صحة الـ API

> الداشبورد دلوقتي بيشتغل على **بيانات تجريبية (mock)** لحد ما نبني الـ Admin API (`/admin/v1`). الـ API بتاع التطبيق شغّال حقيقي 100%.

> **مطلوب SSH** (متاح في خطة Business فأعلى). فعّله من hPanel → Advanced → SSH Access. لو خطتك Premium من غير SSH، رقّيها لـ Business عشان نشغّل `composer` و`artisan`.

---

## 0) اللي اتجهّز محليًا (تم ✅)
- الداشبورد اتبنى وانحط جوه `api/public/dashboard/` (مع `.htaccess` للـ SPA).
- ملف `api/.env.production` جاهز (هتملأ بيانات قاعدة البيانات بس).

---

## 1) أنشئ السابدومين
hPanel → **Domains → Subdomains** → اكتب `shopapp` على `pixelmindeg.com` → Create.
- هيتعمل فولدر افتراضي. هنغيّر مساره في خطوة 6 ليشاور على `public`.

## 2) اختَر PHP 8.3
hPanel → **Advanced → PHP Configuration** → اختار **PHP 8.3** للدومين.
- في تبويب **PHP extensions** اتأكد إن مفعّل: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `ctype`, `json`, `bcmath`, `fileinfo`, `curl`.

## 3) أنشئ قاعدة بيانات MySQL
hPanel → **Databases → MySQL Databases**:
- اعمل Database جديدة + User + Password، واربط الـ user بالـ database.
- سجّل: **اسم الـ database**، **اسم المستخدم**، **الباسورد**. (الـ host غالبًا `localhost`).

## 4) ارفع ملفات المشروع
عندك ملف **`shopapp-deploy.zip`** في فولدر `SHOP`.
- hPanel → **Files → File Manager** → ادخل `domains/pixelmindeg.com/` → اعمل فولدر اسمه `shopapp`.
- ارفع `shopapp-deploy.zip` جواه و**Extract**. المفروض تلاقي جواه `app/ public/ routes/ artisan ...`.

> بدّل المسار لو هيكل حسابك مختلف؛ المهم إن فولدر الـ Laravel يبقى `domains/pixelmindeg.com/shopapp`.

## 5) ركّب الـ dependencies (SSH)
اتصل بالـ SSH (بيانات الدخول من hPanel → SSH Access)، وبعدين:
```bash
cd ~/domains/pixelmindeg.com/shopapp

# لو "composer" مش متاح مباشرة، نزّله محليًا:
php8.3 -r "copy('https://getcomposer.org/installer','composer-setup.php');"
php8.3 composer-setup.php && rm composer-setup.php
# (لو composer متاح كأمر، استخدمه على طول بدل php8.3 composer.phar)

php8.3 composer.phar install --no-dev --optimize-autoloader
```

## 6) إعداد البيئة (.env) + المفتاح
```bash
cp .env.production .env
# افتح .env واملأ DB_DATABASE / DB_USERNAME / DB_PASSWORD من خطوة 3
php8.3 artisan key:generate
```
> للتعديل السريع للـ `.env` استخدم File Manager أو `nano .env`.

## 7) الهجرة + البيانات التجريبية
```bash
php8.3 artisan migrate --seed --force
```
ده بيعمل الجداول وبيزرع: الـ tenant الافتراضي `modist` + إعداداته، 5 تصنيفات، ~8 منتجات، و3 أكواد خصم.

## 8) تحسينات وروابط
```bash
php8.3 artisan storage:link
php8.3 artisan config:cache
php8.3 artisan route:cache
php8.3 artisan view:cache
chmod -R 775 storage bootstrap/cache
```

## 9) وجّه السابدومين على فولدر `public`
hPanel → **Domains → Subdomains** → عند `shopapp` → **Edit / Document Root** →
غيّر المسار ليبقى:
```
domains/pixelmindeg.com/shopapp/public
```
> ده أهم خطوة: لازم الـ document root يبقى `…/shopapp/public` (مش `…/shopapp`) عشان أمان Laravel.

## 10) فعّل HTTPS
hPanel → **Security → SSL** → فعّل شهادة Let's Encrypt المجانية على `shopapp.pixelmindeg.com`، وفعّل **Force HTTPS**.

---

## ✅ اختبار
- صحة الـ API: `https://shopapp.pixelmindeg.com/up` → صفحة OK.
- إعدادات المتجر: `https://shopapp.pixelmindeg.com/api/v1/settings/app` → JSON فيه `app_name/currency/brand/flags`.
- التصنيفات: `https://shopapp.pixelmindeg.com/api/v1/categories` → JSON `{ "data": [...] }`.
- الداشبورد: `https://shopapp.pixelmindeg.com/dashboard` → شاشة تسجيل دخول (تشتغل بـ mock).

## ربط تطبيق Flutter
في `lib/features/shared/config/app_config.dart`:
```dart
apiBaseUrl: 'https://shopapp.pixelmindeg.com/api/v1',
useMockData: false,
```

---

## تحديث لاحق (بعد أي تعديل)
- **الـ API**: ارفع الملفات المتغيّرة، وبعدين:
  ```bash
  php8.3 artisan migrate --force   # لو فيه migrations جديدة
  php8.3 artisan config:cache && php8.3 artisan route:cache
  ```
- **الداشبورد**: محليًا `cd dashboard && npm run build`، وانسخ `dist/*` فوق `api/public/dashboard/`، وارفعهم.

## ملاحظات
- **الإيميلات (OTP):** الـ `.env` مظبوط على `MAIL_MAILER=log` (الرسائل تتسجّل في اللوج مش تتبعت). لتفعيل الإرسال الفعلي، اعمل Email في hPanel واملأ `MAIL_USERNAME/MAIL_PASSWORD` وغيّر `MAIL_MAILER=smtp`.
- **الكاش بعد تعديل .env:** شغّل `php8.3 artisan config:clear` ثم `config:cache`.
- **الأخطاء:** اللوج في `storage/logs/laravel.log`.
