<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-destination upload options, and a real storage type.
 *
 * Two gaps this closes.
 *
 * `driver` was written as the literal 's3' by the controller no matter what,
 * so a destination could not actually be a local one and Cloudflare R2 could
 * not be told apart from Wasabi — even though R2 needs path-style addressing
 * and an "auto" region that Wasabi does not. It is a real discriminator now:
 * local, s3 (any S3-compatible endpoint), r2.
 *
 * `options` holds the knobs that decide how an upload behaves — part size,
 * how many parts in parallel, the ceiling on a single part, where objects
 * land, how long a streaming URL lives. Every one of those existed already,
 * as a global `.env` value in config/media_chunking.php. Global is the wrong
 * scope for them: object storage refuses parts under 5 MiB while the local
 * disk chokes on parts that large, so one number cannot serve both, and the
 * moment a platform has two destinations the single setting is wrong for one
 * of them.
 *
 * JSON rather than a column each: the set differs by type and will keep
 * changing, and a null column per knob per type is a schema that fights every
 * new option. Defaults live in the model, so a row storing `{}` behaves
 * exactly as the platform did before this migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storage_destinations', function (Blueprint $table) {
            $table->json('options')->nullable()->after('use_path_style');
        });
    }

    public function down(): void
    {
        Schema::table('storage_destinations', function (Blueprint $table) {
            $table->dropColumn('options');
        });
    }
};
