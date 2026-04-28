# توثيق جلب المواد والأكاديميات (Universities / Academics API)

**المسار الأساسي:** `{{BASE_URL}}/api/v1`

هذا الملف يشرح **كيف تُجلب شجرة الأكاديميات** (جامعة → كلية → سنة → فصل) و**كيف ترتبط المقررات (المواد)** بفصل دراسي، وما الذي يتاح لكل من **الطالب** و**المدرس** و**المدير** في الـ API الحالي.

---

## 1. نموذج البيانات (مختصر)

```
University (جامعة)
  └── Faculty (كلية)
        └── StudyYear (سنة دراسية)
              └── StudyTerm (فصل دراسي)
                      └── Course (مقرر)  ← علاقة many-to-many عبر جدول الربط course_study_terms
```

- الطالب يُسجَّل عادةً بربط حسابه بـ **`study_term_id`** (فصل دراسي واحد كسياق أكاديمي).
- المقرر يمكن ربطه بـ **واحد أو أكثر** من `study_term_id` (من لوحة المدير).

---

## 2. جلب شجرة الأكاديميات (قراءة عامة — بدون JWT)

هذه النقاط **لا تتطلب** تسجيل دخول؛ مناسبة لشاشات التسجيل، الفلاتر، أو أي عميل يبني Cascading selects.

| الطريقة | المسار | معاملات Query | الاستجابة (مختصر) |
|--------|--------|----------------|-------------------|
| GET | `/academics/universities` | — | `universities[]` : `id`, `name`, `name_en`, `localized_name` |
| GET | `/academics/faculties` | `university_id` (مطلوب) | `faculties[]` |
| GET | `/academics/study-years` | `faculty_id` (مطلوب) | `study_years[]` |
| GET | `/academics/study-terms` | `study_year_id` (مطلوب) | `study_terms[]` |
| GET | `/academics/study-terms/{studyTerm}/courses` | `q`, `page`, `per_page` (اختياري) | Pagination لارافيل: **مقررات منشورة فقط** مربوطة بالفصل، مع `teacher` |
| GET | `/academics/study-term-context` | `study_term_id` (مطلوب) | أسماء ومعرّفات الجامعة/الكلية/السنة/الفصل دفعة واحدة |

**مثال تسلسلي (طالب يختار فصله):**

1. `GET /api/v1/academics/universities`
2. `GET /api/v1/academics/faculties?university_id=1`
3. `GET /api/v1/academics/study-years?faculty_id=2`
4. `GET /api/v1/academics/study-terms?study_year_id=3`
5. اختياري للتحقق من التسميات: `GET /api/v1/academics/study-term-context?study_term_id=5`

---

## 3. الطالب (Student)

### 3.1 السياق الأكاديمي للحساب

- بعد تسجيل الدخول، كائن **`user`** في `GET /auth/me` يتضمن حقولاً مثل `study_term_id` (وعند الحاجة `university_id`, `faculty_id`, `study_year_id`) حسب ما خُزِّن عند التسجيل أو عند التحديث.
- تحديث الفصل الأكاديمي للطالب:
  - **`PATCH /users/me/academic-profile`** (JWT + دور `student`)
  - Body: `{ "study_term_id": <int> }`

### 3.2 كتالوج المقررات المنشورة

- **`GET /courses`** (JWT مطلوب ضمن مجموعة `auth.jwt`…)
  - Query اختياري:
    - `q` — بحث نصي في العنوان والوصف
    - **`study_term_id`** — إن وُجد: يقتصر على المقررات **المنشورة** المربوطة بهذا الفصل (نفس منطق ربط المدير في `course_study_terms`)
    - `page`, `per_page` (حد أقصى 100)
  - يرجع Pagination لارافيل الافتراضي مع `teacher` وحقول المقرر (ومنها `cover_image_url` إن وُجد).

**جلب «مواد فصلي» بدون تسجيل دخول (كتالوج عام):**

- **`GET /academics/study-terms/{studyTerm}/courses`** — **بدون JWT**
  - نفس فلترة **منشور + مربوط بالفصل**، مع `q` و`page` و`per_page` (حد أقصى 100).
  - مفيد قبل تسجيل الدخول أو لشاشات عامة؛ بعد الدخول يمكن للطالب استخدام `GET /courses?study_term_id=<نفس id>` مع JWT إذا أردت تطبيق سياسات الجهاز/الحساب الموحدة.

### 3.3 تفاصيل مقرر

- **`GET /courses/{course}`** — تفاصيل المقرر، حالة التسجيل، تقدم الدفع للطالب، إلخ (كما في التوثيق العام).

---

## 4. المدرس (Teacher)

### 4.1 مقررات المدرس (ليست عبر شجرة جامعات في هذا المسار)

- **`GET /teacher/courses`** (JWT + `role:teacher`)
  - Query: `q`, `page`, `per_page` (حد أقصى 200)
  - يرجع المقررات التي **`teacher_id` = المستخدم الحقيقي**، بتنسيق `ApiPagination` (مثل قائمة المدير للمقررات من ناحية الشكل).

### 4.2 الأكاديميات كمرجع + كتالوج حسب الفصل

- المدرس يمكنه استخدام نفس نقاط **`/academics/...`** العامة (بدون JWT) إذا احتاج واجهة لعرض هيكل جامعات/فصول.
- **`GET /academics/study-terms/{studyTerm}/courses`** — مقررات منشورة للفصل (للمرجع أو للمقارنة مع مقرراته في `GET /teacher/courses`).
- **ربط مقرر بفصل دراسي** من الـ API الحالي يتم من **مسارات المدير**:
  - `POST /admin/academics/study-terms/{studyTerm}/courses`
  - أو `PUT /admin/courses/{course}/study-terms`
