# Database Schema Design

## Table Relationships

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           live_sessions                                 │
│  - id (PK)                                                               │
│  - course_id (FK)                                                        │
│  - lesson_id (FK, nullable)                                             │
│  - teacher_id (FK)                                                       │
│  - title                                                                 │
│  - description                                                           │
│  - scheduled_at                                                          │
│  - started_at                                                            │
│  - ended_at                                                              │
│  - status (enum: scheduled, live, ended, cancelled)                     │
│  - livekit_room_id                                                       │
│  - max_participants                                                      │
│  - settings (JSON)                                                       │
│  - created_at, updated_at                                                │
└─────────────────────────────────────────────────────────────────────────┘
          │
          ├───┬──────────────────────────────────┬────────────────────┐
          │   │                                  │                    │
          ▼   ▼                                  ▼                    ▼
┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐
│live_session_assets│  │live_session_participants│ │live_session_events│ │live_session_recordings│
│  - id (PK)       │  │  - id (PK)      │  │  - id (PK)      │  │  - id (PK)      │
│  - session_id (FK)│ │  - session_id (FK)│ │  - session_id (FK)│ │  - session_id (FK)│
│  - type (enum)   │  │  - user_id (FK) │  │  - type (enum)  │  │  - audio_path   │
│  - file_path     │  │  - joined_at    │  │  - data (JSON)   │  │  - events_path  │
│  - file_name     │  │  - left_at      │  │  - timestamp_ms │  │  - duration_ms  │
│  - file_size     │  │  - role (enum)  │  │  - created_at   │  │  - file_size    │
│  - page_count    │  │  - ip_address   │  │                  │  │  - created_at   │
│  - created_at   │  │  - user_agent   │  │                  │  │                  │
└──────────────────┘  └──────────────────┘  └──────────────────┘  └──────────────────┘
                                                                                     │
                                                                                     ▼
                                                                          ┌──────────────────────┐
                                                                          │live_session_attendance│
                                                                          │  - id (PK)          │
                                                                          │  - recording_id (FK)│
                                                                          │  - user_id (FK)     │
                                                                          │  - joined_at        │
                                                                          │  - left_at          │
                                                                          │  - duration_ms      │
                                                                          │  - completion_pct   │
                                                                          └──────────────────────┘
