# Flutter Exams API / واجهة اختبارات الطالب

Bilingual reference for the **student** exam endpoints under Laravel `GET/POST/PUT /api/v1/...`.  
Documented from code only (`ExamController`, `ExamService`, models, `routes/api.php`).

مرجع ثنائي اللغة لنقاط نهاية **الطالب** للاختبارات. مُستخرج من الكود فقط — لا حقول مخترعة.

Base path: **`/api/v1`**

---

## 1. Overview / نظرة عامة

| EN | AR |
|----|-----|
| Exams are **course-linked** (`course_id`). Students list/take exams for a course they can access. | الاختبارات مرتبطة بدورة. الطالب يعرض/يحل اختبارات دورة يملك وصولاً إليها. |
| Question types: `multiple_choice`, `true_false`, `short_answer`. | أنواع الأسئلة: اختيار من متعدد، صح/خطأ، إجابة قصيرة. |
| Scoring is **instant** on submit (or auto-expire when time runs out). | التصحيح **فوري** عند التسليم (أو عند انتهاء الوقت تلقائياً). |
| Result includes points, percent, pass/fail vs `pass_percent`, and a **grade band** label/color. | النتيجة تتضمن الدرجات والنسبة والنجاح/الرسوب ووسم/لون **شريحة التقدير**. |
| Attempt statuses: `in_progress` → `submitted` or `expired`. | حالات المحاولة: جارية → مُسلَّمة أو منتهية بالوقت. |

Admin/teacher exam CRUD is **not** part of this student API.

إنشاء/تعديل الاختبار من لوحة الإدارة ليس جزءاً من واجهة الطالب هذه.

---

## 2. Auth & headers / المصادقة والترويسات

All exam routes sit in:

```
middleware: auth.jwt + account.freeze + device.lock
+ role:student
```

### Required headers / ترويسات إلزامية

| Header | Required | Notes |
|--------|----------|--------|
| `Authorization` | Yes | `Bearer <JWT>` |
| `X-Device-Id` | Yes | Non-empty string. Missing → **400** `{ "message": "X-Device-Id header is required" }` |
| `Accept` | Recommended | `application/json` |
| `Content-Type` | For JSON bodies | `application/json` |

### Optional device metadata / اختيارية (يُسجّلها `device.lock`)

- `X-Platform`
- `X-Device-Model`
- `X-OS-Version`
- `X-App-Version`

### Auth-related errors / أخطاء المصادقة

| Status | Typical message | When |
|--------|-----------------|------|
| 401 | Unauthorized | No/invalid JWT |
| 400 | `X-Device-Id header is required` | Missing device header |
| 423 | `Device locked to another device` | Device lock mismatch |
| 403 | (role middleware) | Non-student role |

---

## 3. Access rules / قواعد الوصول

### Course enrollment / التسجيل

Student must have a `CourseEnrollment` with **active course access**:

- `status === 'approved'`
- `access_locked === false` (`hasActiveCourseAccess()`)

Otherwise → **403** `{ "message": "Course access required" }`  
(used by list, show, and start).

### Published + availability / النشر والتوفر

| Check | Behavior |
|-------|----------|
| List | Only `status = published` exams |
| Show | Non-published → **404** `Exam not found` |
| Start | `isAvailableNow()` must be true (published + inside `available_from` / `available_until` window if set). Else **422** validation on `exam` |
| Empty exam | Start with 0 questions → **422** |
| Max attempts | Counts attempts with status `submitted` or `expired` only. If `max_attempts` is set and used ≥ max → **422**. `null` = unlimited |
| Open attempt | If an `in_progress` attempt exists and is not timed out, **start returns that same attempt** (no new row) |
| Exam ↔ course | `{exam}` must belong to `{course}` else **404** `Exam not found` |
| Attempt ownership | Attempt must belong to exam + current user else **404** `Attempt not found` |

`available_now` on list/show is the client-friendly boolean for the window; start enforces it again server-side.

---

## 4. Endpoints / النقاط

Shared prefix: `/api/v1/courses/{course}/exams...`  
`{course}` = course id, `{exam}` = exam id, `{attempt}` = attempt id.

---

### 4.1 List exams / قائمة الاختبارات

`GET /api/v1/courses/{course}/exams`

**Body:** none

**Success 200**

```json
{
  "data": [
    {
      "id": 1,
      "title": "Midterm",
      "description": "Chapter 1–3",
      "duration_minutes": 30,
      "pass_percent": 60.0,
      "max_attempts": 2,
      "questions_count": 10,
      "available_now": true,
      "available_from": "2026-08-01T00:00:00+00:00",
      "available_until": "2026-08-31T23:59:59+00:00",
      "my_attempts_count": 1,
      "open_attempt_id": 42,
      "best_score_percent": 85.0,
      "best_passed": true,
      "best_grade": "جيد جداً"
    }
  ]
}
```

