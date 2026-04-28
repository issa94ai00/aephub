# توثيق API (إصدار v1)

هذا الملف يوثق جميع نقاط الـ API المعرفة في `routes/api.php` تحت المسار الأساسي:

- **Base URL**: `{{BASE_URL}}/api/v1`
  - مثال: `http://localhost/api/v1`

---

## متطلبات عامة

### التوثيق (Authentication)

معظم نقاط النهاية داخل:

- Middleware: `auth.jwt`, `account.freeze`, `device.lock`

يعني يجب إرسال:

- **Authorization**: `Bearer <JWT>`

### تقييد الجهاز (Device Lock)

بعض النقاط تعتمد على التحقق من الجهاز عبر الهيدر:

- **X-Device-Id**: `<device-id>`

مهم جداً في تشغيل الفيديو:

- `POST /videos/{video}/playback/session` **يتطلب** `X-Device-Id`
- `POST /videos/playback/key` **يتطلب** `X-Device-Id` ويجب أن يطابق الجهاز الذي أنشأ جلسة التشغيل

### الأدوار (Roles)

هناك middleware `role:...` لبعض المسارات:

- **student**
- **teacher**
- **admin**

### Pagination

عدة endpoints ترجع Paginated payload (Laravel paginator أو تنسيق `ApiPagination`):

- `page` (اختياري)
- `per_page` (اختياري)

### أخطاء شائعة (Status Codes)

- **401 Unauthorized**: لا يوجد JWT صحيح
- **403 Forbidden**: لا تملك صلاحية/دور مناسب
- **404 Not found**: مورد غير موجود أو ممنوع إظهاره
- **409 Conflict**: تمت مراجعة الطلب مسبقاً (مثال: مراجعة دفع/طلب تغيير جهاز)
- **410 Gone**: جلسة تشغيل فيديو انتهت
- **422 Unprocessable Entity**: فشل التحقق من المدخلات
- **423 Locked**: القيد/التعليق (مثل تعليق وصول الطالب للمادة أو عدم مطابقة الجهاز)

---

## Academics (عام / بدون JWT)

### GET `/academics/universities`

**Response 200**

- `universities[]`: `{id,name,name_en,localized_name}`

### GET `/academics/faculties`

**Query**

- `university_id` (required, int)

**Response 200**

- `faculties[]`: `{id,university_id,name,name_en,localized_name}`

### GET `/academics/study-years`

**Query**

- `faculty_id` (required, int)

**Response 200**

- `study_years[]`: `{id,faculty_id,year_number,name,name_en,localized_name}`

### GET `/academics/study-terms`

**Query**

- `study_year_id` (required, int)

**Response 200**

- `study_terms[]`: `{id,study_year_id,term_number,name,name_en,localized_name}`

### GET `/academics/study-terms/{studyTerm}/courses`

مقررات **منشورة فقط** المربوطة بالفصل الدراسي (`course_study_terms`). بدون JWT.

**Query**

- `q` (optional, string) — بحث في العنوان والوصف
- `page`, `per_page` (max 100)

**Response 200**

- Laravel paginator (مثل `GET /courses`) مع `teacher`.

### GET `/academics/study-term-context`

**Query**

- `study_term_id` (required, int)

**Response 200**

يعيد سياق السلم الأكاديمي لهذا الفصل:

- `university_id, faculty_id, study_year_id, study_term_id`
- `university_name, faculty_name, study_year_label, study_term_label`

---

## Auth (عام / بدون JWT)

### POST `/auth/register`

**Body (JSON)**

- `name` (required, string)
- `email` (required, email, unique)
- `phone` (required, string)
- `password` (required, min 8)
- `accept_terms` (required, accepted)
  - يقبل مفاتيح بديلة: `terms_accepted` أو `acceptTerms`
  - يقبل `true/1/"true"/"1"/"yes"/"on"`
- `role` (optional): `student|teacher` (الافتراضي `student`)
- `study_term_id` (required إذا role=student)

**Response**

- **201 Student**: يرجع `token` + `user`
- **201 Teacher**: لا يرجع token؛ يرجع:
  - `message`
  - `approval_status` (مثلاً pending)
  - `user`

**curl**

```bash
curl -X POST "{{BASE_URL}}/api/v1/auth/register" ^
  -H "Content-Type: application/json" ^
  -d "{\"name\":\"Ali\",\"email\":\"ali@example.com\",\"phone\":\"+963...\",\"password\":\"password123\",\"accept_terms\":true,\"role\":\"student\",\"study_term_id\":1}"
```

### POST `/auth/login`

