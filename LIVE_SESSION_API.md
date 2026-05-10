# API Specification

## Base URL
```
Production: https://api.example.com/api/v1
Development: http://localhost:8000/api/v1
```

## Authentication
- **Method**: Laravel Sanctum (Bearer Token)
- **Header**: `Authorization: Bearer {token}`
- **Token Type**: JWT with expiration
- **Role Claims**: `role` (teacher, student, admin)

## Common Response Format

### Success Response
```json
{
  "data": { ... },
  "meta": {
    "timestamp": "2026-05-10T00:00:00Z",
    "request_id": "uuid"
  }
}
```

### Error Response
```json
{
  "error": {
    "code": "ERROR_CODE",
    "message": "Human readable message",
    "details": { ... }
  },
  "meta": {
    "timestamp": "2026-05-10T00:00:00Z",
    "request_id": "uuid"
  }
}
```

### Pagination Response
```json
{
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": "...",
    "next": "..."
  }
}
```

---

## API Endpoints

### 1. Session Management

#### 1.1 Create Live Session
```http
POST /api/v1/live-sessions
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "course_id": 1,
  "lesson_id": 5,
  "title": "Advanced Calculus - Chapter 3",
  "description": "Integration techniques and applications",
  "scheduled_at": "2026-05-15T14:00:00Z",
  "max_participants": 500,
  "settings": {
    "recording_enabled": true,
    "allow_chat": true,
    "require_approval": false
  }
}
```

**Response (201 Created):**
```json
{
  "data": {
    "id": 123,
    "course_id": 1,
    "lesson_id": 5,
    "teacher_id": 42,
    "title": "Advanced Calculus - Chapter 3",
    "description": "Integration techniques and applications",
    "scheduled_at": "2026-05-15T14:00:00Z",
    "started_at": null,
    "ended_at": null,
    "status": "scheduled",
    "livekit_room_id": null,
    "max_participants": 500,
    "settings": {
      "recording_enabled": true,
      "allow_chat": true,
      "require_approval": false
    },
    "created_at": "2026-05-10T10:00:00Z",
    "updated_at": "2026-05-10T10:00:00Z"
  },
  "meta": {
    "timestamp": "2026-05-10T10:00:00Z",
    "request_id": "req_abc123"
  }
}
```

---

#### 1.2 Get Live Session
```http
GET /api/v1/live-sessions/{id}
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "data": {
    "id": 123,
    "course_id": 1,
    "lesson_id": 5,
    "teacher": {
      "id": 42,
      "name": "Dr. Ahmed Hassan",
      "email": "ahmed@example.com"
    },
    "title": "Advanced Calculus - Chapter 3",
    "description": "Integration techniques and applications",
    "scheduled_at": "2026-05-15T14:00:00Z",
    "started_at": "2026-05-15T14:05:00Z",
    "ended_at": null,
    "status": "live",
    "livekit_room_id": "RM_abc123xyz",
    "max_participants": 500,
    "current_participants": 234,
    "settings": {
      "recording_enabled": true,
      "allow_chat": true,
      "require_approval": false
    },
    "assets": [
      {
        "id": 1,
        "type": "pdf",
        "file_name": "calculus_chapter3.pdf",
        "file_size": 15728640,
        "page_count": 45,
        "download_url": "https://storage.example.com/signed-url...",
        "thumbnail_url": "https://storage.example.com/thumb..."
      }
    ],
    "recording": {
      "id": 456,
      "status": "processing",
      "duration_ms": null
    },
    "created_at": "2026-05-10T10:00:00Z",
    "updated_at": "2026-05-15T14:05:00Z"
  }
}
```

---

#### 1.3 Start Live Session
```http
POST /api/v1/live-sessions/{id}/start
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "recording_enabled": true
}
```

**Response (200 OK):**
```json
{
  "data": {
    "id": 123,
    "status": "live",
    "started_at": "2026-05-15T14:05:00Z",
    "livekit_room_id": "RM_abc123xyz",
    "livekit_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "livekit_url": "wss://livekit.example.com",
    "participant": {
      "identity": "user_42",
      "name": "Dr. Ahmed Hassan",
      "metadata": "{\"role\":\"teacher\",\"user_id\":42}"
    }
  },
  "meta": {
    "timestamp": "2026-05-15T14:05:00Z",
    "request_id": "req_def456"
  }
}
```

---

#### 1.4 End Live Session
```http
POST /api/v1/live-sessions/{id}/end
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "reason": "completed"
}
```

