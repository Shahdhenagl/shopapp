# النشر عن طريق Git — shopapp.pixelmindeg.com

بدل رفع الـ zip كل مرة: **`git push` من جهازك + أمر واحد على السيرفر**.

الريبو: `github.com/Shahdhenagl/shopapp` — الفرع `main`.

---

## أ) إعداد لمرة واحدة على السيرفر (SSH)

### 1) مفتاح نشر (Deploy Key) عشان السيرفر يقدر يسحب من GitHub
```bash
ssh-keygen -t ed25519 -C "shopapp-deploy" -f ~/.ssh/id_ed25519 -N ""
cat ~/.ssh/id_ed25519.pub
```
انسخ السطر اللي هيظهر (بيبدأ بـ `ssh-ed25519 …`).

### 2) ضيف المفتاح في GitHub
- افتح: `https://github.com/Shahdhenagl/shopapp/settings/keys`
- **Add deploy key** → الصق المفتاح → سيبه **Read-only** → Add.

### 3) استنسخ الريبو على السيرفر
```bash
ssh -o StrictHostKeyChecking=accept-new -T git@github.com   # قبول github مرة واحدة
git clone git@github.com:Shahdhenagl/shopapp.git ~/shop
chmod +x ~/shop/deploy.sh
```
> لو ظهر خطأ SSH (بعض الاستضافات تقفل SSH الصادر)، استخدم HTTPS بدلها:
> ```bash
> git clone https://github.com/Shahdhenagl/shopapp.git ~/shop
> ```
> ولو الريبو خاص هيطلب توكن — اعمل Personal Access Token من GitHub واستخدمه كباسورد.

### 4) أول نشر
```bash
~/shop/deploy.sh
```

---

## ب) كل تحديث بعد كده

**على جهازك:**
```bash
cd C:\Users\HP\SHOP
# (لو غيّرت الداشبورد) ابنيه وانسخه:
cd dashboard && npm run build && cd ..
# انسخ ناتج البناء لمجلد public بتاع Laravel:
#   (بيتعمل تلقائيًا لو استخدمت السكريبت المحلي، أو يدوي)
git add -A
git commit -m "وصف التغيير"
git push
```

**على السيرفر (أمر واحد):**
```bash
~/shop/deploy.sh
```
خلاص. السكريبت بيعمل: pull + مزامنة الكود + نشر الداشبورد + migrate + مسح الكاش.

---

## ملاحظات
- **`.env` و `vendor/` و `storage/` على السيرفر مش بيتمسوا** أبدًا — بيتحافظ عليهم.
- **متشغّلش `config:cache`** على السيرفر ده (السكريبت بيستخدم `config:clear`).
- **الداشبورد**: البناء بيتعمل على جهازك ويترفع جاهز في `api/public/dashboard` — فالسيرفر مش محتاج Node.
- لو غيّرت مكتبات composer (composer.json)، شغّل مرة على السيرفر: `cd ~/domains/shopapp.pixelmindeg.com/laravel && php composer.phar install --no-dev --optimize-autoloader`.
