<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update existing data
        DB::table('cases')->where('status', 'active')->update(['status' => 'unpacked']);
        DB::table('cases')->where('status', 'shipped')->update(['status' => 'packed']);

        Schema::table('cases', function (Blueprint $table) {
            // Update status enum to only unpacked and packed
            $table->enum('status', ['unpacked', 'packed'])->default('unpacked')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            // Revert status enum to original
            $table->enum('status', ['active', 'packed', 'shipped'])->default('active')->change();
        });

        // Revert data changes
        DB::table('cases')->where('status', 'unpacked')->update(['status' => 'active']);
    }
};