**Response (200 OK):**
```json
{
  "data": {
    "id": 123,
    "status": "ended",
    "ended_at": "2026-05-15T15:30:00Z",
    "duration_ms": 5100000,
    "participant_count": 234,
    "recording": {
      "id": 456,
      "status": "processing",
      "duration_ms": 5100000
    }
  },
  "meta": {
    "timestamp": "2026-05-15T15:30:00Z",
    "request_id": "req_ghi789"
  }
}
```

---

#### 1.5 List Live Sessions
```http
GET /api/v1/live-sessions?course_id=1&status=live&page=1&per_page=15
Authorization: Bearer {token}
```

**Query Parameters:**
- `course_id` (optional) - Filter by course
- `teacher_id` (optional) - Filter by teacher
- `status` (optional) - Filter by status (scheduled, live, ended, cancelled)
- `from_date` (optional) - Filter sessions from date
- `to_date` (optional) - Filter sessions to date
- `page` (optional) - Page number (default: 1)
- `per_page` (optional) - Items per page (default: 15, max: 100)

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 123,
      "course_id": 1,
      "teacher": {
        "id": 42,
        "name": "Dr. Ahmed Hassan"
      },
      "title": "Advanced Calculus - Chapter 3",
      "scheduled_at": "2026-05-15T14:00:00Z",
      "status": "scheduled",
      "current_participants": 0,
      "max_participants": 500
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 45,
    "last_page": 3
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  }
}
```

---

#### 1.6 Update Live Session
```http
PUT /api/v1/live-sessions/{id}
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "title": "Updated Title",
  "scheduled_at": "2026-05-16T14:00:00Z",
  "max_participants": 1000,
  "settings": {
    "recording_enabled": true
  }
}
```

**Response (200 OK):**
```json
{
  "data": {
    "id": 123,
    "title": "Updated Title",
    "scheduled_at": "2026-05-16T14:00:00Z",
    "max_participants": 1000,
    "updated_at": "2026-05-10T12:00:00Z"
  }
}
```

---

#### 1.7 Delete Live Session
```http
DELETE /api/v1/live-sessions/{id}
Authorization: Bearer {token}
```

**Response (204 No Content)**

---

### 2. Asset Management

#### 2.1 Upload Asset
```http
POST /api/v1/live-sessions/{id}/assets
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Request Body:**
```
file: [binary]
type: pdf
```

**Response (201 Created):**
```json
{
  "data": {
    "id": 1,
    "session_id": 123,
    "type": "pdf",
    "file_name": "calculus_chapter3.pdf",
    "file_size": 15728640,
    "mime_type": "application/pdf",
    "page_count": 45,
    "download_url": "https://storage.example.com/signed-url...",
    "thumbnail_url": "https://storage.example.com/thumb...",
    "uploaded_by": {
      "id": 42,
      "name": "Dr. Ahmed Hassan"
    },
    "created_at": "2026-05-15T14:00:00Z"
  }
}
```

---

#### 2.2 List Session Assets
```http
GET /api/v1/live-sessions/{id}/assets
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "type": "pdf",
      "file_name": "calculus_chapter3.pdf",
      "file_size": 15728640,
      "page_count": 45,
      "download_url": "https://storage.example.com/signed-url...",
      "thumbnail_url": "https://storage.example.com/thumb...",
      "created_at": "2026-05-15T14:00:00Z"
    }
  ]
}
```

---

#### 2.3 Delete Asset
```http
DELETE /api/v1/live-sessions/{id}/assets/{asset_id}
Authorization: Bearer {token}
```

**Response (204 No Content)**

---

### 3. Event Management