- **لا يوجد** في `routes/api.php` مسار للمدرس يقرأ «فصول هذا المقرر» (`GET /admin/courses/{course}/study-terms` مقصور على **admin**).

---

## 5. المدير (Admin)

### 5.1 إدارة شجرة الأكاديميات (CRUD)

كلها تحت **`role:admin`** و JWT:

| الطريقة | المسار | الغرض |
|--------|--------|--------|
| GET/POST | `/admin/academics/universities` | قائمة / إنشاء جامعة |
| PATCH/DELETE | `/admin/academics/universities/{university}` | تعديل / حذف |
| GET/POST | `/admin/academics/universities/{university}/faculties` | كليات الجامعة |
| PATCH/DELETE | `.../faculties/{faculty}` | تعديل / حذف كلية |
| GET/POST | `/admin/academics/faculties/{faculty}/study-years` | سنوات الكلية |
| PATCH/DELETE | `.../study-years/{studyYear}` | تعديل / حذف سنة |
| GET/POST | `/admin/academics/study-years/{studyYear}/study-terms` | فصول السنة |
| PATCH/DELETE | `.../study-terms/{studyTerm}` | تعديل / حذف فصل |

### 5.2 جلب مواد (مقررات) مرتبطة بفصل دراسي — لوحة الإدارة مقابل الكتالوج العام

| المسار | JWT | الفلترة | شكل الاستجابة |
|--------|-----|---------|----------------|
| **`GET /academics/study-terms/{studyTerm}/courses`** | لا | **منشور فقط** | Pagination لارافيل (مثل `GET /courses`) |
| **`GET /admin/academics/study-terms/{studyTerm}/courses`** | نعم (admin) | كل الحالات | JSON ثابت: `study_term_id` + مصفوفة `courses` (حقول محدودة) |

**`GET /admin/academics/study-terms/{studyTerm}/courses`** (JWT + admin)

- **Response 200 (شكل تقريبي):**

```json
{
  "study_term_id": 5,
  "courses": [
    { "id": 10, "title": "...", "title_en": "...", "teacher_id": 3 }
  ]
}
```

- الحقول المختارة من المقرر في الكود: `id`, `title`, `title_en`, `teacher_id` (بدون فلتر `published`؛ قد تظهر مقررات غير منشورة إن وُجدت مربوطة بالفصل).

### 5.3 ربط مقرر بفصل / فك الربط

| الطريقة | المسار | Body |
|--------|--------|------|
| POST | `/admin/academics/study-terms/{studyTerm}/courses` | `{ "course_id": <int> }` — يربط دون إزالة ربط المقرر بفصول أخرى |
| DELETE | `/admin/academics/study-terms/{studyTerm}/courses/{course}` | — يفك الربط |

### 5.4 فصول مقرر معيّن (مزامنة كاملة)

| الطريقة | المسار | الوصف |
|--------|--------|--------|
| GET | `/admin/courses/{course}/study-terms` | يعيد `course_id`, `study_term_ids[]`, `study_terms[]` |
| PUT | `/admin/courses/{course}/study-terms` | Body: `{ "study_term_ids": [1,2,3] }` — **يستبدل** كل روابط الفصول لهذا المقرر |

### 5.5 قائمة كل المقررات (ليست مرتبطة مباشرة بفلتر أكاديمي في المسار)

- **`GET /admin/courses`** — بحث `q` + pagination (كل المقررات بكل الحالات، مع `teacher`).

---

## 6. ملخص سريع حسب الدور

| الدور | جلب شجرة جامعات/فصول | جلب مقررات مربوطة بفصل محدد (`studyTerm`) | كتالوج مقررات عام |
|--------|----------------------|-------------------------------------------|---------------------|
| **بدون تسجيل** | نعم (`/academics/*`) | نعم منشور فقط (`GET /academics/study-terms/{id}/courses`) | لا |
| **طالب** | نعم (نفس المسارات العامة) | نعم: مسار عام أعلاه، أو `GET /courses?study_term_id=` مع JWT | نعم (`GET /courses`، اختياري `study_term_id`) |
| **مدرس** | نعم (عامة) | نعم (مسار عام منشور) | مقرراته (`GET /teacher/courses`) + اختياري كتالوج الفصل |
| **مدير** | نعم + CRUD تحت `/admin/academics/*` | نعم (`GET /admin/.../study-terms/{id}/courses` لكل الحالات) | نعم (`GET /admin/courses`) |

---

## 7. أمثلة cURL

**فصول سنة دراسية (عام):**

```bash
curl "{{BASE_URL}}/api/v1/academics/study-terms?study_year_id=3"
```

**مواد فصل معيّن — كتالوج منشور (بدون JWT):**

```bash
curl "{{BASE_URL}}/api/v1/academics/study-terms/5/courses?page=1&per_page=20"
```

**مواد فصل معيّن — طالب مع JWT (نفس المنطق):**

```bash
curl "{{BASE_URL}}/api/v1/courses?study_term_id=5&page=1&per_page=20" ^
  -H "Authorization: Bearer STUDENT_JWT"
```

**مواد فصل معيّن — مدير (يشمل غير المنشور):**

```bash
curl "{{BASE_URL}}/api/v1/admin/academics/study-terms/5/courses" ^
  -H "Authorization: Bearer ADMIN_JWT"
```

**كتالوج الطالب (كل المنشور):**

```bash
curl "{{BASE_URL}}/api/v1/courses?page=1&per_page=20" ^
  -H "Authorization: Bearer STUDENT_JWT"
```

---

## 8. مراجع أخرى

- التوثيق الشامل لبقية الـ API: `api_All.md`
