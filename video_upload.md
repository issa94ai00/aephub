# توثيق رفع فيديو مشفّر (AES-128-CBC) والتشغيل المتدفق — للعميل Flutter / Android

هذا المستند يصف **منطق الخادم** (Laravel API `v1`) لرفع ملف فيديو مشفّر محلياً (مثلاً ~150 ميغابايت) على التخزين **المحلي**، ثم ربطه كـ **CourseVideo**، ثم **التشغيل الفوري** عبر **HTTP Range** وبث مباشر يشبه Wasabi (روابط مؤقتة).

---

## 1) ما يتوقعه الخادم من التشفير على الجهاز

| الحقل | القيمة |
|--------|--------|
| الخوارزمية | **`AES-128-CBC`** فقط (نفس قيمة `cipher` في واجهات الرفع وإنشاء الفيديو). |
| المفتاح | **16 بايت** عشوائية → تُرسل للـ API كسلسلة **Base64** (بدون إعادة ترميز مرتين). |
| IV | **16 بايت** عشوائية (للبداية فقط لملف CBC واحد) → **Base64**. |
| حشوة | **PKCS#7** (معيار `Cipher.getInstance("AES/CBC/PKCS7Padding")` على Android). |
| الملف المرفوع | **بايتات الـ ciphertext فقط** (الملف الواحد المتواصل؛ لا يُرفع IV كبادئة منفصلة داخل الملف إلا إذا كان العميل يتعامل معها في فك التشفير بنفس الطريقة — الخادم **لا** يزيل بادئة؛ يخزن الملف كما أرسلته). |

**مهم للتشغيل المتدفق (CBC):** الخادم يخزّن **IV واحداً** (`content_iv`) لكامل الملف. في **AES-CBC**، فك التشفير الصحيح لأي بايت يتطلب سلسلة الكتل من بداية الملف، أو استخدام كتل الـ ciphertext السابقة كمدخلات لسلسلة الكتل عند القفز لمنتصف الملف (seek).  
**التوصية العملية للتشغيل السريع:** ابدأ القراءة من **الoffset 0** مع **Range** متتالية (تقدّمي)، أو اطلب نطاقات تبدأ عند حدود **16 بايت** (`cipher_block_alignment_bytes`) وتدار seek على العميل وفق منطق CBC.

---

## 2) مسار الرفع إلى التخزين المحلي (CourseFile)

الملف الناتج عن التشفير يُرفع كـ **CourseFile** مرتبط بدورة (`course_id`). الخادم يحفظ المسار تحت قرص **`local`** (افتراضياً `storage/app/private`) عند `FILESYSTEM_DISK=local`.

### خيار أ — رفع متعدد الأجزاء (مُفضّل لـ ~150 ميغابايت)

1. **`POST /api/v1/courses/{course}/videos/multipart/init`**  
   - ترويسات: `Authorization: Bearer <JWT>`، وعادة `X-Device-Id` وغيرها حسب سياسة التطبيق.  
   - جسم JSON يتضمن على الأقل:  
     `original_name`, `cipher`, `content_key`, `content_iv`  
     (نفس قواعد Base64 16 بايت للمفتاح والـ IV).  
   - **للتخزين المحلي:** إمّا إرسال `"storage_disk": "local"` أو ترك الحقل فارغاً إذا كان **`FILESYSTEM_DISK=local`** على الخادم (يُختار المسار المحلي مباشرة).  
   - الاستجابة `201` تحتوي مثلاً:  
     `upload_id`, `object_key`, `storage_disk`, `multipart_token`,  
     وأحجام مقترحة: `part_size_bytes`, `recommended_part_size_bytes`, **`recommended_parallel_parts`** (يُنصح برفع عدة أجزاء بالتوازي حسب التلميح).

2. **`POST .../multipart/sign-part`** لكل جزء — يعيد `url` و `method: PUT`.  
   - **للتخزين المحلي:** الـ `url` يشير إلى  
     `PUT /api/v1/courses/{course}/videos/multipart/part?part_token=...`  
     الهوية مثبتة بـ **`part_token`** في الاستعلام (يمكن عدم إرسال Bearer على هذا الـ PUT إن وُجد التوكن؛ يُفضّل إرسال نفس الترويسات إن أمكن).