#### 3.1 Create Event
```http
POST /api/v1/live-sessions/{id}/events
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body (Draw Event):**
```json
{
  "type": "draw",
  "data": {
    "tool": "pen",
    "color": "#ff0000",
    "width": 3,
    "points": [[100,200], [105,210], [110,220]]
  },
  "timestamp_ms": 1715760300000
}
```

**Request Body (Page Change Event):**
```json
{
  "type": "page_change",
  "data": {
    "page": 5,
    "previous_page": 4
  },
  "timestamp_ms": 1715760305000
}
```

**Request Body (Equation Event):**
```json
{
  "type": "equation",
  "data": {
    "latex": "\\int_0^1 x^2 dx",
    "position": {"x": 100, "y": 200},
    "scale": 1.5
  },
  "timestamp_ms": 1715760310000
}
```

**Response (201 Created):**
```json
{
  "data": {
    "id": 789,
    "session_id": 123,
    "user_id": 42,
    "type": "draw",
    "data": {
      "tool": "pen",
      "color": "#ff0000",
      "width": 3,
      "points": [[100,200], [105,210], [110,220]]
    },
    "timestamp_ms": 1715760300000,
    "created_at": "2026-05-15T14:05:00Z"
  }
}
```

---

#### 3.2 Get Session Events
```http
GET /api/v1/live-sessions/{id}/events?from=1715760000000&to=1715763600000&limit=1000
Authorization: Bearer {token}
```

**Query Parameters:**
- `from` (optional) - Start timestamp in milliseconds
- `to` (optional) - End timestamp in milliseconds
- `type` (optional) - Filter by event type
- `limit` (optional) - Maximum events to return (default: 1000, max: 10000)

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 789,
      "type": "draw",
      "data": {
        "tool": "pen",
        "color": "#ff0000",
        "width": 3,
        "points": [[100,200], [105,210], [110,220]]
      },
      "timestamp_ms": 1715760300000
    },
    {
      "id": 790,
      "type": "page_change",
      "data": {
        "page": 5,
        "previous_page": 4
      },
      "timestamp_ms": 1715760305000
    }
  ],
  "meta": {
    "from": 1715760000000,
    "to": 1715763600000,
    "total_count": 1234
  }
}
```

---

### 4. Token Management

#### 4.1 Get LiveKit Token
```http
POST /api/v1/live-sessions/{id}/token
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "role": "student",
  "metadata": {
    "user_agent": "Mozilla/5.0..."
  }
}
```

**Response (200 OK):**
```json
{
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "livekit_url": "wss://livekit.example.com",
    "room_name": "RM_abc123xyz",
    "participant": {
      "identity": "user_456",
      "name": "Student Name",
      "metadata": "{\"role\":\"student\",\"user_id\":456}"
    },
    "expires_at": "2026-05-15T16:00:00Z"
  }
}
```

---

### 5. Recording Management

#### 5.1 Get Recording
```http
GET /api/v1/live-sessions/{id}/recording
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "data": {
    "id": 456,
    "session_id": 123,
    "audio_url": "https://storage.example.com/signed-audio...",
    "events_url": "https://storage.example.com/signed-events...",
    "duration_ms": 5100000,
    "audio_size_bytes": 31457280,
    "events_size_bytes": 5242880,
    "codec": "opus",
    "sample_rate": 16000,
    "channels": 1,
    "bitrate_kbps": 32,
    "status": "ready",
    "created_at": "2026-05-15T15:30:00Z"
  }
}
```

---

#### 5.2 List Recordings
```http
GET /api/v1/live-sessions/{id}/recordings
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 456,
      "duration_ms": 5100000,
      "status": "ready",
      "created_at": "2026-05-15T15:30:00Z"
    }
  ]
}
```

---

### 6. Participant Management

#### 6.1 Get Session Participants
```http
GET /api/v1/live-sessions/{id}/participants
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 42,
      "user": {
        "id": 42,
        "name": "Dr. Ahmed Hassan"
      },
      "role": "teacher",
      "joined_at": "2026-05-15T14:05:00Z",
      "left_at": null,
      "connection_quality": "excellent"
    },
    {
      "id": 2,
      "user_id": 456,
      "user": {
        "id": 456,
        "name": "Student Name"
      },
      "role": "student",
      "joined_at": "2026-05-15T14:06:00Z",
      "left_at": "2026-05-15T15:20:00Z",
      "connection_quality": "good"
    }
  ]
}
```

---

#### 6.2 Get Participant Statistics
```http
GET /api/v1/live-sessions/{id}/participants/stats
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "data": {
    "total_participants": 234,
    "current_participants": 180,
    "teacher": {
      "id": 42,
      "name": "Dr. Ahmed Hassan"
    },
    "by_role": {
      "teacher": 1,
      "student": 233
    },
    "by_connection_quality": {
      "excellent": 120,
      "good": 80,
      "fair": 30,
      "poor": 4
    },
    "peak_participants": 234,
    "peak_time": "2026-05-15T14:30:00Z"
  }
}
```

---

### 7. Attendance Management

#### 7.1 Mark Attendance Start
```http
POST /api/v1/live-sessions/{id}/attendance
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "recording_id": 456
}
```