**Body (JSON)**

- `email` (required)
- `password` (required)

**Response**

- **200**: `token`, `token_type=bearer`, `expires_in`, `score_degree`
- **401**: `message=Invalid credentials`
- **403** (teacher غير مُعتمد): `message` + `approval_status`
- **403** (account deleted): `{message,status="deleted"}`
- **423** (account frozen): `{message="Account is frozen",status="frozen"}`

### POST `/auth/forgot-password` (بدون JWT، throttle: 5 طلبات/دقيقة لكل مفتاح التقييد الافتراضي)

يطلب إرسال رمز مكوّن من 6 أرقام إلى بريد المستخدم المسجّل. لأسباب أمنية تكون الاستجابة **نفس الرسالة** سواء وُجد الحساب أم لا (لا يكشف إن كان البريد مسجّلاً).

**Body (JSON)**

- `email` (required, email)

**Response**

- **200**: `message` — نص يوضح أنه إن وُجد الحساب سيصل رمز إلى البريد
- **503**: فشل إرسال البريد (إعدادات SMTP أو الخادم) — `message`

**curl**

```bash
curl -X POST "{{BASE_URL}}/api/v1/auth/forgot-password" ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"ali@example.com\"}"
```

### POST `/auth/reset-password` (بدون JWT، throttle: 10 طلبات/دقيقة)

يتحقق من الرمز المرسل بالبريد ثم يضبط كلمة مرور جديدة. بعد **5 محاولات خاطئة** للرمز يُلغى السجل ويلزم طلب رمز جديد من `/auth/forgot-password`.

**Body (JSON)**

- `email` (required, email) — نفس البريد المستخدم عند طلب الرمز
- `code` (required, string) — 6 أرقام فقط (مثلاً `042891`)
- `password` (required, min 8)
- `password_confirmation` (required، يجب أن يطابق `password`)

**Response**

- **200**: `message` — نجاح تحديث كلمة المرور
- **422**: `message` — رمز غير صالح أو منتهي أو محاولات كثيرة خاطئة
- **422**: أخطاء تحقق الحقول (صيغة البريد، طول كلمة المرور، عدم تطابق التأكيد، صيغة الرمز)

**curl**

```bash
curl -X POST "{{BASE_URL}}/api/v1/auth/reset-password" ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"ali@example.com\",\"code\":\"123456\",\"password\":\"newpassword123\",\"password_confirmation\":\"newpassword123\"}"
```

### GET `/auth/me` (JWT)

**Headers**

- `Authorization: Bearer ...`

**Response 200**

- `user`

### POST `/auth/logout` (JWT)

يبطل الـ token الحالي.

**Response 200**

- `message=Logged out`

### POST `/auth/refresh` (JWT)

**Response 200**

- `token`, `token_type`, `expires_in`

---

## Device Change Requests

### POST `/device-change-requests/unauth` (بدون JWT)

مخصص للحالات التي يكون فيها الحساب مقيد بجهاز آخر ولا يمكن تسجيل الدخول.

**Body (JSON)**

- `email` (required)
- `password` (required)
- `reason` (optional)
- `requested_device_id` (optional)

**Response**

- **201**: `request`
- **200**: إن كان هناك طلب pending سابقاً يعيده
- **401**: `Invalid credentials`
- **409**: `Device lock is not enabled for this account`

### POST `/users/me/device-change-requests` (JWT)

**Body (JSON)**

- `reason` (optional)
- `requested_device_id` (optional)

**Response 201**

- `request`

### GET `/admin/device-change-requests` (JWT, admin)

**Query**

- `status` (optional): `pending|approved|rejected`
- `user_id` (optional, int)
- `page`, `per_page`

**Response 200**

قائمة مع `student`, `status`, `reason`, `created_at`

### POST `/admin/device-change-requests/{deviceChangeRequest}/review` (JWT, admin)

**Body (JSON)**

- `status` (required): `approved|rejected`
- `note` (optional)
- `action` (optional): `reset_lock|set_lock_device|none`
- `device_id` (optional) مطلوب عندما `action=set_lock_device`

**Response**

- **200**: `{request:{id,status}, user:{id,locked_device_id}}`
- **409**: `Request already reviewed`
- **422**: عند نقص `device_id` مع `set_lock_device`

---

## User Profile

### PATCH `/users/me` (JWT)

تحديث بيانات الحساب الأساسية. الحقول **اختيارية**؛ يُحدَّث ما يُرسل فقط. يجب تضمين **حقل واحد على الأقل**؛ وإلا **422**.

**Body (JSON)**

