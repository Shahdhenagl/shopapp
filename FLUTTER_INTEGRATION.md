# MODIST API — دليل تكامل تطبيق Flutter

كل اللي مطوّر الـ Flutter محتاجه عشان يوصّل التطبيق بالـ backend اللايف.

- **Base URL:** `https://shopapp.pixelmindeg.com/api/v1`
- **Health check:** `https://shopapp.pixelmindeg.com/up`

---

## 0) إعداد التطبيق (الخطوة الوحيدة المطلوبة للتشغيل)
في `lib/features/shared/config/app_config.dart`:
```dart
AppConfig(
  appName: 'MODIST',
  apiBaseUrl: 'https://shopapp.pixelmindeg.com/api/v1',
  useMockData: false,   // ← اقلبها false عشان يكلّم السيرفر الحقيقي
)
```
بعد كده كل الـ remote data sources تشتغل تلقائيًا.

**شغل لسه مطلوب في كود التطبيق (من خطة الـ PDF):**
1. **تخزين التوكن** في `flutter_secure_storage` + ربط `DioClient.onUnauthorized` بتسجيل الخروج الحقيقي في الـ bootstrap.
2. **Interceptor للتجديد:** عند 401 نادِ `POST /auth/refresh` بالـ refresh_token مرة واحدة وأعد المحاولة؛ لو فشل → logout.
3. **إكمال تحويل ردّ السلة** في `CartRemoteDataSource` (حاليًا فيه `_notImplemented()`) — حوّل شكل §Cart لـ `CartItem`/`AppliedPromo`. الشكل مصمّم بحيث `CartItem.lineId` = `product.id|size|colorValue` = `line_id` بتاع السيرفر.
4. (اختياري) **الدفع:** بدّل `CardPayment` ليبعت `payment_token` فقط (tokenization من SDK مزود الدفع) بدل بيانات الكارت الخام.

---

## 1) الهيدرز (تتبعت مع كل طلب)
| Header | القيمة | ملاحظات |
|---|---|---|
| `Accept` | `application/json` | إلزامي |
| `Content-Type` | `application/json` | للطلبات اللي ليها body |
| `Accept-Language` | `en` أو `ar` | بيحدّد لغة المحتوى والرسائل |
| `Authorization` | `Bearer <token>` | بعد تسجيل الدخول |

> كل التواريخ ISO-8601 UTC. كل الـ IDs نصوص (String).

---

## 2) شكل الرد والأخطاء
**نجاح:** إما الكائن مباشرة أو ملفوف في `{ "data": ... }` (التطبيق يقبل الاتنين).

**خطأ (شكل ثابت):**
```json
{ "message": "نص للمطوّر", "errors": { "email": ["..."] } }
```
**أكواد الحالة:** `422` تحقق، `401` غير مصرّح، `403` ممنوع، `404` غير موجود، `409` تعارض، `429` كثرة طلبات، `5xx` خطأ سيرفر.

> **401 في أي مكان** → التطبيق ينفّذ `onUnauthorized` (يمسح الجلسة ويروح للدخول). السيرفر بيرجّع 401 فقط لو التوكن ناقص/منتهي/غلط.

---

## 3) المصادقة (Auth)

| Method | Path | Body | الرد |
|---|---|---|---|
| POST | `/auth/register` | `{name,email,phone,password}` | `201` + `{token,refresh_token,user}` |
| POST | `/auth/login` | `{email,password}` | `200` + `{token,refresh_token,user}` |
| POST | `/auth/refresh` | `{refresh_token}` | `200` + `{token,refresh_token,user}` |
| POST | `/auth/logout` | — (Bearer) | `204` |
| POST | `/auth/password/forgot` | `{email}` | `200` (دايمًا، حتى لو الإيميل مش موجود) |
| POST | `/auth/password/verify` | `{email,code}` | `200` / `422` |
| POST | `/auth/password/resend` | `{email}` | `200` |
| POST | `/auth/password/reset` | `{email,password}` | `200` |

