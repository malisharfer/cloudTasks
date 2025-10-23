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
        Schema::table('constraints', function (Blueprint $table) {
            // $table->index('soldier_id');
            // $table->index(['start_date', 'end_date']); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('constraints', function (Blueprint $table) {
            Schema::dropIfExists('constraints');
        });
    }
};
