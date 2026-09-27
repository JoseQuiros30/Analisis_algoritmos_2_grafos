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
        Schema::create('connections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('origin_station_id')->constrained('stations')->restrictOnDelete();
            $table->foreignId('destination_station_id')->constrained('stations')->restrictOnDelete();
            $table->string('line', 20);
            $table->unsignedInteger('base_time');
            $table->unsignedInteger('weather_penalty')->default(0);
            $table->unsignedInteger('peak_hour_penalty')->default(0);
            $table->unsignedInteger('congestion_penalty')->default(0);
            $table->unsignedInteger('transfer_penalty')->default(0);
            $table->timestamps();
            $table->unique(['origin_station_id', 'destination_station_id', 'line']);
            $table->index('destination_station_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('connections');
    }
};
