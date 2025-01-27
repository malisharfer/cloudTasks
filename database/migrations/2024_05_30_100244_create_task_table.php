<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->time('start_hour');
            $table->integer('duration');
            $table->integer('parallel_weight')->default(0);
            $table->string('type');
            $table->string('color');
            $table->boolean('is_alert');
            $table->boolean('is_weekend');
            $table->boolean('is_night');
            $table->boolean('in_parallel')->default(false);
            $table->json('concurrent_tasks')->nullable();
            $table->string('department_name')->nullable();
            $table->json('recurring');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIfExists();
            $table->dropSoftDeletes();
        });

    }
};