```

---

## Table Specifications

### 1. live_sessions

Stores the main session information.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, Auto Increment | Primary key |
| course_id | BIGINT UNSIGNED | FK → courses.id | Associated course |
| lesson_id | BIGINT UNSIGNED | FK → lessons.id (nullable) | Associated lesson (optional) |
| teacher_id | BIGINT UNSIGNED | FK → users.id | Session teacher |
| title | VARCHAR(255) | NOT NULL | Session title |
| description | TEXT | NULLABLE | Session description |
| scheduled_at | TIMESTAMP | NULLABLE | Scheduled start time |
| started_at | TIMESTAMP | NULLABLE | Actual start time |
| ended_at | TIMESTAMP | NULLABLE | Actual end time |
| status | ENUM | NOT NULL, Default: scheduled | scheduled, live, ended, cancelled |
| livekit_room_id | VARCHAR(100) | UNIQUE, NULLABLE | LiveKit room identifier |
| max_participants | INT UNSIGNED | Default: 1000 | Maximum concurrent participants |
| settings | JSON | NULLABLE | Session settings (recording enabled, etc.) |
| created_at | TIMESTAMP | NOT NULL | Creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_course_id (course_id)
- INDEX idx_teacher_id (teacher_id)
- INDEX idx_status (status)
- INDEX idx_scheduled_at (scheduled_at)
- INDEX idx_livekit_room_id (livekit_room_id)

---

### 2. live_session_assets

Stores uploaded files (PDFs, images) for each session.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, Auto Increment | Primary key |
| session_id | BIGINT UNSIGNED | FK → live_sessions.id | Parent session |
| type | ENUM | NOT NULL | pdf, image |
| storage_disk | VARCHAR(50) | NOT NULL | Storage disk (local, s3, etc.) |
| storage_path | VARCHAR(500) | NOT NULL | File path in storage |
| file_name | VARCHAR(255) | NOT NULL | Original filename |
| file_size | BIGINT UNSIGNED | NOT NULL | File size in bytes |
| mime_type | VARCHAR(100) | NOT NULL | MIME type |
| page_count | INT UNSIGNED | NULLABLE (for PDFs) | Number of pages |
| thumbnail_path | VARCHAR(500) | NULLABLE | Thumbnail image path |
| uploaded_by | BIGINT UNSIGNED | FK → users.id | Uploader user |
| created_at | TIMESTAMP | NOT NULL | Upload timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_session_id (session_id)
- INDEX idx_type (type)

---

### 3. live_session_participants

Tracks participants who joined the session.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, Auto Increment | Primary key |
| session_id | BIGINT UNSIGNED | FK → live_sessions.id | Session |
| user_id | BIGINT UNSIGNED | FK → users.id | User |
| joined_at | TIMESTAMP | NOT NULL | Join timestamp |
| left_at | TIMESTAMP | NULLABLE | Leave timestamp |
| role | ENUM | NOT NULL | teacher, student, guest |
| ip_address | VARCHAR(45) | NULLABLE | Participant IP address |
| user_agent | VARCHAR(500) | NULLABLE | User agent string |
| connection_quality | ENUM | NULLABLE | excellent, good, fair, poor |
| unique(session_id, user_id) | - | UNIQUE | Prevent duplicate entries |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE INDEX unique_participant (session_id, user_id)
- INDEX idx_session_id (session_id)
- INDEX idx_user_id (user_id)
- INDEX idx_joined_at (joined_at)

---

### 4. live_session_events

Stores all real-time events (draw, page change, equations, text).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, Auto Increment | Primary key |
| session_id | BIGINT UNSIGNED | FK → live_sessions.id | Session |
| user_id | BIGINT UNSIGNED | FK → users.id (nullable) | Event creator (null for system) |
| type | ENUM | NOT NULL | draw, page_change, equation, text, clear, undo |
| data | JSON | NOT NULL | Event payload |
| timestamp_ms | BIGINT UNSIGNED | NOT NULL | Event timestamp in milliseconds |
| created_at | TIMESTAMP | NOT NULL | Creation timestamp |

**Data Schema Examples:**

**Draw Event:**
```json
{
  "tool": "pen",
  "color": "#ff0000",
  "width": 3,
  "points": [[100,200], [105,210], [110,220]]
}
```

**Page Change Event:**
```json
{
  "page": 5,
  "previous_page": 4
}
```

**Equation Event:**
```json
{
  "latex": "\\int_0^1 x^2 dx",
  "position": {"x": 100, "y": 200},
  "scale": 1.5
}
```

**Text Event:**
```json
{
  "value": "Important note",
  "position": {"x": 100, "y": 200},
  "font": "Arial",
  "size": 14
}
```

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_session_id (session_id)
- INDEX idx_timestamp_ms (timestamp_ms)
- INDEX idx_type (type)
- INDEX idx_created_at (created_at)
- INDEX idx_session_timestamp (session_id, timestamp_ms) - For playback queries

---

### 5. live_session_recordings

Stores recording metadata and file references.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, Auto Increment | Primary key |
| session_id | BIGINT UNSIGNED | FK → live_sessions.id | Parent session |
| storage_disk | VARCHAR(50) | NOT NULL | Storage disk |
| audio_path | VARCHAR(500) | NOT NULL | Compressed audio file path |
| events_path | VARCHAR(500) | NOT NULL | Events JSON file path |
| duration_ms | BIGINT UNSIGNED | NOT NULL | Recording duration in ms |
| audio_size_bytes | BIGINT UNSIGNED | NOT NULL | Audio file size |
| events_size_bytes | BIGINT UNSIGNED | NOT NULL | Events file size |
| codec | VARCHAR(20) | NOT NULL | Audio codec (opus, aac) |
| sample_rate | INT UNSIGNED | NOT NULL | Sample rate in Hz |
| channels | TINYINT UNSIGNED | NOT NULL | Number of audio channels |
| bitrate_kbps | INT UNSIGNED | NULLABLE | Audio bitrate |
| status | ENUM | NOT NULL, Default: processing | processing, ready, failed |
| processing_started_at | TIMESTAMP | NULLABLE | Processing start time |
| processing_ended_at | TIMESTAMP | NULLABLE | Processing end time |
| error_message | TEXT | NULLABLE | Error if processing failed |
| created_at | TIMESTAMP | NOT NULL | Creation timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_session_id (session_id)
- INDEX idx_status (status)
- INDEX idx_created_at (created_at)

---

### 6. live_session_attendance

Tracks attendance from recordings (playback analytics).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, Auto Increment | Primary key |
| recording_id | BIGINT UNSIGNED | FK → live_session_recordings.id | Recording |
| user_id | BIGINT UNSIGNED | FK → users.id | User |
| started_at | TIMESTAMP | NOT NULL | Playback start time |
| ended_at | TIMESTAMP | NULLABLE | Playback end time |
| duration_ms | BIGINT UNSIGNED | NOT NULL | Total watch duration |
| completion_pct | DECIMAL(5,2) | Default: 0.00 | Completion percentage |
| last_position_ms | BIGINT UNSIGNED | NOT NULL | Last watched position |
| ip_address | VARCHAR(45) | NULLABLE | User IP address |
| created_at | TIMESTAMP | NOT NULL | Creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_recording_id (recording_id)
- INDEX idx_user_id (user_id)
- INDEX idx_started_at (started_at)
- UNIQUE INDEX unique_view (recording_id, user_id, started_at)

---

## Enums

### Session Status
```php
enum SessionStatus: string
{
    case SCHEDULED = 'scheduled';
    case LIVE = 'live';
    case ENDED = 'ended';
    case CANCELLED = 'cancelled';
}
```

### Asset Type
```php
enum AssetType: string
{
    case PDF = 'pdf';
    case IMAGE = 'image';
}
```

### Participant Role
```php
enum ParticipantRole: string
{
    case TEACHER = 'teacher';
    case STUDENT = 'student';
    case GUEST = 'guest';
}
```

### Connection Quality
```php
enum ConnectionQuality: string
{
    case EXCELLENT = 'excellent';
    case GOOD = 'good';
    case FAIR = 'fair';
    case POOR = 'poor';
}
```

### Event Type
```php
enum EventType: string
{
    case DRAW = 'draw';
    case PAGE_CHANGE = 'page_change';
    case EQUATION = 'equation';
    case TEXT = 'text';
    case CLEAR = 'clear';
    case UNDO = 'undo';
}
```

### Recording Status
```php
enum RecordingStatus: string
{
    case PROCESSING = 'processing';
    case READY = 'ready';
    case FAILED = 'failed';
}
```

---

## Migration Files

### Migration: create_live_sessions_table

```php
<?php