| Field | Notes |
|-------|--------|
| `title` / `description` | Localized (`localized_*`); `description` may be `null` |
| `duration_minutes` | `null` = no timer |
| `max_attempts` | `null` = unlimited |
| `my_attempts_count` | Count of `submitted` + `expired` only |
| `open_attempt_id` | Id of current `in_progress` attempt, or `null` |
| `best_*` | Best among submitted/expired by `score_percent`; may be `null` |
| Dates | ISO-8601 or `null` |

**Errors:** 403 course access; 401/400/423 auth/device.

---

### 4.2 Show exam / تفاصيل الاختبار

`GET /api/v1/courses/{course}/exams/{exam}`

**Body:** none

**Success 200**

```json
{
  "data": {
    "id": 1,
    "title": "Midterm",
    "description": "Chapter 1–3",
    "duration_minutes": 30,
    "pass_percent": 60.0,
    "max_attempts": 2,
    "questions_count": 10,
    "shuffle_questions": true,
    "show_correct_answers": true,
    "available_now": true,
    "grade_bands": [
      {
        "min_percent": 90.0,
        "max_percent": 100.0,
        "label": "ممتاز",
        "color": "#34d399"
      }
    ]
  }
}
```

Note: grade band `label` is localized; `label_en` is **not** exposed on this endpoint (only `label` + `color` + percent range).

**Errors:** 403; 404 exam not found / wrong course / not published.

---

### 4.3 Start attempt / بدء محاولة

`POST /api/v1/courses/{course}/exams/{exam}/attempts`

**Body:** none (empty)

**Success 201** — attempt payload (see §6). Correctness fields hidden (`reveal = false`).

**Errors:**

| Status | Cause |
|--------|--------|
| 403 | No course access |
| 404 | Exam not in course |
| 422 | Not available / no questions / max attempts reached |

Typical 422 shape (Laravel):

```json
{
  "message": "...",
  "errors": {
    "exam": ["Exam is not available"]
  }
}
```

Possible `exam` validation messages (translated via `__()`):  
`Exam is not available` | `Exam has no questions` | `Maximum attempts reached`

---

### 4.4 Show attempt / عرض محاولة

`GET /api/v1/courses/{course}/exams/{exam}/attempts/{attempt}`

**Body:** none

**Behavior:**

- Server may auto-expire timed-out `in_progress` attempts before responding.
- Reveals correct answers / explanations **only if** attempt is closed (`submitted`/`expired`) **and** `exam.show_correct_answers === true`.

**Success 200** — attempt payload (§6).

**Errors:** 404 attempt/exam; ownership mismatch → 404.

---

### 4.5 Save answers (draft) / حفظ إجابات مؤقت

`PUT /api/v1/courses/{course}/exams/{exam}/attempts/{attempt}/answers`

**Body:**

```json
{
  "answers": [
    {
      "question_id": 11,
      "selected_option_id": 101,
      "text_answer": null
    }
  ]
}
```

| Field | Validation |
|-------|------------|
| `answers` | required array |
| `answers.*.question_id` | required integer |
| `answers.*.selected_option_id` | nullable integer |
| `answers.*.text_answer` | nullable string, max **2000** |

Unknown `question_id`s are **silently skipped**. Upserts per `(attempt_id, question_id)`.

**Success 200**

```json
{ "message": "Saved" }
```

**Errors:**

| Status | Cause |
|--------|--------|
| 422 | Attempt closed (`Attempt is closed`) or timed out during save (`Attempt timed out`) |
| 404 | Wrong attempt/exam/owner |
| 422 | Request validation failure |

---

### 4.6 Submit attempt / تسليم المحاولة

`POST /api/v1/courses/{course}/exams/{exam}/attempts/{attempt}/submit`

**Body** (answers optional — can rely on previously saved answers):

```json
{
  "answers": [
    {
      "question_id": 11,
      "selected_option_id": 101,
      "text_answer": null
    }
  ]
}
```

| Field | Validation |
|-------|------------|
| `answers` | nullable array |
| `answers.*.question_id` | required if row present |
| `answers.*.selected_option_id` | nullable integer |
| `answers.*.text_answer` | nullable string, max 2000 |

**Success 200**