- `name` (optional, string, max 255)
- `email` (optional, email, فريد بين المستخدمين باستثناء حسابك)
- `phone` (optional, string, max 32، نفس نمط التسجيل: أرقام و`+` ومسافات و`. () -`)

**Response 200**

- `user` (المستخدم بعد التحديث)

**Response 422**

- `message` عند عدم إرسال أي من الحقول أعلاه

### POST `/users/me/password` (JWT)

**Body (JSON)**

- `current_password` (required)
- `password` (required, min 8)
- `password_confirmation` (required، يجب أن يطابق `password`)

**Response 200**

- `message`
- `user` (بعد التحديث)

**Response 422**

- `message` عند عدم تطابق كلمة المرور الحالية

### PATCH `/users/me/academic-profile` (JWT, student)

**Body (JSON)**

- `study_term_id` (required, int)

**Response 200**

- `message`
- `user` (بعد التحديث)

### POST `/users/me/delete-account` (JWT, student)

حذف منطقي لحساب الطالب عبر تعيين `users.status = deleted` ثم إبطال JWT الحالي.

**Body (JSON)**

- `current_password` (required, string)

**Response 200**

```json
{
  "message": "تم حذف الحساب بنجاح.",
  "status": "deleted"
}
```

**Response 410** (محذوف مسبقاً)

```json
{
  "message": "الحساب محذوف مسبقاً.",
  "status": "deleted"
}
```

**Response 422**

- `message` عند عدم تطابق `current_password`

---

## Notifications

### GET `/users/me/notifications` (JWT)

**Query**

- `unread_only` (optional, boolean)
- `page`, `per_page`

**Response 200**

تنسيق pagination ويحتوي عناصر:

- `{id,type,title,body,data,read_at,created_at}`

### POST `/users/me/notifications/{notification}/read` (JWT)

**Response 200**

- `notification: {id, read_at}`

**Forbidden 403** إذا كانت الإشعار ليس للمستخدم.

---

## Courses (Catalog)

### GET `/courses` (JWT)

قائمة الدورات المنشورة فقط.

**Query**

- `q` (optional, string)
- `study_term_id` (optional, int) — عند التمرير: فقط المقررات المربوطة بهذا الفصل الدراسي (منشورة)
- `page`, `per_page` (max 100)

**Response 200**

Laravel paginator الافتراضي.

### GET `/courses/{course}` (JWT)

يعيد تفاصيل الدورة + حالة التسجيل + تقدم الدفع (للطلاب).

**Response 200**

- `course`
  - محمل مع: `teacher`, `videos`, `files`
- `enrollment_status` (nullable)
- `is_enrolled` (nullable/boolean)
- `course_access_active` (boolean)
- `enrollment` (nullable): `{id,status,access_locked,paid_amount_cents,unlocked_videos_count,unlocked_sessions_count}`
- `payment_progress` (nullable) (للطلاب)

ملاحظة:

- للطالب مع `course_access_active=true` قد يتم **تقييد الفيديوهات** المعادة حسب الدفعات المعتمدة (progressive unlock).

### GET `/courses/{course}/cover` (بدون JWT للدورات المنشورة)

- للدورات `published`: قابل للقراءة بدون JWT (مناسب لـ Image.network)
- للدورات غير المنشورة: يتطلب JWT ويجب أن يكون `admin` أو teacher مالك للدورة

**Response**

- ملف صورة (binary) أو JSON 404/403 حسب الحالة

### POST `/courses/{course}/cover` (JWT, teacher/admin)

رفع صورة الغلاف.

**multipart/form-data**

- `image` أو `cover_image` (required أحدهما، max 5MB, mimes: jpg/jpeg/png/webp)

**Response 200**

- `course`

### GET `/courses/{course}/students` (JWT, teacher/admin)

**Query**

- `status` (optional): `pending|approved|rejected` (الافتراضي `approved`)
- `q` (optional): بحث بالاسم أو الإيميل
- `page`, `per_page`

**Response 200**

تنسيق `ApiPagination` مع عناصر:

- `id`
- `student: {id,name,email}`
- `status`
- `access_locked`
- `requested_at, approved_at, created_at`

---

## Teacher Courses Management

### GET `/teacher/courses` (JWT, teacher)

قائمة دورات المدرس (teacher_id = auth).

**Query**

- `q`, `page`, `per_page` (max 200)

**Response 200**: `ApiPagination`

### POST `/teacher/courses` (JWT, teacher)

**multipart/form-data أو JSON**

