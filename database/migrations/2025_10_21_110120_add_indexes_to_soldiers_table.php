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
        Schema::table('soldiers', function (Blueprint $table) {
            // $table->index('team_id');
            // $table->index('type');
            // $table->index('course');
            // $table->index('qualifications');
            // $table->index('is_reservist');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('soldiers', function (Blueprint $table) {
            Schema::dropIfExists('soldiers');
        });
    }
};
