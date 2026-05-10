# Laravel Folder Structure

## Overview

The live session module follows Laravel's best practices with a clean architecture approach, separating concerns into distinct layers.

```
app/
├── Domain/
│   └── LiveSession/
│       ├── Models/
│       │   ├── LiveSession.php
│       │   ├── LiveSessionAsset.php
│       │   ├── LiveSessionParticipant.php
│       │   ├── LiveSessionEvent.php
│       │   ├── LiveSessionRecording.php
│       │   └── LiveSessionAttendance.php
│       │
│       ├── Enums/
│       │   ├── SessionStatus.php
│       │   ├── AssetType.php
│       │   ├── ParticipantRole.php
│       │   ├── ConnectionQuality.php
│       │   ├── EventType.php
│       │   └── RecordingStatus.php
│       │
│       ├── DTOs/
│       │   ├── LiveSessionDTO.php
│       │   ├── CreateLiveSessionDTO.php
│       │   ├── UpdateLiveSessionDTO.php
│       │   ├── AssetDTO.php
│       │   ├── EventDTO.php
│       │   ├── RecordingDTO.php
│       │   ├── ParticipantDTO.php
│       │   └── AttendanceDTO.php
│       │
│       ├── Services/
│       │   ├── LiveKitService.php
│       │   ├── LiveSessionService.php
│       │   ├── AssetService.php
│       │   ├── EventService.php
│       │   ├── RecordingService.php
│       │   ├── PlaybackService.php
│       │   └── NotificationService.php
│       │
│       ├── Repositories/
│       │   ├── Contracts/
│       │   │   ├── LiveSessionRepositoryInterface.php
│       │   │   ├── AssetRepositoryInterface.php
│       │   │   ├── EventRepositoryInterface.php
│       │   │   ├── RecordingRepositoryInterface.php
│       │   │   └── ParticipantRepositoryInterface.php
│       │   │
│       │   ├── LiveSessionRepository.php
│       │   ├── AssetRepository.php
│       │   ├── EventRepository.php
│       │   ├── RecordingRepository.php
│       │   └── ParticipantRepository.php
│       │
│       ├── Http/
│       │   ├── Controllers/
│       │   │   ├── LiveSessionController.php
│       │   │   ├── AssetController.php
│       │   │   ├── EventController.php
│       │   │   ├── RecordingController.php
│       │   │   └── ParticipantController.php
│       │   │
│       │   ├── Requests/
│       │   │   ├── CreateLiveSessionRequest.php
│       │   │   ├── UpdateLiveSessionRequest.php
│       │   │   ├── StartSessionRequest.php
│       │   │   ├── EndSessionRequest.php
│       │   │   ├── UploadAssetRequest.php
│       │   │   ├── CreateEventRequest.php
│       │   │   ├── GetTokenRequest.php
│       │   │   └── UpdateAttendanceRequest.php
│       │   │
│       │   ├── Resources/
│       │   │   ├── LiveSessionResource.php
│       │   │   ├── LiveSessionCollection.php
│       │   │   ├── AssetResource.php
│       │   │   ├── EventResource.php
│       │   │   ├── RecordingResource.php
│       │   │   ├── ParticipantResource.php
│       │   │   └── AttendanceResource.php
│       │   │
│       │   └── Middleware/
│       │       ├── EnsureSessionOwner.php
│       │       ├── EnsureSessionLive.php
│       │       └── EnsureSessionParticipant.php
│       │
│       ├── Policies/
│       │   ├── LiveSessionPolicy.php
│       │   ├── AssetPolicy.php
│       │   ├── EventPolicy.php
│       │   └── RecordingPolicy.php
│       │
│       ├── Events/
│       │   ├── SessionStarted.php
│       │   ├── SessionEnded.php
│       │   ├── UserJoinedSession.php
│       │   ├── UserLeftSession.php
│       │   ├── EventCreated.php
│       │   ├── RecordingCreated.php
│       │   └── RecordingReady.php
│       │
│       ├── Listeners/
│       │   ├── StartLiveKitRecording.php
│       │   ├── StopLiveKitRecording.php
│       │   ├── NotifyParticipants.php
│       │   ├── ProcessRecording.php
│       │   ├── CacheSessionState.php
│       │   └── TrackAttendance.php
│       │
│       └── Jobs/
│           ├── ProcessRecordingJob.php
│           ├── CompressAudioJob.php
│           ├── CleanupOldEventsJob.php
│           ├── ArchiveRecordingJob.php
│           └── SendNotificationJob.php
│
├── Services/
│   └── External/
│       ├── LiveKit/
│       │   ├── LiveKitClient.php
│       │   ├── LiveKitTokenGenerator.php
│       │   └── LiveKitRoomManager.php
│       │
│       └── Storage/
│           ├── AssetStorageService.php
│           └── RecordingStorageService.php
│
└── Support/
    └── Traits/
        ├── HasLiveSessions.php
        └── HasAttendance.php

database/
├── migrations/
│   ├── 2026_05_10_000001_create_live_sessions_table.php
│   ├── 2026_05_10_000002_create_live_session_assets_table.php
│   ├── 2026_05_10_000003_create_live_session_participants_table.php
│   ├── 2026_05_10_000004_create_live_session_events_table.php
│   ├── 2026_05_10_000005_create_live_session_recordings_table.php
│   └── 2026_05_10_000006_create_live_session_attendance_table.php
│
└── seeders/
    └── LiveSessionSeeder.php

routes/
├── api.php
│   └── Live session routes
│
└── channels.php
    └── WebSocket channel definitions

tests/
├── Unit/
│   ├── Domain/
│   │   └── LiveSession/
│   │       ├── Models/
│   │       ├── Services/
│   │       └── Repositories/
│   │
└── Feature/
    └── Api/
        └── LiveSession/
            ├── LiveSessionTest.php
            ├── AssetTest.php
            ├── EventTest.php
            └── RecordingTest.php
```