- `title` (required)
- `title_en` (optional)
- `description` (optional)
- `description_en` (optional)
- `price_cents` (required, int >= 0)
- `currency` (required)
- `sham_cash_code` (optional)
- `status` (required): `draft|published|archived`
- `image` أو `cover_image` (optional)

**Response 201**

- `course`

### PATCH `/teacher/courses/{course}` (JWT, teacher)

مثل create لكن حقول `sometimes`.

**Response 200**

- `course`

---

## Enrollments

### POST `/courses/{course}/enroll` (JWT, student)

ينشئ/يحدث تسجيل الطالب إلى `pending`.

**Response 201**

- `enrollment`

### POST `/courses/{course}/enroll/express` (JWT, student)

مسار "انضمام سريع" مخصص لوضع البوابة عندما تكون قيمة إعداد الموقع `score_degree=0`.

- يُنشئ/يعتمد تسجيل الطالب مباشرة (`approved`)
- يسجّل دفعة رمزية بدون إيصال داخل `payment_requests`:
  - `provider=portal_express`
  - `amount_paid_cents=1`
  - `status=approved`
- يفعّل فتح المحتوى بالكامل (progressive unlock بنسبة 100%)

**Response**

- **201** عند إنشاء عملية الانضمام لأول مرة
- **200** إذا كان الطالب منضمّاً مسبقاً (أو كان هناك سجل `portal_express` معتمد)

**Body**

- لا يتطلب حقول (يمكن إرسال `{}`).

### POST `/courses/{course}/enroll/approve` (JWT, teacher/admin)

المعلم يمكنه الموافقة فقط على دوراته.

**Body (JSON)**

- `user_id` (required, int)
- `status` (optional): `approved|rejected` (الافتراضي approved)

**Response 200**

- `enrollment`

### POST `/courses/{course}/enrollments/lock` (JWT, teacher/admin)

تعليق وصول الطالب للمحتوى (تبقى الحالة `approved` لكن `access_locked=true`).

**Body (JSON)**

- `user_id` (required)

**Response 200**

- `enrollment`: `{id,course_id,user_id,status,access_locked,access_locked_at,access_locked_by}`

**422** إذا التسجيل ليس `approved`.

### POST `/courses/{course}/enrollments/unlock` (JWT, teacher/admin)

**Body (JSON)**

- `user_id` (required)

**Response 200**

- `enrollment`: payload مماثل ولكن `access_locked=false`

### GET `/admin/enrollments` (JWT, admin)

**Query**

- `status` (optional): `pending|approved|rejected`
- `course_id` (optional)
- `user_id` (optional)
- `q` (optional): بحث ببيانات الطالب
- `page`, `per_page`

**Response 200**: `ApiPagination`

### GET `/admin/enrollments/{enrollment}` (JWT, admin)

**Response 200**

- `enrollment`: `{id,course_id,user_id,status,access_locked,note,created_at,updated_at}`

### POST `/admin/enrollments/{enrollment}/review` (JWT, admin)

يراجع فقط إذا كان `pending`.

**Body (JSON)**

- `status` (required): `approved|rejected`
- `note` (optional)

**Response 200**

- `enrollment`: `{id,status,access_locked,paid_amount_cents,unlocked_videos_count,unlocked_sessions_count,note}`

**409** إذا تمت المراجعة سابقاً.

---

## Payments

### POST `/payments` (JWT, student)

إرسال إشعار دفع مع إيصال (Sham Cash).

**multipart/form-data**

- `course_id` (required, int)
- `amount_paid_cents` (required, int)
- `subject_name` (required, string)
- `receipt` (required file, max 10MB, mimes: jpg/jpeg/png/webp)

حقول أكاديمية (إما IDs أو نص):

- IDs (مفضلة):
  - `study_term_id` (optional) (إذا موجود يملأ تلقائياً university/faculty/year IDs والنصوص)
  - أو `university_id`, `faculty_id`, `study_year_id`
- fallback نصي (للتوافق):
  - `university`, `study_year`, `study_term`

**Response**

- **201**: `payment`
- **422**: إذا كانت الدورة مدفوعة بالكامل أو نقص حقول أكاديمية

### GET `/users/me/payments` (JWT, student)

**Query**

- `course_id` (optional)
- `status` (optional): `pending|approved|rejected`
- `page`, `per_page`

**Response 200**: `ApiPagination`

### GET `/teacher/payments` (JWT, teacher)

يظهر الدفعات لدورات المدرس.

**Query**

- `status`, `course_id`, `page`, `per_page`

**Response 200**: `ApiPagination` مع `receipt.url`

### GET `/teacher/payments/{paymentRequest}` (JWT, teacher)

