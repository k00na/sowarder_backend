<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dining_table_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('table_session_id')->constrained('table_sessions')->restrictOnDelete();
            $table->string('order_reference')->unique();
            $table->string('idempotency_key');
            $table->text('instructions')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->unique(['restaurant_id', 'idempotency_key']);
            $table->index(['restaurant_id', 'table_session_id']);
            $table->index(['restaurant_id', 'submitted_at']);
            $table->index(['table_session_id', 'acknowledged_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