use App\Enums\SessionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('lesson_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->enum('status', collect(SessionStatus::cases())->map(fn($c) => $c->value)->toArray())->default(SessionStatus::SCHEDULED->value);
            $table->string('livekit_room_id', 100)->unique()->nullable();
            $table->unsignedInteger('max_participants')->default(1000);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index('course_id');
            $table->index('teacher_id');
            $table->index('status');
            $table->index('scheduled_at');
            $table->index('livekit_room_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_sessions');
    }
};
```

### Migration: create_live_session_assets_table

```php
<?php

use App\Enums\AssetType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_session_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('live_sessions')->onDelete('cascade');
            $table->enum('type', collect(AssetType::cases())->map(fn($c) => $c->value)->toArray());
            $table->string('storage_disk', 50);
            $table->string('storage_path', 500);
            $table->string('file_name');
            $table->unsignedBigInteger('file_size');
            $table->string('mime_type', 100);
            $table->unsignedInteger('page_count')->nullable();
            $table->string('thumbnail_path', 500)->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index('session_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_session_assets');
    }
};
```

### Migration: create_live_session_participants_table

```php
<?php

use App\Enums\ParticipantRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_session_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('live_sessions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('joined_at');
            $table->timestamp('left_at')->nullable();
            $table->enum('role', collect(ParticipantRole::cases())->map(fn($c) => $c->value)->toArray());
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->enum('connection_quality', ['excellent', 'good', 'fair', 'poor'])->nullable();
            $table->unique(['session_id', 'user_id']);
            $table->timestamps();

            $table->index('session_id');
            $table->index('user_id');
            $table->index('joined_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_session_participants');
    }
};
```

### Migration: create_live_session_events_table

```php
<?php