**Response 200**

- `payment` (مع `receipt.url`)

### GET `/teacher/payments/{paymentRequest}/receipt` (JWT, teacher)

تحميل الإيصال (binary download).

### GET `/admin/payments` (JWT, admin)

**Query**

- `status`, `course_id`, `user_id`, `page`, `per_page`

**Response 200**: `ApiPagination` مع `receipt.url`

### GET `/admin/payments/{paymentRequest}` (JWT, admin)

**Response 200**

- `payment` (مع `receipt.url`)

### POST `/admin/payments/{paymentRequest}/review` (JWT, admin)

يراجع فقط إذا `pending`.

**Body (JSON)**

- `status` (required): `approved|rejected`
- `note` (optional)

**Response 200**

- `payment` (الحالة بعد الحفظ)

عند `approved`:

- يتم إنشاء/تحديث `CourseEnrollment` إلى `approved` وتطبيق `EnrollmentPaymentProgress`
- إنشاء إشعار للطالب

### GET `/admin/payments/{paymentRequest}/receipt` (JWT, admin)

تحميل الإيصال (binary download).

---

## Course Files

### GET `/courses/{course}/files` (JWT)

**Access**

- admin: أي دورة
- teacher: فقط دورته
- student: فقط إذا enrollment `approved` و `access_locked=false`

**Response 200**

- `files[]` + `meta` (تنسيق `ApiPagination`)

### GET `/courses/{course}/files/{file}` (JWT)

**Response 200**

- `file`:
  - يحتوي مفاتيح التشفير: `cipher`, `content_key`, `content_iv`, `key_version`, `encrypted_sha256`
  - `download_path`: مسار التحميل النسبي

### GET `/courses/{course}/files/{file}/download` (JWT)

يرجع **bytes مشفرة** ويدعم Range عبر `LocalEncryptedBlobRangeResponse`.

### DELETE `/courses/{course}/files/{file}` (JWT, admin)

**Response 200**

- `{deleted:true,file_id}`

### POST `/courses/{course}/files` (JWT, teacher/admin)

رفع ملف مشفر (مرفق كامل).

**multipart/form-data**

- `file` (required, max ~2GB)
- `name` (optional)
- `name_en` (optional)
- `cipher` (required)
- `content_key` (required, base64 لِـ 16 bytes)
- `content_iv` (required, base64 لِـ 16 bytes)
- `key_version` (optional)
- `encrypted_sha256` (optional)

**Response 201**

- `file` (payload)

### Multipart Upload (local disk / Wasabi / Cloudflare R2) (JWT, teacher/admin)

#### POST `/courses/{course}/files/multipart/init`

**Body (JSON)**

- `original_name` (required)
- `name`, `name_en` (optional)
- `size_bytes`, `mime_type` (optional)
- `cipher`, `content_key`, `content_iv` (required)
- `key_version`, `encrypted_sha256` (optional)
- `storage_disk` (optional): `local` \| `wasabi` \| `r2`.
  - **`local`**: جلسة رفع متعدد الأجزاء على قرص التطبيق المحلي (`storage/app/private` عبر قرص `local`).
  - **`wasabi` / `r2`**: تخزين S3-متوافق؛ عند `r2` يكون `object_key` تحت `.../multipart/r2/...` فيُستنتج القرص في `sign-part` و`abort`.
  - **عند الغياب**: إذا كان **`FILESYSTEM_DISK`** (القرص الافتراضي في `config/filesystems.php`) هو **`local`**, يُستخدم مسار الرفع **المحلي** مباشرةً دون محاولة Wasabi/R2. وإلا يُجرَّب Wasabi ثم R2 (إن وُجدت الإعدادات)، ثم **الرجوع تلقائياً إلى محلي** إن فشل كلاهما أو لم يُضبطا.

**Response 201**

- `upload_id`, `object_key`, `storage_disk` (`local` أو `wasabi` أو `r2`)
- `part_size_bytes`, `recommended_part_size_bytes`, `max_parts`
- `expires_in_seconds`
- `multipart_token` (يستخدم لاحقاً في complete)

#### POST `/courses/{course}/files/multipart/sign-part`

**Body (JSON)**

- `upload_id` (required)
- `object_key` (required) يجب أن يبدأ بـ `course-files/{courseId}/multipart/`
- `part_number` (required 1..10000)

**Response 200**

- `{url, method:"PUT", headers:[], part_number}`
- عند `storage_disk: local`: `url` يشير إلى **`PUT /api/v1/courses/{id}/files/multipart/part?part_token=...`** على نفس الخادم؛ الهوية مثبتة عبر **`part_token`** (يمكن رفع الجزء بدون `Authorization` إن وُجد التوكن في الرابط). بخلاف ذلك يُفضَّل إرسال Bearer كالمعتاد.

