# حذف حساب الطالب عبر API — دليل تكامل Flutter

## السلوك

- الحذف **منطقي (soft delete)**: يتم تعيين الحقل `users.status` إلى القيمة `deleted` (لا يُحذف الصف من قاعدة البيانات).
- بعد نجاح الطلب يُبطل **رمز JWT الحالي**؛ يجب على التطبيق حذف التوكن محلياً والانتقال لشاشة تسجيل الدخول.
- الطلاب فقط: المدرسون والمدراء لا يستطيعون استخدام هذا المسار (يُرجع **403**).

## المسار

| العنصر | القيمة |
|--------|--------|
| Method | `POST` |
| URL | `{BASE_URL}/api/v1/users/me/delete-account` |
| المصادقة | Header: `Authorization: Bearer {access_token}` |
| Middleware | نفس مجموعة المسارات المحمية: JWT + تجميد الحساب + قفل الجهاز + دور طالب |

## جسم الطلب (JSON)

```json
{
  "current_password": "كلمة المرور الحالية للمستخدم"
}
```

| الحقل | نوع | مطلوب | ملاحظات |
|--------|-----|--------|---------|
| `current_password` | `string` | نعم | للتحقق من هوية صاحب الحساب قبل الحذف |

## استجابات ناجحة

**200 OK** — تم التحديث وإبطال التوكن:

```json
{
  "message": "تم حذف الحساب بنجاح.",
  "status": "deleted"
}
```

## أخطاء شائعة

| HTTP | متى | مثال جسم الاستجابة |
|------|-----|---------------------|
| **401** | توكن مفقود/منتهي/غير صالح | `{ "message": "Unauthorized" }` |
| **403** | ليس طالباً، أو الحساب `deleted` عند محاولة استدعاء API آخر، أو قيود أخرى (مثل الجهاز) | حسب الـ middleware |
| **410** | الحساب مُعلَّم كـ `deleted` مسبقاً | `{ "message": "الحساب محذوف مسبقاً.", "status": "deleted" }` |
| **422** | كلمة المرور الحالية غير صحيحة | `{ "message": "..." }` (نفس رسالة تحديث كلمة المرور) |
| **423** | الحساب مجمّد (`frozen`) — لا يمكن إكمال الطلبات العادية حتى يُرفع التجميد | `{ "message": "Account is frozen", "status": "frozen" }` |

## تسجيل الدخول بعد الحذف

`POST /api/v1/auth/login` مع بريد وكلمة مرور صحيحة لحساب `deleted` يُرجع **403**:

```json
{
  "message": "تم حذف هذا الحساب ولا يمكن تسجيل الدخول.",
  "status": "deleted"
}
```

## مثال Dart (مبسّط)

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;

Future<void> deleteStudentAccount({
  required String baseUrl,
  required String accessToken,
  required String currentPassword,
}) async {
  final uri = Uri.parse('$baseUrl/api/v1/users/me/delete-account');
  final res = await http.post(
    uri,
    headers: {
      'Authorization': 'Bearer $accessToken',
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    },
    body: jsonEncode({'current_password': currentPassword}),
  );

  if (res.statusCode == 200) {
    // امسح التوكن محلياً وانتقل لتسجيل الدخول
    return;
  }
  // تعامل مع 401 / 403 / 410 / 422 حسب res.statusCode و jsonDecode(res.body)
}
```

## ملاحظات أمنية لتطبيق Flutter

- اطلب من المستخدم تأكيداً صريحاً (مربع حوار) قبل إرسال الطلب.
- بعد **200** لا تعتمد على التوكن القديم لأي طلب لاحق.
- عالج **403** مع `status == "deleted"` في استجابات API العامة بمسح الجلسة وإظهار رسالة مناسبة.