```json
{
  "data": { /* attempt payload; reveal if show_correct_answers */ },
  "result": {
    "score_points": 3.0,
    "max_points": 4.0,
    "score_percent": 75.0,
    "passed": true,
    "grade_label": "جيد",
    "grade_label_en": "Good",
    "grade_color": "#a78bfa",
    "time_spent_seconds": 420,
    "status": "submitted"
  }
}
```

If attempt already closed, service returns existing graded attempt (idempotent-ish; no re-grade).

If time expired, status becomes `expired` (auto-submit grades saved answers).

---

## 5. Question types & answer submission / أنواع الأسئلة والإجابات

### Shared answer object (request)

```json
{
  "question_id": 1,
  "selected_option_id": null,
  "text_answer": null
}
```

### `multiple_choice`

- Options returned with `id` + `label` (localized).
- Submit: set **`selected_option_id`** to chosen option id.
- Leave `text_answer` null / omit.
- Graded correct if selected option belongs to question and `is_correct === true`.

### `true_false`

- Same as MCQ: two options (typically صح/خطأ or True/False).
- Submit via **`selected_option_id`** (not a boolean).
- Server does **not** shuffle options for `true_false` even if `shuffle_options` is on.

### `short_answer`

- `options` array is empty `[]` in the taking payload.
- Submit: set **`text_answer`** (string). Leave `selected_option_id` null.
- Graded against server `accepted_answers` (exact match after trim; case-insensitive unless question `case_sensitive` — that flag is **not** sent to the client).
- Empty / missing text → incorrect, 0 points.
- On reveal (if `show_correct_answers`): response may include `accepted_answers` and `explanation`.

### Per-question fields while taking / أثناء الحل

```json
{
  "id": 11,
  "type": "multiple_choice",
  "prompt": "2 + 2 = ?",
  "points": 2.0,
  "sort_order": 0,
  "options": [
    { "id": 101, "label": "3" },
    { "id": 102, "label": "4" }
  ],
  "answer": null
}
```

After save / on reload, `answer` may be:

```json
{
  "selected_option_id": 102,
  "text_answer": null,
  "is_correct": null,
  "points_awarded": null
}
```

When revealed (`reveal === true`):

- Option objects may include `"is_correct": true/false`
- Question may include `explanation`
- Short answer may include `accepted_answers`
- Answer may include real `is_correct` and `points_awarded`

---

## 6. Attempt lifecycle / دورة حياة المحاولة

```
list exams
  → show exam (rules, grade bands, shuffle flags)
  → start (or resume via open_attempt_id / POST start)
  → optional PUT save answers (autosave)
  → POST submit
  → show attempt / use submit `result` for instant score UI
```

### Attempt payload (`data` on start / showAttempt / submit)

```json
{
  "id": 42,
  "exam_id": 1,
  "status": "in_progress",
  "started_at": "2026-08-12T10:00:00+00:00",
  "submitted_at": null,
  "expires_at": "2026-08-12T10:30:00+00:00",
  "score_points": null,
  "max_points": null,
  "score_percent": null,
  "passed": null,
  "grade_label": null,
  "grade_label_en": null,
  "grade_color": null,
  "time_spent_seconds": null,
  "exam": {
    "id": 1,
    "title": "Midterm",
    "pass_percent": 60.0,
    "show_correct_answers": true
  },
  "questions": [ /* see §5 */ ]
}
```

| Field | Notes |
|-------|--------|
| `status` | `in_progress` \| `submitted` \| `expired` |
| `expires_at` | Only when **open** + `duration_minutes` set + `started_at` set; else `null`. = `started_at + duration_minutes` |
| Score fields | Filled after submit/expire |
| `questions` | Order may be shuffled server-side if `shuffle_questions` |

---

## 7. Instant result fields / حقول النتيجة الفورية

Prefer the dedicated `result` object from **submit**; same score fields also appear on the attempt `data` after grading.

| Field | Type | Meaning |
|-------|------|---------|
| `score_points` | float | Points awarded |
| `max_points` | float | Sum of question points |
| `score_percent` | float | Round `(score/max)*100`, 2 decimals; 0 if max=0 |
| `passed` | bool | `score_percent >= exam.pass_percent` |
| `grade_label` | string? | Band label (primary / AR side) |
| `grade_label_en` | string? | Band English label |
| `grade_color` | string? | Hex color from band |
| `time_spent_seconds` | int | Seconds from `started_at` to submit |
| `status` | string | `submitted` or `expired` |

Default bands (admin defaults; per-exam configurable): Excellent 90–100, Very good 80–89.99, Good 70–79.99, Pass 60–69.99, Fail 0–59.99.

---

## 8. UI / UX notes / ملاحظات الواجهة