#### POST `/courses/{course}/files/multipart/complete`

**Body (JSON)**

- `upload_id` (required)
- `object_key` (required)
- `multipart_token` (required)
- `parts[]` (required)
  - `parts[].part_number`
  - `parts[].etag`

**Response 201**

- `file` (payload)

#### POST `/courses/{course}/files/multipart/abort`

**Body (JSON)**

- `upload_id`
- `object_key`

**Response 200**

- `{aborted:true}`

---

## Course Chat

### GET `/courses/{course}/chat` (JWT)

**Response 200**

Laravel paginator لمراسلات الدورة، مع:

- `user:id,name`
- `file:id,name,storage_disk,storage_path`

### POST `/courses/{course}/chat` (JWT)

**Body (JSON)**

- `body` (optional)
- `type` (optional): `text|file`
- `course_file_id` (optional) إذا موجود غالباً يصبح `type=file`

**Response 201**

- `message` (مع `user`)

---

## Sessions (Student)

### GET `/courses/{course}/sessions` (JWT, student)

يعيد جلسات الدورة مع حالة الحضور + فتح المحتوى حسب الدفعات.

**Response 200**

- `sessions[]`
  - `attended` (boolean)
  - `payment_unlocked` (boolean)
  - إذا غير مفتوحة: `videos=[]`
  - إذا مفتوحة: `videos[]` ويضاف (نفس مبدأ Wasabi لكل التخزين المدعوم):
    - `storage_disk`
    - `wasabi_object_key` (مسار الملف على القرص أو مفتاح S3)
    - `wasabi_url` (رابط عام إن وُجد، مثل S3/Wasabi)
    - `wasabi_temporary_url` — **Presigned** لـ Wasabi/R2/S3، أو **رابط Laravel موقّع** لـ `local`/`public` (`GET /api/v1/courses/{course}/files/{file}/stream-signed?expires&signature`) يدعم Range بدون JWT
- `progress`: `{sessions_total,sessions_attended,progress_percent}`
- `unlock`: `{total_sessions,unlocked_sessions_count,total_videos,unlocked_videos_count}`

**423** إذا التسجيل غير approved أو الوصول معلق.

### POST `/courses/{course}/sessions/{session}/attend` (JWT, student)

تسجيل حضور جلسة.

**Response 200**

- `{attended:true}`

---

## Videos

### POST `/courses/{course}/videos` (JWT, teacher/admin)

ينشئ فيديو مرتبط بملف CourseFile موجود (عن طريق `storage_path`).

**Body (JSON)**

- `title` (required)
- `title_en` (optional)
- `description` (optional)
- `description_en` (optional)
- `storage_path` (required)
  - يجب أن يكون مسار download نسبي مثل:
    - `/api/v1/courses/{courseId}/files/{fileId}/download`
- `size_bytes` (optional)
- `duration_seconds` (optional)
- `mime_type` (optional)
- `cipher` (required): `AES-128-CBC`
- `content_key` (required, base64 16 bytes)
- `content_iv` (required, base64 16 bytes)
- `key_version` (optional)
- `encrypted_sha256` (optional, hex 64)

**Response 201**

- `video` (مختصر)

**422** إذا `storage_path` لا يطابق الدورة أو لا يشير لملف موجود.

### GET `/videos/{video}` (JWT)

**Response 200**

- `video` يتضمن `encrypted_stream_path` وحقول `wasabi_*` كما في الجلسات (رابط مؤقت مباشر للبايتات عند الإمكان).

### GET|HEAD `/courses/{course}/files/{file}/stream-signed` (موقّع، بدون JWT)

بث الملف المشفّر بنفس سلوك التحميل مع **HTTP Range**؛ يُولَّد عبر `URL::temporarySignedRoute` ويُعاد في `wasabi_temporary_url` للتخزين المحلي/العام.

**Query:** `disposition=inline|attachment` (اختياري)

### GET `/videos/{video}/encrypted` (JWT)

يبث bytes مشفرة (نفس blob الخاص بالملف المرتبط) ويدعم HTTP Range.

**Query**

- `disposition=inline|attachment` (optional)

**Response**

- binary stream
- **403** إذا status ليس active
- **404** إذا الملف غير موجود
- **422** إذا الفيديو لا يشير لمسار تحميل ملف صحيح

---

## Playback (Video Key Sessions)

