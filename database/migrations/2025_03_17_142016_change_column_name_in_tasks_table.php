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
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('is_alert')->nullable()->change();
            $table->string('is_weekend')->nullable()->change();
            $table->string('is_night')->nullable()->change();
            $table->string('in_parallel')->nullable()->change();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('is_alert')->nullable(false)->change();
            $table->string('is_weekend')->nullable(false)->change();
            $table->string('is_night')->nullable(false)->change();
            $table->string('in_parallel')->nullable(false)->change();

        });
    }
};