---

## Detailed Structure

### 1. Domain Layer (app/Domain/LiveSession/)

This is the core business logic layer, independent of the framework.

#### Models
All Eloquent models with relationships and casts.

#### Enums
PHP 8.2 enums for type-safe values.

#### DTOs (Data Transfer Objects)
Immutable classes for passing data between layers.

#### Services
Business logic implementation, no HTTP concerns.

#### Repositories
Data access abstraction using Repository pattern.

#### Policies
Authorization logic using Laravel's policy system.

#### Events & Listeners
Domain events and their handlers.

#### Jobs
Queue jobs for async processing.

---

### 2. HTTP Layer (app/Domain/LiveSession/Http/)

Framework-specific HTTP handling.

#### Controllers
Request handling, response formatting, service delegation.

#### Requests (Form Requests)
Validation rules and authorization checks.

#### Resources (API Resources)
Response formatting using Laravel API Resources.

#### Middleware
Custom middleware for route-specific logic.

---

### 3. External Services (app/Services/External/)

Third-party service integrations.

#### LiveKit
LiveKit SFU integration for audio streaming.

#### Storage
File storage abstraction for assets and recordings.

---

### 4. Support Layer (app/Support/)

Reusable traits and utilities.

---

## File Naming Conventions

### Models
- PascalCase, singular: `LiveSession.php`

### Enums
- PascalCase, singular: `SessionStatus.php`

### DTOs
- PascalCase, singular: `LiveSessionDTO.php`

### Services
- PascalCase, singular: `LiveSessionService.php`

### Repositories
- PascalCase, singular: `LiveSessionRepository.php`

### Controllers
- PascalCase, singular: `LiveSessionController.php`

### Requests
- PascalCase, singular: `CreateLiveSessionRequest.php`

### Resources
- PascalCase, singular: `LiveSessionResource.php`

### Events
- PascalCase, singular: `SessionStarted.php`

