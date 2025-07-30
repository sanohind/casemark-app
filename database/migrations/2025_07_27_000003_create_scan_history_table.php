<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('scan_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('case_id');
            $table->string('box_no', 20);
            $table->string('part_no', 50);
            $table->integer('scanned_qty');
            $table->integer('total_qty');
            $table->string('seq', 10); // Sequence number from barcode
            $table->enum('status', ['scanned', 'packed'])->default('scanned');
            $table->timestamp('scanned_at')->useCurrent();
            $table->string('scanned_by', 100)->nullable();
            $table->foreign('case_id')->references('id')->on('cases')->onDelete('cascade');
        });
    }
    public function down() {
        Schema::dropIfExists('scan_history');
    }
};