**ردّ login/register (مسطّح، مش ملفوف في data):**
```json
{
  "token": "12|xY....",
  "refresh_token": "aBc...64char",
  "user": { "id": "7", "name": "Sara", "email": "s@x.com", "phone": "+20100...", "avatar_url": null }
}
```
- كود إعادة التعيين 4–6 أرقام (نصّي)، صلاحية 10 دقايق.
- التطبيق يخزّن `token` ويبعته Bearer؛ يخزّن `refresh_token` للتجديد.

---

## 4) إعدادات المتجر (White-label) — عام
`GET /settings/app` → بيتجاب مرة عند بدء التطبيق، الفشل غير قاتل (يفضّل على الافتراضي).
```json
{ "data": {
  "app_name": "MODIST",
  "currency": "EGP",
  "brand": { "primary": "#0E0E0E", "on_primary": "#FFFFFF", "accent": "#1F8A5B" },
  "flags": { "card_payment": true, "cash_payment": true, "promo_codes": true, "favorites": true }
} }
```
- الألوان الغير مُرسلة بيفضل عليها لون التطبيق الافتراضي. أي flag مش موجود = `true`.

---

## 5) الكتالوج — عام

### `GET /categories`
```json
{ "data": [ { "id": "tshirt", "label_key": "category_tshirt", "icon_key": "tshirt" } ] }
```
`icon_key ∈ tshirt|pants|jacket|shorts|shoes`.

### `GET /products`  (فلاتر اختيارية: `?category=&q=&newest=true&page=&per_page=`)
### `GET /products/{id}`  (404 لو مش موجود)
شكل المنتج:
```json
{
  "id": "p1", "name": "Men's Casual Navy Shirt", "style": "Men Style",
  "description": "....", "price": 820, "currency": "EGP",
  "images": ["https://..."],
  "colors": ["#FF1B2A4A", "#FF7B1E1E"],
  "sizes": ["S","M","L","XL","XXL","XXXL"],
  "category_id": "tshirt", "rating": 4.6, "is_newest": true
}
```
- `name/style/description` بتترجم حسب `Accept-Language`.
- `colors` نصوص hex بصيغة `#AARRGGBB`.

---

## 6) السلة (Cart) — Bearer

| Method | Path | Body | الرد |
|---|---|---|---|
| GET | `/cart` | — | السلة كاملة |
| POST | `/cart` | `{product_id,size,color,quantity}` | السلة المحدّثة |
| PATCH | `/cart/{lineId}` | `{quantity}` | السلة المحدّثة |
| DELETE | `/cart/{lineId}` | — | السلة المحدّثة |
| DELETE | `/cart` | — | سلة فاضية |
| POST | `/cart/promo` | `{code}` | `{ "fraction":0.10, "code":"MODIST10" }` أو `422` |

- `color` رقم int (نفس اللي التطبيق بيبعته).
- `lineId` = `product_id|size|color`.

شكل السلة:
```json
{ "data": {
  "items": [
    { "line_id": "p1|M|4279371338", "size": "M", "color": 4279371338,
      "quantity": 2, "line_total": 1640, "product": { /* كائن المنتج §5 */ } }
  ],
  "summary": { "subtotal": 1640, "discount": 164, "total": 1476, "currency": "EGP",
               "applied_promo": { "code": "MODIST10", "fraction": 0.10 } }
} }
```
> الكوبون اللي بيتطبّق بـ `/cart/promo` بيتحفظ على السلة، والسيرفر بيعيد حساب الخصم عند الـ checkout.

**أكواد خصم تجريبية:** `MODIST10`=10% · `WELCOME15`=15% · `XX032910`=20%.

---

## 7) المفضلة (Favorites) — Bearer

| Method | Path | Body | الرد |
|---|---|---|---|
| GET | `/favorites` | — | `{ "data": ["p1","p4"] }` |
| POST | `/favorites` | `{product_id}` | `{ "data": [...] }` (toggle) |
| DELETE | `/favorites` | — | `204` |

> التطبيق بيخزّن IDs بس ويجيب تفاصيل المنتجات من الكتالوج.

---