### POST `/videos/{video}/playback/session` (JWT)

**Headers**

- `X-Device-Id` (required)

**Response 201**

- `session_id`
- `expires_at` (بعد 5 دقائق)
- `watermark: {text, seed}`

### POST `/videos/playback/key` (JWT فقط، throttle:playback-key)

**Headers**

- `X-Device-Id` (required) يجب أن يطابق الجهاز الخاص بالجلسة

**Body (JSON)**

- `session_id` (required)

**Response 200**

- `cipher`
- `content_key` (base64)
- `content_iv` (base64)
- `expires_at`

**Errors**

- **423**: `Device mismatch`
- **410**: `Session expired`

---

## Video Progress

### POST `/videos/{video}/progress` (JWT)

**Body (JSON)**

- `position_ms` (required, int, 0..864000000)
- `completed` (optional, boolean)

ملاحظة: إذا وصل `position_ms` إلى 95% من مدة الفيديو يتم اعتباره `completed=true`.

**Response 200**

- `progress: {course_video_id, position_ms, completed, updated_at}`

---

## Security Events

### POST `/security/events` (JWT)

**Headers**

- `X-Device-Id` (اختياري لكنه يُحفظ إن وجد)

**Body (JSON)**

- `type` (required, string)
- `payload` (optional, object/array)

**Response 201**

- `event`

### GET `/admin/security/events` (JWT, admin)

**Query**

- `type` (optional)
- `user_id` (optional)
- `device_id` (optional)
- `from` (optional date)
- `to` (optional date)
- `page`, `per_page`

**Response 200**

قائمة مع:

- `title` عربي لبعض الأنواع
- `subtitle` (اسم المستخدم أو email من payload)

### GET `/admin/security/events/{event}` (JWT, admin)

**Response 200**

- `event: {id,type,title,payload,device_id,created_at}`

---

## Admin - Users & Teachers & Devices

### POST `/admin/users/{user}/reset-device` (JWT, admin)

**Response 200**

- `{message:"Device lock reset"}`

### GET `/admin/users` (JWT, admin)

**Query**

- `role` (optional): `student|teacher|admin`
- `status` (optional): `active|frozen`
- `q` (optional)
- `page`, `per_page`

**Response 200**: `ApiPagination`

### GET `/admin/users/suggest` (JWT, admin)

**Query**

- `q` (required)
- `limit` (optional 1..20)
- `role` (optional)
- `status` (optional)

**Response 200**

- `data[]: {id,name,email,role,status}`

### POST `/admin/users/freeze` (JWT, admin)

تجميد حسب name/email وقد يكون مبهم.

**Body (JSON)**

- `user_name` (required) (اسم أو ايميل)
- `lock_id` (required)
- `reason` (optional)

**Response**

- **200**: `user` (frozen)
- **422**: إذا تطابق أكثر من مستخدم (`matches[]`)
- **404**: User not found

### POST `/admin/users/{user}/freeze` (JWT, admin)

**Body (JSON)**

- `lock_id` (required)
- `reason` (optional)

**Response 200**

- `user: {id,status,account_lock_id,frozen_at}`

### POST `/admin/users/{user}/unfreeze` (JWT, admin)

**Response 200**

- `user: {id,status,account_lock_id}`

### GET `/admin/teachers/pending` (JWT, admin)

**Query**

- `q`, `page`, `per_page`

**Response 200**: `ApiPagination`

### POST `/admin/teachers/{user}/approve` (JWT, admin)

**Body (JSON)**

- `note` (optional)

**Response 200**

- `teacher: {id,status:"approved",teacher_verified_at}`

### POST `/admin/teachers/{user}/reject` (JWT, admin)

**Body (JSON)**

- `note` (optional)

**Response 200**

- `teacher: {id,status:"rejected"}`

### GET `/admin/teachers` (JWT, admin)

**Query**

- `q` (optional)
- `status` (optional): `approved|pending|rejected`
- `page`, `per_page`

**Response 200**: `ApiPagination`

### GET `/admin/users/{user}/devices` (JWT, admin)

**Response 200**

- `user`: `{id,name,email,device_lock_enabled,locked_device_id,locked_device_at}`
- `devices`: قائمة `UserDevice`

### POST `/admin/users/{user}/devices/{userDevice}/deactivate` (JWT, admin)

**Response 200**

- `device` (بعد تعطيل `is_active=false`)

### POST `/admin/users/{user}/devices/{userDevice}/activate` (JWT, admin)

**Response 200**

- `device` (بعد تفعيل `is_active=true`)

### POST `/admin/users/{user}/lock-device` (JWT, admin)

