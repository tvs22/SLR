<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('battery_strategies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->time('sell_start_time')->nullable();
            $table->time('sell_end_time')->nullable();
            $table->time('buy_start_time')->nullable();
            $table->time('buy_end_time')->nullable();
            $table->unsignedInteger('soc_lower_bound')->default(0);
            $table->unsignedInteger('soc_upper_bound')->default(100);
            $table->string('strategy_group')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('battery_strategies');
    }
};