3. **`POST .../multipart/complete`** — يرسل `parts` مع `etag` كما يعيدها الخادم/التخزين بعد كل PUT.

4. استجابة الإكمال أو عرض الملف تحتوي **`file.id`** و **`download_path`** بالشكل الثابت:

   `"/api/v1/courses/{courseId}/files/{fileId}/download"`

> **ملاحظة:** يمكن أيضاً استخدام مسارات الملفات `/courses/{course}/files/multipart/...` بدلاً من `/courses/{course}/videos/multipart/...` — كلاهما يوصل لنفس المنطق.

### خيار ب — رفع واحد (Multipart form)

- **`POST /api/v1/courses/{course}/files`**  
  حقول النموذج: `file` + `cipher` + `content_key` + `content_iv` (+ اختياري `name`, `encrypted_sha256`, …).  
- حد الحجم يخضع لإعدادات **Nginx `client_max_body_size`** و **PHP `post_max_size` / `upload_max_filesize`**؛ لملفات كبيرة يُفضّل خيار **multipart** أعلاه.

### حقول اختيارية مفيدة

- **`encrypted_sha256`**: SHA-256 لملف الـ ciphertext (hex 64 حرفاً) إن وُجد في واجهة إنشاء الفيديو؛ يساعد على التحقق من سلامة الملف.

---

## 3) إنشاء سجل الفيديو (CourseVideo)

بعد وجود `CourseFile`:

- **`POST /api/v1/courses/{course}/videos`**  
  - **`storage_path` (إلزامي):** يجب أن يطابق **بالضبط** النمط:

    `/api/v1/courses/{courseId}/files/{fileId}/download`

    حيث `courseId` هو نفس الدورة، و`fileId` هو `id` الملف من خطوة الرفع.

  - **`cipher`:** `"AES-128-CBC"`  
  - **`content_key` / `content_iv`:** نفس القيم **Base64** المرسلة مع رفع الملف (16 بايت بعد فك Base64).  
  - ملاحظة: الخادم يخزّن المفتاح في قاعدة البيانات بعد تشفير Laravel (`encrypted_content_key`)؛ للتشغيل يُسترجَى للعميل لاحقاً عبر جلسة التشغيل (انظر أدناه).

### 3.1) حذف فيديو الدورة (سجل + ملف التخزين عند الإمكان)

- **`DELETE /api/v1/courses/{courseId}/videos/{videoId}`**  
  - ترويسات: **`Authorization: Bearer <JWT>`**، **`X-Device-Id`** (مطلوب مع وسيط `device.lock` مثل باقي الـ API).  
  - صلاحية: **`teacher`** (مالك الدورة فقط، وحساب معتمد) أو **`admin`**.  
  - الربط: `{videoId}` يجب أن يكون فيديوً **تابعاً لنفس** `{courseId}` (استعلام مقيّد؛ خلاف ذلك **404**).  
  - الاستجابة **`200`** مثلاً: `{ "deleted": true, "video_id": <int> }`.  
  - **ما يحدث على الخادم:**  
    - حذف سجل **`CourseVideo`** (مع ما يرتبط به من تقدم مشاهدة وجلسات تشغيل وربط جلسات محاضرات عبر الـ cascade في قاعدة البيانات).  
    - إن كان `storage_path` بالنمط `/api/v1/courses/{courseId}/files/{fileId}/download` ويُستنتج منه **`CourseFile`**، وبعد الحذف **لا يبقى أي فيديو آخر** يستخدم نفس `storage_path`، يُحذف الملف من القرص/S3 ويُحذف سجل **`CourseFile`**.  
    - إن شارك فيديوان نفس مسار الملف، يُحذف السجل الحالي فقط ويبقى الملف حتى لا يبقى فيديو يشير إليه.

#### مثال Flutter (Dio)

