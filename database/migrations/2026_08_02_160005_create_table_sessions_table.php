<?php

use App\Enums\SessionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Partial unique index: at most one OPEN session per dining table
     * (table_sessions_one_open_per_dining_table_unique).
     */
    public function up(): void
    {
        Schema::create('table_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dining_table_id')->constrained()->restrictOnDelete();
            $table->string('status')->default(SessionStatus::Open->value);
            $table->timestamp('opened_at');
            $table->timestamp('ended_at')->nullable();
            $table->boolean('ended_with_unacknowledged')->default(false);
            $table->unsignedInteger('unacknowledged_count_at_end')->nullable();
            $table->timestamps();

            $table->index(['restaurant_id', 'status']);
            $table->index(['restaurant_id', 'dining_table_id', 'status']);
            $table->index(['dining_table_id', 'status']);
        });

        // Enforce invariant: at most one open session per dining table (PostgreSQL).
        DB::statement('
            CREATE UNIQUE INDEX table_sessions_one_open_per_dining_table_unique
            ON table_sessions (dining_table_id)
            WHERE status = \'open\'
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS table_sessions_one_open_per_dining_table_unique');
        Schema::dropIfExists('table_sessions');
    }
};