| Topic | Guidance |
|-------|----------|
| Timer | Drive countdown from `expires_at` (ISO). If `null`, no timed limit. Also show `duration_minutes` from exam meta before start. |
| Autosave | Periodically `PUT .../answers`; on submit send final `answers` again (safe). |
| Resume | If list has `open_attempt_id`, navigate to show attempt or call start (returns same open attempt). |
| Correct answers | Hide until submit/expire **and** `show_correct_answers` is true. While `in_progress`, `is_correct` / option flags are null/absent. |
| Shuffle | Server shuffles questions (and MCQ options) when building payload if flags enabled. **True/false options are never shuffled.** Re-fetching may re-shuffle — prefer keeping local question order for one session after start. |
| Localization | Many strings already localized server-side (`title`, `prompt`, `label`, …). Result also exposes `grade_label` + `grade_label_en`. |
| Availability | Disable Start when `available_now === false`; still show window dates. |
| Attempts left | `max_attempts` null → unlimited; else `max_attempts - my_attempts_count` (open attempt does not consume until submitted/expired). |
| Timeout | Opening or saving a timed-out attempt triggers server auto-grade → status `expired`. Refresh UI from showAttempt. |
| Unanswered | Submit still grades missing questions as incorrect (0 points). |

---

## 9. Example JSON / أمثلة

### Start — `POST .../exams/1/attempts` → **201**

```http
POST /api/v1/courses/5/exams/1/attempts
Authorization: Bearer eyJhbGciOi...
X-Device-Id: flutter-device-abc123
Accept: application/json
```

```json
{
  "data": {
    "id": 42,
    "exam_id": 1,
    "status": "in_progress",
    "started_at": "2026-08-12T07:00:00+00:00",
    "submitted_at": null,
    "expires_at": "2026-08-12T07:30:00+00:00",
    "score_points": null,
    "max_points": null,
    "score_percent": null,
    "passed": null,
    "grade_label": null,
    "grade_label_en": null,
    "grade_color": null,
    "time_spent_seconds": null,
    "exam": {
      "id": 1,
      "title": "اختبار منتصف الفصل",
      "pass_percent": 60.0,
      "show_correct_answers": true
    },
    "questions": [
      {
        "id": 11,
        "type": "multiple_choice",
        "prompt": "2 + 2 = ؟",
        "points": 2.0,
        "sort_order": 0,
        "options": [
          { "id": 101, "label": "3" },
          { "id": 102, "label": "4" }
        ],
        "answer": null
      },
      {
        "id": 12,
        "type": "true_false",
        "prompt": "السماء زرقاء",
        "points": 1.0,
        "sort_order": 1,
        "options": [
          { "id": 201, "label": "صح" },
          { "id": 202, "label": "خطأ" }
        ],
        "answer": null
      },
      {
        "id": 13,
        "type": "short_answer",
        "prompt": "عاصمة فرنسا؟",
        "points": 1.0,
        "sort_order": 2,
        "options": [],
        "answer": null
      }
    ]
  }
}
```

### Submit — `POST .../attempts/42/submit` → **200**

```http
POST /api/v1/courses/5/exams/1/attempts/42/submit
Authorization: Bearer eyJhbGciOi...
X-Device-Id: flutter-device-abc123
Content-Type: application/json
```

```json
{
  "answers": [
    { "question_id": 11, "selected_option_id": 102, "text_answer": null },
    { "question_id": 12, "selected_option_id": 201, "text_answer": null },
    { "question_id": 13, "selected_option_id": null, "text_answer": "paris" }
  ]
}
```

Response (abbreviated; with `show_correct_answers: true`):

```json
{
  "data": {
    "id": 42,
    "exam_id": 1,
    "status": "submitted",
    "started_at": "2026-08-12T07:00:00+00:00",
    "submitted_at": "2026-08-12T07:07:00+00:00",
    "expires_at": null,
    "score_points": 4.0,
    "max_points": 4.0,
    "score_percent": 100.0,
    "passed": true,
    "grade_label": "ممتاز",
    "grade_label_en": "Excellent",
    "grade_color": "#34d399",
    "time_spent_seconds": 420,
    "exam": {
      "id": 1,
      "title": "اختبار منتصف الفصل",
      "pass_percent": 60.0,
      "show_correct_answers": true
    },
    "questions": [
      {
        "id": 11,
        "type": "multiple_choice",
        "prompt": "2 + 2 = ؟",
        "points": 2.0,
        "sort_order": 0,
        "options": [
          { "id": 101, "label": "3", "is_correct": false },
          { "id": 102, "label": "4", "is_correct": true }
        ],
        "explanation": null,
        "answer": {
          "selected_option_id": 102,
          "text_answer": null,
          "is_correct": true,
          "points_awarded": 2.0
        }
      },
      {
        "id": 13,
        "type": "short_answer",
        "prompt": "عاصمة فرنسا؟",
        "points": 1.0,
        "sort_order": 2,
        "options": [],
        "explanation": null,
        "accepted_answers": ["Paris", "باريس"],
        "answer": {
          "selected_option_id": null,
          "text_answer": "paris",
          "is_correct": true,
          "points_awarded": 1.0
        }
      }
    ]
  },
  "result": {
    "score_points": 4.0,
    "max_points": 4.0,
    "score_percent": 100.0,
    "passed": true,
    "grade_label": "ممتاز",
    "grade_label_en": "Excellent",
    "grade_color": "#34d399",
    "time_spent_seconds": 420,
    "status": "submitted"
  }
}
```

