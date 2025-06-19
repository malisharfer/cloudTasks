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
            $table->integer('max_shifts')->default(0)->nullable();
            $table->integer('max_nights')->default(0)->nullable();
            $table->integer('max_weekends')->default(0)->nullable();
            $table->integer('max_alerts')->default(0)->nullable();
            $table->integer('max_in_parallel')->default(0)->nullable();
            $table->integer('capacity');
            $table->boolean('is_trainee');
            $table->boolean('is_mabat');
            $table->json('qualifications');
            $table->boolean('is_reservist')->default(false);
            $table->json('last_reserve_dates')->nullable();
            $table->json('reserve_dates')->nullable();
            $table->json('next_reserve_dates')->nullable();
            $table->json('constraints_limit')->nullable();
            $table->boolean('not_thursday_evening')->default(false);
            $table->boolean('not_sunday_morning')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('soldiers');
    }
};
