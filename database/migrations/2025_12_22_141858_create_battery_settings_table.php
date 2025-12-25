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
        Schema::create('battery_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('target_price_cents', 5, 2);
            $table->decimal('longterm_target_price_cents', 5, 2);
            $table->boolean('forced_discharge');
            $table->time('discharge_start_time');
            $table->decimal('target_electric_price_cents', 5, 2);
            $table->decimal('longterm_target_electric_price_cents', 5, 2);
            $table->boolean('forced_charge');
            $table->time('charge_start_time');
            $table->decimal('battery_level_percent', 5, 2);
            $table->enum('status', ['prioritize_charging', 'prioritize_selling', 'self_sufficient'])->default('self_sufficient');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('battery_settings');
    }
};
