<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('service_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('customer_name');
            $table->string('customer_phone')->index();

            $table->dateTime('starts_at');
            $table->dateTime('ends_at');

            $table->string('status')->default('pending');

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};