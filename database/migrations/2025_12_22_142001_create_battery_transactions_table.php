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
        Schema::create('battery_transactions', function (Blueprint $table) {
            $table->id();
            $table->timestamp('datetime');
            $table->decimal('price_cents', 5, 2);
            $table->string('action', 50);
            $table->foreignId('battery_id')->constrained('battery_settings');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('battery_transactions');
    }
};
