<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A move of everything stored on one destination onto another.
 *
 * Adding a destination was always possible; leaving one never was. A row in
 * `course_files` names the disk it lives on, so retiring a bucket means
 * rewriting every one of those rows in step with copying the object — and the
 * delete guard on the storage screen refuses to remove a destination precisely
 * because it cannot do that for you.
 *
 * This is that operation, made resumable and observable. It is a table rather
 * than a queued job alone because the interesting part is not the dispatch: a
 * few thousand videos take hours, the admin closes the tab, and the question on
 * the next visit is "how far did it get". Progress counters answer that, and
 * `cancel_requested` gives a way to stop without killing the worker.
 *
 * Disks are stored as strings, not foreign keys. `local` is a real destination
 * with no row behind it, and a disk key deliberately outlives the row that
 * created it — the same reason `course_files.storage_disk` is a string.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_transfers', function (Blueprint $table) {
            $table->id();

            $table->string('from_disk');
            $table->string('to_disk');

            $table->string('status', 20)->default('pending');

            // Counted up front so the progress bar has a denominator. It can
            // drift if a teacher uploads mid-transfer; the job treats it as the
            // estimate it is rather than a contract.
            $table->unsignedInteger('total_items')->default(0);
            $table->unsignedInteger('moved_items')->default(0);
            $table->unsignedInteger('failed_items')->default(0);

            $table->unsignedBigInteger('total_bytes')->default(0);
            $table->unsignedBigInteger('moved_bytes')->default(0);

            // Off by default. Keeping the source object turns a migration into
            // a copy that can be verified before anything becomes unrecoverable,
            // which is the order you want when the payload is course video.
            $table->boolean('delete_source')->default(false);

            // Set by the admin, read by the job between items. A flag rather
            // than a signal because the worker is a separate process and may be
            // on another machine.
            $table->boolean('cancel_requested')->default(false);

            $table->text('message')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->timestamps();

            // The screen asks "is anything running right now" on every load.
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_transfers');
    }
};