**Response (201 Created):**
```json
{
  "data": {
    "id": 789,
    "recording_id": 456,
    "user_id": 456,
    "started_at": "2026-05-15T16:00:00Z",
    "last_position_ms": 0
  }
}
```

---

#### 7.2 Update Attendance
```http
PUT /api/v1/live-sessions/{id}/attendance/{attendance_id}
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "last_position_ms": 2550000,
  "duration_ms": 2550000
}
```

**Response (200 OK):**
```json
{
  "data": {
    "id": 789,
    "recording_id": 456,
    "user_id": 456,
    "started_at": "2026-05-15T16:00:00Z",
    "ended_at": "2026-05-15T16:42:30Z",
    "duration_ms": 2550000,
    "completion_pct": 50.00,
    "last_position_ms": 2550000,
    "updated_at": "2026-05-15T16:42:30Z"
  }
}
```

---

## WebSocket Events

### Connection
```javascript
const ws = new WebSocket('wss://api.example.com/ws/live-sessions/{id}?token={token}');
```

### Event Format
```json
{
  "event": "event_type",
  "data": { ... },
  "timestamp_ms": 1715760300000
}
```

### Event Types

#### 1. user_joined
```json
{
  "event": "user_joined",
  "data": {
    "user_id": 456,
    "name": "Student Name",
    "role": "student"
  },
  "timestamp_ms": 1715760300000
}
```

#### 2. user_left
```json
{
  "event": "user_left",
  "data": {
    "user_id": 456,
    "name": "Student Name",
    "role": "student"
  },
  "timestamp_ms": 1715760900000
}
```

#### 3. draw_event
```json
{
  "event": "draw_event",
  "data": {
    "user_id": 42,
    "tool": "pen",
    "color": "#ff0000",
    "width": 3,
    "points": [[100,200], [105,210], [110,220]]
  },
  "timestamp_ms": 1715760300000
}
```

#### 4. page_change_event
```json
{
  "event": "page_change_event",
  "data": {
    "user_id": 42,
    "page": 5,
    "previous_page": 4
  },
  "timestamp_ms": 1715760305000
}
```

#### 5. equation_event
```json
{
  "event": "equation_event",
  "data": {
    "user_id": 42,
    "latex": "\\int_0^1 x^2 dx",
    "position": {"x": 100, "y": 200},
    "scale": 1.5
  },
  "timestamp_ms": 1715760310000
}
```

#### 6. session_started
```json
{
  "event": "session_started",
  "data": {
    "session_id": 123,
    "started_at": "2026-05-15T14:05:00Z"
  },
  "timestamp_ms": 1715760300000
}
```

#### 7. session_ended
```json
{
  "event": "session_ended",
  "data": {
    "session_id": 123,
    "ended_at": "2026-05-15T15:30:00Z",
    "duration_ms": 5100000
  },
  "timestamp_ms": 1715765400000
}
```

---

## Error Codes

| Code | HTTP Status | Description |
|------|-------------|-------------|
| AUTH_REQUIRED | 401 | Authentication required |
| AUTH_INVALID | 401 | Invalid authentication token |
| AUTH_EXPIRED | 401 | Authentication token expired |
| FORBIDDEN | 403 | Access denied |
| NOT_FOUND | 404 | Resource not found |
| VALIDATION_ERROR | 422 | Request validation failed |
| SESSION_NOT_FOUND | 404 | Session not found |
| SESSION_ALREADY_STARTED | 400 | Session already started |
| SESSION_ALREADY_ENDED | 400 | Session already ended |
| SESSION_FULL | 400 | Session has reached max participants |
| INVALID_FILE_TYPE | 422 | Invalid file type |
| FILE_TOO_LARGE | 422 | File size exceeds limit |
| LIVEKIT_ERROR | 500 | LiveKit service error |
| RECORDING_ERROR | 500 | Recording processing error |
| INTERNAL_ERROR | 500 | Internal server error |

---

## Rate Limiting

| Endpoint | Limit | Window |
|----------|-------|--------|
| All endpoints | 1000 requests | 1 hour |
| POST /live-sessions | 10 requests | 1 hour |
| POST /live-sessions/{id}/events | 100 requests | 1 minute |
| GET /live-sessions/{id}/events | 60 requests | 1 minute |

Rate limit headers:
```
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1715763600
```

---

## Next Steps

1. ✅ Architecture Documentation
2. ✅ Database Schema Design
3. ✅ API Specification
4. ⏭️ Laravel Folder Structure
5. ⏭️ Implementation Phase