استبدل `baseUrl` و`courseId` و`videoId` و`accessToken` و`deviceId` بقيم التطبيق.

```dart
import 'package:dio/dio.dart';

Future<void> deleteCourseVideo({
  required String baseUrl, // مثال: https://example.com  (بدون /api/v1)
  required int courseId,
  required int videoId,
  required String accessToken,
  required String deviceId,
}) async {
  final dio = Dio(BaseOptions(
    baseUrl: baseUrl,
    headers: {
      'Accept': 'application/json',
      'Authorization': 'Bearer $accessToken',
      'X-Device-Id': deviceId,
    },
  ));

  final response = await dio.delete<Map<String, dynamic>>(
    '/api/v1/courses/$courseId/videos/$videoId',
  );

  if (response.statusCode == 200 && response.data?['deleted'] == true) {
    // تم الحذف
  }
}
```

معالجة الأخطاء الشائعة: **401** (JWT منتهٍ/غير صالح)، **403** (ممنوع — لست مالك الدورة)، **404** (فيديو غير موجود أو لا ينتمي لهذه الدورة)، **423** (حساب مجمّد أو قفل جهاز).

---

## 4) التشغيل المتدفق — منطق الخادم

### 4.1 بث البايتات (ملف مشفّر)

الخادم **لا يفك** التشفير؛ يمرّر **نفس الملف المخزّن** مع دعم **`Range`**.

**مساران للعميل:**

| المصدر | الوصف |
|--------|--------|
| **`GET /api/v1/videos/{videoId}/encrypted`** | يتطلب **JWT** + صلاحية مشاهدة الفيديو. يدعم **GET/HEAD** و **Range** (`206`). |
| **`wasabi_temporary_url`** من **`GET /videos/{id}`** أو من قائمة الجلسات | للتخزين **المحلي** يكون رابط **Laravel موقّع زمنياً**:  
  `GET /api/v1/courses/{course}/files/{file}/stream-signed?expires=...&signature=...`  
  **بدون JWT**؛ صلاحية افتراضية قابلة للضبط (مثلاً ~30 دقيقة). يدعم **Range** مثل presigned S3. |

**ترويسات إرشادية** (على الاستجابة الثنائية):

- `Accept-Ranges: bytes`
- `X-Encrypted-Blob-Block-Align` (عادة `16`)
- `X-Suggested-Range-Bytes` (اقتراح حجم نطاق)

**JSON `playback`** (في استجابة الفيديو / الجلسات عند توفر الحقول):

- `supports_byte_range`
- `cipher_block_alignment_bytes`
- `suggested_range_request_bytes`
- `suggested_initial_prefetch_bytes`

استخدمها لضبط حجم طلبات **Range** على العميل (محاذاة لمضاعفات 16 بايت).

### 4.2 الحصول على مفتاح فك التشفير للمشاهد (طالب)

المفتاح **لا** يُعطى مع كل طلب بث؛ يُسلَّم ضمن جلسة تشغيل قصيرة:

1. **`POST /api/v1/videos/{video}/playback/session`**  
   - JWT + **`X-Device-Id`** (إلزامي).  
   - يعيد `session_id` و`expires_at` (جلسة قصيرة، مثلاً دقائق).

2. **`POST /api/v1/videos/playback/key`**  
   - JWT + نفس **`X-Device-Id`** المطابق للجلسة.  
   - جسم: `{ "session_id": <id> }`  
   - يعيد `cipher`, `content_key` / `content_key_base64`, `content_iv` / `content_iv_base64`.  
   - **لا تعيد** ترميز Base64 مرتين: القيم جاهزة كسلاسل Base64 لـ 16 بايت.

بعدها يبني العميل **مصدر بيانات** يقرأ من رابط البث (مع Range)، يفك AES-128-CBC بالمفتاح/IV، ويغذّي المشغّل (مثلاً عبر `MediaSource` مخصص أو كاش مؤقت).

---

## 5) ملخص تدفق Flutter موصى به (معلّم)

