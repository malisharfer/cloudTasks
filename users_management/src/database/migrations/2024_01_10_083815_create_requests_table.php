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
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->string('submit_username');
            $table->string('identity', 9)->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone', 10);
            $table->string('email')->nullable();
            $table->string('unit');
            $table->string('sub');
            $table->string('authentication_type');
            $table->string('service_type');
            $table->date('expiration_date');
            $table->string('status');
            $table->string('description');
            $table->timestamps();
            $table->timestamp('update_status_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
