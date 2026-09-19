# رفع ملفات المجتمع (مساهمون + أدمن) على Cloudflare R2

ملفات مجموعات البيانات من المساهمين والأدمن تُرفع إلى القرص المُعرّف في `config/filesystems.php` تحت مفتاح **community_disk**. يمكن أن يكون `local` (التطوير) أو `r2` (الإنتاج).

## الخطوات

### 1. إنشاء API Token من Cloudflare

- الدخول إلى: **Cloudflare Dashboard → R2 → Manage R2 API Tokens**
- **Create API Token** → اختيار **R2 Token**
- الصلاحيات: **Object Read & Write**
- حفظ: **Access Key ID** و **Secret Access Key** و **Endpoint**

### 2. إعداد ملف `.env`

```env
# قرص ملفات المجتمع: استخدم r2 لتفعيل R2
FILESYSTEM_DISK_COMMUNITY=r2

# بيانات R2 (نفس أسماء متغيرات AWS لأن R2 متوافق مع S3)
AWS_ACCESS_KEY_ID=ضع_access_key_هنا
AWS_SECRET_ACCESS_KEY=ضع_secret_key_هنا
AWS_DEFAULT_REGION=auto
AWS_BUCKET=اسم_bucket_الذي_أنشأته
AWS_ENDPOINT=https://YOUR_ACCOUNT_ID.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=true
```

(استبدل `YOUR_ACCOUNT_ID` بمعرّف الحساب من لوحة Cloudflare.)

### 3. على السيرفر: تثبيت حزمة R2/S3 (مهم)

خطأ **"Class League\Flysystem\AwsS3V3\PortableVisibilityConverter not found"** يعني أن الحزمة غير مثبتة في بيئة الإنتاج. نفّذ على السيرفر (SSH أو من مجلد المشروع):

```bash
cd /path/to/حصتك
composer install --no-dev
```

إن استمر الخطأ، ثبّت الحزمة صراحة:

```bash
composer require league/flysystem-aws-s3-v3:^3.25 --no-interaction
```

ثم مسح الكاش:

```bash
php artisan config:clear
php artisan cache:clear
```

### 4. مسح الكاش (بعد أي تعديل على .env)

```bash
php artisan config:clear
php artisan cache:clear
```

### 5. التجربة

- من لوحة المساهم: رفع مجموعة بيانات (ملف).
- من أدمن المجتمع: مراجعة التقديمات → عرض البيانات / تحميل الملف.

الملفات المُرفعة سابقاً على `local` تبقى في `storage/app/private/community_datasets/`. بعد التبديل إلى `r2` الملفات الجديدة فقط ستُرفع على R2.

## التخزين يذهب إلى محلي (storage/app/private) بدل R2؟

1. **مسح كاش الإعدادات (مهم):**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```
2. **التأكد من `.env`:**
   - السطر `FILESYSTEM_DISK_COMMUNITY=r2` موجود بدون مسافات قبل أو بعد القيمة.
   - لا يوجد تعليق `#` أمامه.
3. **على السيرفر (إن كنت تستخدم `config:cache`):**
   بعد تعديل `.env` نفّذ مرة واحدة:
   ```bash
   php artisan config:cache
   ```
   حتى يُحفظ قيمة `FILESYSTEM_DISK_COMMUNITY=r2` في الكاش.
4. **إعادة تشغيل:** إن كنت تستخدم queue أو supervisor أعد تشغيلها بعد تغيير `.env` ومسح الكاش.

## رابط تحميل عام من R2 (اختياري)

إذا فعّلت **Public Access** على الـ bucket وربطت دومين (مثلاً عبر R2 custom domain)، يمكنك تعيين:

```env
AWS_URL=https://your-public-bucket-url.r2.dev
```

للاستخدام مع `Storage::disk('r2')->url($path)` إن أردت روابط تحميل مباشرة لاحقاً.