1. توليد `key` (16B) و `iv` (16B)؛ تشفير الملف `AES-128-CBC` + PKCS7.  
2. رفع الملف كـ **CourseFile** (multipart محلي مفضّل لـ 150 ميغا).  
3. **`POST /courses/{id}/videos`** مع `storage_path = /api/v1/courses/{id}/files/{fileId}/download` ونفس `cipher/key/iv`.  
4. (اختياري) **`DELETE /api/v1/courses/{id}/videos/{videoId}`** لحذف الفيديو والملف المرتبط عند عدم مشاركته.  
5. للتشغيل:  
   - إنشاء **`playback/session`**  
   - جلب **`playback/key`**  
   - فتح بث من **`wasabi_temporary_url`** (محلي موقّع) أو **`/videos/{id}/encrypted`** + JWT  
   - طلب **Range** متسلسل أو محاذٍ لـ 16 بايت؛ فك التشفير ثم تمرير للـ decoder.

---

## 6.5) استئناف الرفع المتقطع

إذا انقطع الرفع (خروج من التطبيق، فقدان الشبكة، إلخ)، يمكن استئنافه بدون إعادة رفع الأجزاء المكتملة:

1. **احتفظ محلياً بـ:** `upload_id`, `object_key`, `multipart_token`, `total_parts`
2. عند إعادة فتح التطبيق، أرسل:

   **`POST .../multipart/status`**
   ```json
   {
     "upload_id": "...",
     "object_key": "...",
     "multipart_token": "..."
   }
   ```

3. الاستجابة:
   ```json
   {
     "upload_id": "...",
     "object_key": "...",
     "storage_disk": "local",
     "uploaded_parts": [1, 2, 3, 5],
     "expires_at": 1746200000
     }
   ```

4. أعد رفع الأجزاء المفقودة فقط (في المثال: الجزء 4 وما بعده).
5. ثم أرسل `multipart/complete` كالمعتاد.

> **ملاحظة:** الـ `multipart_token` تنتهي صلاحيته بعد ساعتين. إذا انتهت، يجب بدء رفع جديد عبر `multipart/init`.

---

## 6) متطلبات الخادم (مرجعية لتجنب فشل الرفع)

- **Nginx:** `client_max_body_size` كافٍ لأكبر جزء رفع (مثلاً ≥ 128M إن لزم).  
- **PHP-FPM:** `post_max_size` و `upload_max_filesize` للرفع أحادي؛ لـ multipart الأجزاء تمرّر غالباً عبر `php://input` على مسار الـ PUT المحلي — راجع سقف **`MULTIPART_MAX_PART_BYTES`** في الإعدادات.

---

## 7) مسارات API سريعة

| الغرض | الطريقة | المسار |
|--------|---------|--------|
| بدء رفع متعدد (ملفات) | POST | `/api/v1/courses/{course}/files/multipart/init` |
| بدء رفع متعدد (فيديو) | POST | `/api/v1/courses/{course}/videos/multipart/init` |
| توقيع جزء | POST | `.../multipart/sign-part` |
| رفع جزء (محلي - ملفات) | PUT | `/api/v1/courses/{course}/files/multipart/part` |
| رفع جزء (محلي - فيديو) | PUT | `/api/v1/courses/{course}/videos/multipart/part` |
| إكمال الرفع | POST | `.../multipart/complete` |
| إلغاء الرفع | POST | `.../multipart/abort` |
| حالة الرفع (استئناف) | POST | `.../multipart/status` |
| إنشاء فيديو | POST | `/api/v1/courses/{course}/videos` |
| حذف فيديو | DELETE | `/api/v1/courses/{course}/videos/{video}` |
| بث مشفّر (JWT) | GET | `/api/v1/videos/{video}/encrypted` |
| بث موقّع (محلي) | GET | `/api/v1/courses/{course}/files/{file}/stream-signed` |
| جلسة تشغيل | POST | `/api/v1/videos/{video}/playback/session` |
| مفتاح فك التشفير | POST | `/api/v1/videos/playback/key` |

---

*آخر تحديث يعكس منطق المستودع الحالي (تخزين محلي، روابط موقّعة، Range، AES-128-CBC).*