use App\Enums\EventType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_session_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('live_sessions')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('type', collect(EventType::cases())->map(fn($c) => $c->value)->toArray());
            $table->json('data');
            $table->unsignedBigInteger('timestamp_ms');
            $table->timestamps();

            $table->index('session_id');
            $table->index('timestamp_ms');
            $table->index('type');
            $table->index('created_at');
            $table->index(['session_id', 'timestamp_ms']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_session_events');
    }
};
```

### Migration: create_live_session_recordings_table

```php
<?php

use App\Enums\RecordingStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_session_recordings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('live_sessions')->onDelete('cascade');
            $table->string('storage_disk', 50);
            $table->string('audio_path', 500);
            $table->string('events_path', 500);
            $table->unsignedBigInteger('duration_ms');
            $table->unsignedBigInteger('audio_size_bytes');
            $table->unsignedBigInteger('events_size_bytes');
            $table->string('codec', 20);
            $table->unsignedInteger('sample_rate');
            $table->unsignedTinyInteger('channels');
            $table->unsignedInteger('bitrate_kbps')->nullable();
            $table->enum('status', collect(RecordingStatus::cases())->map(fn($c) => $c->value)->toArray())->default(RecordingStatus::PROCESSING->value);
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processing_ended_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('session_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_session_recordings');
    }
};
```

### Migration: create_live_session_attendance_table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_session_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recording_id')->constrained('live_session_recordings')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedBigInteger('duration_ms');
            $table->decimal('completion_pct', 5, 2)->default(0.00);
            $table->unsignedBigInteger('last_position_ms');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('recording_id');
            $table->index('user_id');
            $table->index('started_at');
            $table->unique(['recording_id', 'user_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_session_attendance');
    }
};
```

---

## Data Volume Estimates

### Storage Requirements per Session (1 hour)

| Component | Size | Notes |
|-----------|------|-------|
| PDF Asset | 10-20 MB | One-time upload |
| Audio Recording | 30-40 MB | Opus @ 32 kbps |
| Event Log | 3-5 MB | Compressed JSON |
| Thumbnail | 1-2 MB | Generated from PDF |
| **Total** | **~45-65 MB** | Per session |

### Database Growth Estimates

| Table | Records per Session | Annual Estimate (1000 sessions) |
|-------|---------------------|----------------------------------|
| live_sessions | 1 | 1,000 |
| live_session_assets | 1-3 | 3,000 |
| live_session_participants | 50-500 | 250,000 |
| live_session_events | 5,000-50,000 | 25,000,000 |
| live_session_recordings | 1 | 1,000 |
| live_session_attendance | 50-500 | 250,000 |

**Recommended:**
- Partition `live_session_events` by `session_id` or date
- Archive old events after 1 year to cold storage
- Use read replicas for analytics queries

---

## Next Steps

1. ✅ Database Schema Design
2. ⏭️ Create Enum classes
3. ⏭️ Create Migration files
4. ⏭️ Create Eloquent Models
5. ⏭️ API Specification
