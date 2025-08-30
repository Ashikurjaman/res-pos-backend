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
        Schema::create('branch_stores', function (Blueprint $table) {
            $table->id();
            $table->string('product_id', 100);
            $table->string('product_name', 100);
            $table->string('category_id', 100);
            $table->string('category_name', 100);
            $table->integer('product_type');
            $table->integer('price');
            $table->integer('prv_stock')->default(0);
            $table->integer('stock')->default(0);
            $table->integer('after_stock')->default(0);
            $table->integer('product_code');
            $table->integer('unit');
            $table->integer('vat');
            $table->integer('sd');
            $table->integer('status')->default('1');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_stores');
    }
};
