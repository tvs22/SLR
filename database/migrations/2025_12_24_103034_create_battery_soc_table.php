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
        Schema::create('battery_soc', function (Blueprint $table) {
            $table->id();
            $table->integer('hour');
            $table->integer('soc');
            $table->enum('type', ['soc_plans', 'soc_low_plans', 'current']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('battery_soc');
    }
};