**Body (JSON)**

- `device_id` (required)
- `enable_lock` (optional boolean, default true)

**Response 200**

- `{message:"User locked to device", user: ...}`

---

## Admin - Courses

### GET `/admin/courses` (JWT, admin)

**Query**

- `q`, `page`, `per_page` (max 200)

**Response 200**: `ApiPagination`

### POST `/admin/courses` (JWT, admin)

**multipart/form-data أو JSON**

- `teacher_id` (required) يجب أن يكون admin أو teacher approved
- `title` (required)
- `title_en` (optional)
- `description` (optional)
- `description_en` (optional)
- `price_cents` (required)
- `currency` (required)
- `sham_cash_code` (optional)
- `status` (required): `draft|published|archived`
- `image` أو `cover_image` (optional)

**Response 201**

- `course`

### PATCH `/admin/courses/{course}` (JWT, admin)

نفس الحقول لكن `sometimes`.

### DELETE `/admin/courses/{course}` (JWT, admin)

**Response 200**

- `{deleted:true}`

### POST `/admin/courses/{course}/assign-teacher` (JWT, admin)

**Body (JSON)**

- `teacher_id` (required) يجب أن يكون role=teacher و approved

**Response 200**

- `course: {id,title,teacher:{id,name}|null}`

---

## Admin - Course Sessions (Teacher/Admin)

> هذه المسارات تعمل تحت `role:teacher,admin` داخل مجموعة `admin/courses/{course}/sessions`.

### GET `/admin/courses/{course}/sessions` (JWT, teacher/admin)

**Response 200**

- `sessions[]` (مع `videos` و `videos_count`)

### POST `/admin/courses/{course}/sessions` (JWT, teacher/admin)

**Body (JSON)**

- `title` (required)
- `title_en` (optional)
- `sort_order` (optional)

**Response 201**

- `session`

### PATCH `/admin/courses/{course}/sessions/{session}` (JWT, teacher/admin)

**Body (JSON)**

- `title` (sometimes)
- `title_en` (sometimes)
- `sort_order` (sometimes)

**Response 200**

- `session`

### DELETE `/admin/courses/{course}/sessions/{session}` (JWT, teacher/admin)

**Response 200**

- `{deleted:true}`

### PUT `/admin/courses/{course}/sessions/{session}/videos` (JWT, teacher/admin)

ربط فيديوهات بالجلسة وتحديد ترتيبها.

**Body (JSON)**

- `items[]` (required)
  - `items[].course_video_id` (required)
  - `items[].sort_order` (optional)

**Response 200**

- `session` (محمل مع videos)

---

## Admin - Academics CRUD (admin)

### Universities

- GET `/admin/academics/universities`
- POST `/admin/academics/universities` (name required)
- PATCH `/admin/academics/universities/{university}`
- DELETE `/admin/academics/universities/{university}`

### Faculties

- GET `/admin/academics/universities/{university}/faculties`
- POST `/admin/academics/universities/{university}/faculties`
- PATCH `/admin/academics/universities/{university}/faculties/{faculty}`
- DELETE `/admin/academics/universities/{university}/faculties/{faculty}`

### Study Years

- GET `/admin/academics/faculties/{faculty}/study-years`
- POST `/admin/academics/faculties/{faculty}/study-years` (`year_number` required)
- PATCH `/admin/academics/faculties/{faculty}/study-years/{studyYear}`
- DELETE `/admin/academics/faculties/{faculty}/study-years/{studyYear}`

### Study Terms

- GET `/admin/academics/study-years/{studyYear}/study-terms`
- POST `/admin/academics/study-years/{studyYear}/study-terms` (`term_number` required)
- PATCH `/admin/academics/study-years/{studyYear}/study-terms/{studyTerm}`
- DELETE `/admin/academics/study-years/{studyYear}/study-terms/{studyTerm}`

### Courses attached to a term

- GET `/admin/academics/study-terms/{studyTerm}/courses`
- POST `/admin/academics/study-terms/{studyTerm}/courses` (course_id required) -> attach دون إزالة روابط أخرى
- DELETE `/admin/academics/study-terms/{studyTerm}/courses/{course}` -> detach

---

## Admin - Course Study Terms

### GET `/admin/courses/{course}/study-terms` (JWT, admin)

**Response 200**

- `course_id`
- `study_term_ids[]`
- `study_terms[]`

### PUT `/admin/courses/{course}/study-terms` (JWT, admin)

**Body (JSON)**

- `study_term_ids[]` (required)

**Response 200**

نفس Response الخاص بـ show.