### Listeners
- PascalCase, singular: `StartLiveKitRecording.php`

### Jobs
- PascalCase, singular: `ProcessRecordingJob.php`

---

## Namespace Conventions

```php
// Models
namespace App\Domain\LiveSession\Models;

// Enums
namespace App\Domain\LiveSession\Enums;

// DTOs
namespace App\Domain\LiveSession\DTOs;

// Services
namespace App\Domain\LiveSession\Services;

// Repositories
namespace App\Domain\LiveSession\Repositories;

// Controllers
namespace App\Domain\LiveSession\Http\Controllers;

// Requests
namespace App\Domain\LiveSession\Http\Requests;

// Resources
namespace App\Domain\LiveSession\Http\Resources;

// Policies
namespace App\Domain\LiveSession\Policies;

// Events
namespace App\Domain\LiveSession\Events;

// Listeners
namespace App\Domain\LiveSession\Listeners;

// Jobs
namespace App\Domain\LiveSession\Jobs;

// External Services
namespace App\Services\External\LiveKit;
```

---

## Configuration

### Config Files

Create `config/livekit.php`:

```php
<?php

return [
    'api_key' => env('LIVEKIT_API_KEY'),
    'api_secret' => env('LIVEKIT_API_SECRET'),
    'host' => env('LIVEKIT_HOST', 'localhost'),
    'port' => env('LIVEKIT_PORT', 7880),
    'use_ssl' => env('LIVEKIT_USE_SSL', true),
    
    'room' => [
        'default_name_prefix' => env('LIVEKIT_ROOM_PREFIX', 'session_'),
        'default_max_participants' => env('LIVEKIT_MAX_PARTICIPANTS', 1000),
        'default_empty_timeout' => env('LIVEKIT_EMPTY_TIMEOUT', 300),
    ],
    
    'token' => [
        'ttl' => env('LIVEKIT_TOKEN_TTL', 3600),
        'algorithm' => env('LIVEKIT_TOKEN_ALGORITHM', 'HS256'),
    ],
    
    'audio' => [
        'default_codec' => 'opus',
        'default_bitrate' => 32000,
        'default_sample_rate' => 16000,
        'default_channels' => 1,
    ],
];
```

Create `config/live-session.php`:

```php
<?php

return [
    'storage' => [
        'assets_disk' => env('LIVE_SESSION_ASSETS_DISK', 's3'),
        'recordings_disk' => env('LIVE_SESSION_RECORDINGS_DISK', 's3'),
        'assets_path' => env('LIVE_SESSION_ASSETS_PATH', 'live-sessions/assets'),
        'recordings_path' => env('LIVE_SESSION_RECORDINGS_PATH', 'live-sessions/recordings'),
    ],
    
    'limits' => [
        'max_file_size' => env('LIVE_SESSION_MAX_FILE_SIZE', 52428800), // 50MB
        'max_participants' => env('LIVE_SESSION_MAX_PARTICIPANTS', 1000),
        'max_session_duration' => env('LIVE_SESSION_MAX_DURATION', 14400), // 4 hours
    ],
    
    'recording' => [
        'default_enabled' => env('LIVE_SESSION_RECORDING_ENABLED', true),
        'compression_enabled' => env('LIVE_SESSION_COMPRESSION_ENABLED', true),
    ],
    
    'cache' => [
        'session_ttl' => env('LIVE_SESSION_CACHE_TTL', 3600),
        'participants_ttl' => env('LIVE_SESSION_PARTICIPANTS_TTL', 300),
        'events_buffer_ttl' => env('LIVE_SESSION_EVENTS_TTL', 86400),
    ],
];
```

---

## Environment Variables

Add to `.env`:

```env
# LiveKit Configuration
LIVEKIT_API_KEY=your_api_key
LIVEKIT_API_SECRET=your_api_secret
LIVEKIT_HOST=livekit.example.com
LIVEKIT_PORT=7880
LIVEKIT_USE_SSL=true
LIVEKIT_ROOM_PREFIX=session_
LIVEKIT_MAX_PARTICIPANTS=1000
LIVEKIT_EMPTY_TIMEOUT=300
LIVEKIT_TOKEN_TTL=3600
LIVEKIT_TOKEN_ALGORITHM=HS256

# Live Session Configuration
LIVE_SESSION_ASSETS_DISK=s3
LIVE_SESSION_RECORDINGS_DISK=s3
LIVE_SESSION_ASSETS_PATH=live-sessions/assets
LIVE_SESSION_RECORDINGS_PATH=live-sessions/recordings
LIVE_SESSION_MAX_FILE_SIZE=52428800
LIVE_SESSION_MAX_PARTICIPANTS=1000
LIVE_SESSION_MAX_DURATION=14400
LIVE_SESSION_RECORDING_ENABLED=true
LIVE_SESSION_COMPRESSION_ENABLED=true
LIVE_SESSION_CACHE_TTL=3600
LIVE_SESSION_PARTICIPANTS_TTL=300
LIVE_SESSION_EVENTS_TTL=86400
```

---

## Service Provider

Create `app/Providers/LiveSessionServiceProvider.php`:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\LiveSession\Repositories\Contracts\LiveSessionRepositoryInterface;
use App\Domain\LiveSession\Repositories\Contracts\AssetRepositoryInterface;
use App\Domain\LiveSession\Repositories\Contracts\EventRepositoryInterface;
use App\Domain\LiveSession\Repositories\Contracts\RecordingRepositoryInterface;
use App\Domain\LiveSession\Repositories\Contracts\ParticipantRepositoryInterface;
use App\Domain\LiveSession\Repositories\LiveSessionRepository;
use App\Domain\LiveSession\Repositories\AssetRepository;
use App\Domain\LiveSession\Repositories\EventRepository;
use App\Domain\LiveSession\Repositories\RecordingRepository;
use App\Domain\LiveSession\Repositories\ParticipantRepository;
use App\Services\External\LiveKit\LiveKitClient;

class LiveSessionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(LiveSessionRepositoryInterface::class, LiveSessionRepository::class);
        $this->app->bind(AssetRepositoryInterface::class, AssetRepository::class);
        $this->app->bind(EventRepositoryInterface::class, EventRepository::class);
        $this->app->bind(RecordingRepositoryInterface::class, RecordingRepository::class);
        $this->app->bind(ParticipantRepositoryInterface::class, ParticipantRepository::class);
        
        // Service bindings
        $this->app->singleton(LiveKitClient::class, function ($app) {
            return new LiveKitClient(
                config('livekit.api_key'),
                config('livekit.api_secret'),
                config('livekit.host'),
                config('livekit.port'),
                config('livekit.use_ssl')
            );
        });
    }

    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(database_path('migrations/live-session'));
        
        // Load routes
        $this->loadRoutesFrom(base_path('routes/live-session.php'));
        
        // Publish config
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/livekit.php' => config_path('livekit.php'),
                __DIR__.'/../config/live-session.php' => config_path('live-session.php'),
            ], 'live-session-config');
        }
    }
}
```

---

## Next Steps

1. ✅ Architecture Documentation
2. ✅ Database Schema Design
3. ✅ API Specification
4. ✅ Laravel Folder Structure
5. ⏭️ Create Enum classes
6. ⏭️ Create Migration files
7. ⏭️ Create Eloquent Models
8. ⏭️ Create DTOs
9. ⏭️ Create Repository interfaces and implementations
10. ⏭️ Create Services
11. ⏭️ Create Form Requests
12. ⏭️ Create API Resources
13. ⏭️ Create Controllers
14. ⏭️ Create Policies
15. ⏭️ Create Events & Listeners
16. ⏭️ Create Jobs
17. ⏭️ Create API routes
18. ⏭️ Create WebSocket channels
19. ⏭️ Create tests
