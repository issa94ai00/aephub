# API Changes for Flutter Team — Course Files

## Summary

Updated the `GET /api/v1/courses/{id}/files` response to include `mime_type`, `original_name`, `storage_path`, and nested `encryption` object.

---

## New JSON Response Structure

```json
{
  "files": [
    {
      "id": 1,
      "course_id": 5,
      "name": "physics_chapter3.pdf.enc",
      "name_en": "Physics Chapter 3",
      "original_name": "physics_chapter3.pdf",
      "localized_name": "physics_chapter3.pdf",
      "mime_type": "application/pdf",
      "storage_disk": "local",
      "storage_path": "course-files/5/abc123.pdf.enc",
      "size_bytes": 2048000,
      "download_path": "/api/v1/courses/5/files/1/download",
      "encryption": {
        "cipher": "AES-128-CBC",
        "content_key": "base64key...",
        "content_iv": "base64iv...",
        "key_version": "v1",
        "encrypted_sha256": "hash..."
      }
    }
  ],
  "meta": { ... }
}
```

---

## What Changed (Before → After)

| Field | Before | After |
|---|---|---|
| `mime_type` | ❌ Missing | ✅ Always present (defaults to `"application/pdf"`) |
| `original_name` | ❌ Missing | ✅ Original file name before encryption (e.g. `"file.pdf"`) |
| `storage_path` | ❌ Missing | ✅ Server storage path |
| `encryption` | ❌ Flat fields at root | ✅ Nested under `encryption` object |
| `cipher` | Root level | `encryption.cipher` |
| `content_key` | Root level | `encryption.content_key` |
| `content_iv` | Root level | `encryption.content_iv` |
| `key_version` | Root level | `encryption.key_version` |
| `encrypted_sha256` | Root level | `encryption.encrypted_sha256` |

---

## Flutter Migration Guide

### 1. Update File Model

```dart
class CourseFile {
  final int id;
  final int courseId;
  final String name;
  final String? nameEn;
  final String? originalName;   // NEW
  final String localizedName;
  final String mimeType;         // NEW - always present
  final String storageDisk;
  final String storagePath;      // NEW
  final int? sizeBytes;
  final String downloadPath;
  final Encryption encryption;   // NEW - nested object

  // ...
}

class Encryption {
  final String cipher;
  final String contentKey;
  final String contentIv;
  final String? keyVersion;
  final String? encryptedSha256;
}
```

### 2. Update JSON Parsing

```dart
CourseFile.fromJson(Map<String, dynamic> json) => CourseFile(
  id: json['id'],
  courseId: json['course_id'],
  name: json['name'],
  nameEn: json['name_en'],
  originalName: json['original_name'],       // NEW
  localizedName: json['localized_name'],
  mimeType: json['mime_type'] ?? 'application/pdf',  // NEW
  storageDisk: json['storage_disk'],
  storagePath: json['storage_path'],         // NEW
  sizeBytes: json['size_bytes'],
  downloadPath: json['download_path'],
  encryption: Encryption.fromJson(json['encryption']),  // NESTED
);

Encryption.fromJson(Map<String, dynamic> json) => Encryption(
  cipher: json['cipher'],
  contentKey: json['content_key'],
  contentIv: json['content_iv'],
  keyVersion: json['key_version'],
  encryptedSha256: json['encrypted_sha256'],
);
```

### 3. Fix File Name Detection (No More Guessing)

**Before** (fragile):
```dart
if (n.endsWith('.pdf.enc')) return true;
if (n.endsWith('.enc')) {
  final base = n.substring(0, n.length - 4);
  return base.endsWith('.pdf');  // fails for "file.enc"
}
```

**After** (reliable):
```dart
final isEncrypted = file.encryption.cipher.isNotEmpty;
final originalExt = p.extension(file.originalName ?? file.name).toLowerCase();
final isPdf = originalExt == '.pdf' || file.mimeType == 'application/pdf';
```

### 4. Decryption — Use Nested Fields

```dart
// Before
final key = file.contentKey;
final iv = file.contentIv;

// After
final key = file.encryption.contentKey;
final iv = file.encryption.contentIv;
final cipher = file.encryption.cipher;
```

---

## Quick Verification (cURL)

| Check | Command | Expected |
|---|---|---|
| JSON structure | `curl /courses/1/files \| jq 'keys'` | `["files","meta"]` |
| `mime_type` exists | `curl ... \| jq '.files[0].mime_type'` | `"application/pdf"` |
| `original_name` exists | `curl ... \| jq '.files[0].original_name'` | ends with `.pdf` |
| `encryption` nested | `curl ... \| jq '.files[0].encryption \| keys'` | `["cipher","content_key","content_iv","key_version","encrypted_sha256"]` |
| File name | `curl ... \| jq '.files[0].name'` | ends with `.pdf` or `.pdf.enc` |
