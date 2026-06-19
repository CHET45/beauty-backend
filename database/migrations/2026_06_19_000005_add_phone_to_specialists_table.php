<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('specialists', function (Blueprint $table) {
            $table->string('phone', 40)->nullable()->after('title');
        });

        $phones = [
            'Elena Petrova' => '+371 2911 2233',
            'Marcus Reid' => '+371 2940 1234',
            'Sofia Larsen' => '+371 2945 8821',
            'Aria Novak' => '+371 2903 5551',
        ];

        foreach ($phones as $name => $phone) {
            DB::table('specialists')->where('name', $name)->update(['phone' => $phone]);
        }
    }

    public function down(): void
    {
        Schema::table('specialists', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
