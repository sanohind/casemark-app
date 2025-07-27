<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('content_lists', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('case_id');
            $table->string('box_no', 20);
            $table->string('part_no', 50);
            $table->string('part_name', 255);
            $table->integer('quantity');
            $table->text('remark')->nullable();
            $table->timestamps();
            $table->foreign('case_id')->references('id')->on('cases')->onDelete('cascade');
        });
    }
    public function down() {
        Schema::dropIfExists('content_lists');
    }
};
