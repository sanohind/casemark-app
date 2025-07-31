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
        Schema::table('scan_history', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['case_id']);
            
            // Drop case_id column
            $table->dropColumn('case_id');
            
            // Drop scanned_by column
            $table->dropColumn('scanned_by');
        });

        Schema::table('scan_history', function (Blueprint $table) {
            // Add case_no column
            $table->string('case_no', 50)->after('id');
            
            // Update status enum to only scanned and unscanned
            $table->enum('status', ['scanned', 'unscanned'])->default('scanned')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scan_history', function (Blueprint $table) {
            // Drop case_no column
            $table->dropColumn('case_no');
            
            // Revert status enum
            $table->enum('status', ['scanned', 'packed'])->default('scanned')->change();
        });

        Schema::table('scan_history', function (Blueprint $table) {
            // Add back case_id column
            $table->unsignedBigInteger('case_id')->after('id');
            
            // Add back scanned_by column
            $table->string('scanned_by', 100)->nullable()->after('status');
            
            // Add back foreign key constraint
            $table->foreign('case_id')->references('id')->on('cases')->onDelete('cascade');
        });
    }
};