---

## 10. Suggested Flutter models & checklist / نماذج مقترحة وقائمة تحقق

### Models (sketch)

```dart
class ExamListItem {
  final int id;
  final String title;
  final String? description;
  final int? durationMinutes;
  final double passPercent;
  final int? maxAttempts;
  final int questionsCount;
  final bool availableNow;
  final DateTime? availableFrom;
  final DateTime? availableUntil;
  final int myAttemptsCount;
  final int? openAttemptId;
  final double? bestScorePercent;
  final bool? bestPassed;
  final String? bestGrade;
}

class ExamDetail {
  // + shuffleQuestions, showCorrectAnswers, gradeBands
}

class GradeBand {
  final double minPercent;
  final double maxPercent;
  final String label;
  final String? color;
}

class ExamAttemptView {
  final int id;
  final int examId;
  final String status; // in_progress | submitted | expired
  final DateTime? startedAt;
  final DateTime? submittedAt;
  final DateTime? expiresAt;
  final double? scorePoints;
  final double? maxPoints;
  final double? scorePercent;
  final bool? passed;
  final String? gradeLabel;
  final String? gradeLabelEn;
  final String? gradeColor;
  final int? timeSpentSeconds;
  final AttemptExamMeta exam;
  final List<ExamQuestionView> questions;
}

class ExamQuestionView {
  final int id;
  final String type; // multiple_choice | true_false | short_answer
  final String prompt;
  final double points;
  final int sortOrder;
  final List<ExamOptionView> options;
  final StudentAnswerView? answer;
  final String? explanation; // reveal only
  final List<String>? acceptedAnswers; // short_answer + reveal only
}

class ExamOptionView {
  final int id;
  final String label;
  final bool? isCorrect; // reveal only
}

class StudentAnswerView {
  final int? selectedOptionId;
  final String? textAnswer;
  final bool? isCorrect;
  final double? pointsAwarded;
}

class ExamResult {
  final double scorePoints;
  final double maxPoints;
  final double scorePercent;
  final bool passed;
  final String? gradeLabel;
  final String? gradeLabelEn;
  final String? gradeColor;
  final int? timeSpentSeconds;
  final String status;
}

class AnswerPayload {
  final int questionId;
  final int? selectedOptionId;
  final String? textAnswer;
}
```

### Flow checklist

- [ ] Attach JWT + `X-Device-Id` on every call  
- [ ] Gate UI on approved enrollment (handle 403)  
- [ ] List → detail → Start only if `available_now`  
- [ ] Resume via `open_attempt_id` / start idempotency  
- [ ] Render by `type`; MCQ/TF use option ids; short answer uses text  
- [ ] Timer from `expires_at`; on expiry refresh attempt (may be `expired`)  
- [ ] Autosave answers; submit with final answers  
- [ ] Show `result` immediately after submit  
- [ ] Reveal corrections only when closed + `show_correct_answers`  
- [ ] Respect `max_attempts` messaging from 422  

### Quick route map

| Method | Path |
|--------|------|
| GET | `/api/v1/courses/{course}/exams` |
| GET | `/api/v1/courses/{course}/exams/{exam}` |
| POST | `/api/v1/courses/{course}/exams/{exam}/attempts` |
| GET | `/api/v1/courses/{course}/exams/{exam}/attempts/{attempt}` |
| PUT | `/api/v1/courses/{course}/exams/{exam}/attempts/{attempt}/answers` |
| POST | `/api/v1/courses/{course}/exams/{exam}/attempts/{attempt}/submit` |

---

*Source of truth: `app/Http/Controllers/Api/ExamController.php`, `app/Services/ExamService.php`, exam models, `routes/api.php` (student group).*
