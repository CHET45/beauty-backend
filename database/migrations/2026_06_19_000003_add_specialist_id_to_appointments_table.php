<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Optional: a booking can be left without a specialist ("any") for
            // an admin to assign later. restrictOnDelete mirrors service_id —
            // a specialist with bookings cannot be deleted, only deactivated.
            $table->foreignId('specialist_id')
                ->nullable()
                ->after('service_id')
                ->constrained()
                ->restrictOnDelete();

            $table->index(['specialist_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['specialist_id', 'starts_at']);
            $table->dropConstrainedForeignId('specialist_id');
        });
    }
};