## 8) الدفع (Checkout) — Bearer
`POST /checkout`
```json
{
  "amount": 1800, "currency": "EGP",
  "payment_method": "creditCard",
  "address": { "address": "12 شارع التحرير", "city": "القاهرة", "area": "وسط البلد", "branch": "الرئيسي" },
  "card": { "payment_token": "tok_..." }
}
```
- `payment_method ∈ creditCard | cash`.
- `card.payment_token` مطلوب فقط مع `creditCard`.
- `amount` من العميل **مجرد عرض** — السيرفر بيعيد حساب الإجمالي من السلة.

الرد (`201`):
```json
{ "data": { "id": "MOD-AB12CD", "amount": 1476, "currency": "EGP",
            "status": "paid", "payment_method": "creditCard", "items": [...], "created_at": "..." } }
```
> ⚠️ متبعتش بيانات كارت خام. اعمل tokenization من SDK المزود وابعت `payment_token` بس.

---

## 9) الإشعارات (Notifications) — Bearer

| Method | Path | Body | الرد |
|---|---|---|---|
| GET | `/notifications` | — | مصفوفة إشعارات (الأحدث أولًا) |
| POST | `/notifications/read` | — | نفس القائمة بعد تعليمها مقروءة |
| GET | `/notifications/count` | — | `{ "data": { "unread": 3 } }` |
| POST | `/notifications/devices` | `{token, platform:"android\|ios"}` | `204` (تسجيل FCM) |
| DELETE | `/notifications/devices` | `{token}` | `204` (عند logout) |

شكل الإشعار:
```json
{ "id": "n1", "type": "order", "message": "تم شحن طلبك MOD-AB12CD!",
  "created_at": "2026-06-30T09:05:00Z", "images": [], "is_read": false }
```
- `type ∈ order | promo | product | general`.
- `message` نص جاهز ومترجم حسب `Accept-Language`.
- عدّاد غير المقروء يتحسب من `is_read` (مفيش endpoint منفصل مطلوب).

---

## 10) البروفايل (Profile) — Bearer

| Method | Path | Body | الرد |
|---|---|---|---|
| GET | `/me` | — | `{ "data": { ...user } }` |
| PATCH | `/me` | `{name?, phone?, avatar_url?}` | `{ "data": { ...user } }` |
| GET | `/me/orders` | — | `{ "data": [ ...orders ] }` |

كائن الـ user: `{ id, name, email, phone, avatar_url }`.

---

## 11) أمثلة سريعة (curl)
```bash
BASE=https://shopapp.pixelmindeg.com/api/v1

# تسجيل
curl -s -X POST "$BASE/auth/register" -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"name":"Sara","email":"sara@x.com","phone":"+201000000000","password":"password123"}'

# دخول
curl -s -X POST "$BASE/auth/login" -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"sara@x.com","password":"password123"}'

# منتجات (عام)
curl -s "$BASE/products" -H "Accept: application/json" -H "Accept-Language: ar"

# سلة (Bearer)
curl -s "$BASE/cart" -H "Accept: application/json" -H "Authorization: Bearer <TOKEN>"
```

---

## 12) ملخص كل المسارات
| Method | Path | Auth |
|---|---|---|
| POST | `/auth/register` | عام |
| POST | `/auth/login` | عام |
| POST | `/auth/refresh` | عام |
| POST | `/auth/logout` | Bearer → 204 |
| POST | `/auth/password/{forgot,verify,resend,reset}` | عام |
| GET | `/settings/app` | عام |
| GET | `/categories` | عام |
| GET | `/products` , `/products/{id}` | عام |
| GET/POST/PATCH/DELETE | `/cart` , `/cart/{lineId}` , `/cart/promo` | Bearer |
| GET/POST/DELETE | `/favorites` | Bearer |
| POST | `/checkout` | Bearer |
| GET/POST/GET | `/notifications` , `/notifications/read` , `/notifications/count` | Bearer |
| POST/DELETE | `/notifications/devices` | Bearer → 204 |
| GET/PATCH | `/me` | Bearer |
| GET | `/me/orders` | Bearer |

---

*كل الشكل ده مطابق لـ `*Model.fromJson` في التطبيق — التطبيق بيقرأ المفاتيح دي بالظبط.*
