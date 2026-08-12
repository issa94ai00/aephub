<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Capacity limits and the outcome of the last connection check.
 *
 * Two things the storage screen could not answer before.
 *
 * **How full is it.** A destination is a bucket someone pays for by the
 * gigabyte, or a server disk that also holds the database. The platform knew
 * how many bytes it had written there — every `course_files.size_bytes` and
 * `course_videos.size_bytes` carries it — but had nowhere to record the ceiling,
 * so it could not warn before a 150MB upload was the one that went over.
 * `quota_bytes` is that ceiling; null keeps the old behaviour of no limit.
 *
 * **Is it still reachable.** `testConnection()` already proved credentials work,
 * but the answer lived in a flash message that was gone on the next page load.
 * A destination whose keys were rotated last week looks identical to a healthy
 * one until an upload fails. Storing the result — with the round-trip time,
 * because a bucket that answers in four seconds is a problem of its own — makes
 * the state visible on the list instead of one click away.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storage_destinations', function (Blueprint $table) {
            // Null means unlimited, which is what every existing row wants:
            // nobody has set a ceiling, and inventing one here would start
            // rejecting uploads that work today.
            $table->unsignedBigInteger('quota_bytes')->nullable()->after('options');

            $table->timestamp('last_checked_at')->nullable()->after('is_default');

            // Null = never checked, and that is a different state from "checked
            // and failing" — the list says "unknown" rather than accusing a
            // destination nobody has tested yet.
            $table->boolean('last_check_ok')->nullable()->after('last_checked_at');

            // Provider errors are long; the useful part is at the front, and
            // the column is a display aid rather than a log.
            $table->string('last_check_message', 500)->nullable()->after('last_check_ok');

            $table->unsignedInteger('last_check_latency_ms')->nullable()->after('last_check_message');
        });
    }

    public function down(): void
    {
        Schema::table('storage_destinations', function (Blueprint $table) {
            $table->dropColumn([
                'quota_bytes',
                'last_checked_at',
                'last_check_ok',
                'last_check_message',
                'last_check_latency_ms',
            ]);
        });
    }
};
