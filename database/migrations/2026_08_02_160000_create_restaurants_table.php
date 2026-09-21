<?php

use App\Enums\RestaurantStatus;
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
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo_path')->nullable();
            $table->string('status')->default(RestaurantStatus::Enabled->value);
            $table->string('currency', 3);
            $table->string('timezone')->default('Indian/Maldives');
            $table->string('primary_color', 7)->default('#0F766E');
            $table->string('secondary_color', 7)->default('#134E4A');
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
