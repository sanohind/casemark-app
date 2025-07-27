<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('cases', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('case_no', 50)->unique();
            $table->string('destination', 100);
            $table->string('order_no', 50)->nullable();
            $table->string('prod_month', 6);
            $table->string('case_size', 20);
            $table->decimal('gross_weight', 8, 2);
            $table->decimal('net_weight', 8, 2);
            $table->enum('status', ['active', 'packed', 'shipped'])->default('active');
            $table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('cases');
    }
};
