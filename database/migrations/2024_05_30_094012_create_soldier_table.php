<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('soldiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->nullable();
            $table->boolean('gender');
            $table->boolean('is_permanent')->default(false);
            $table->dateTime('enlist_date')->nullable();
            $table->integer('course');
            $table->boolean('has_exemption');
            $table->integer('max_shift')->nullable();
            $table->integer('max_night')->nullable();
            $table->integer('max_weekend')->nullable();
            $table->integer('capacity');
            $table->integer('capacity_hold');
            $table->boolean('is_trainee');
            $table->boolean('is_mabat');
            $table->json('qualifications');
            $table->boolean('is_reservist')->default(false);
            $table->json('reserve_dates')->nullable();
            $table->json('next_reserve_dates')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('soldiers');
    }
};
