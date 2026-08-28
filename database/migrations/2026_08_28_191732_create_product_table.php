<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product', function (Blueprint $table) {
            $table->id();
            $table->date('entrydate');
            $table->string('product_name', 255);
            $table->string('product_code', 115)->unique();
            $table->decimal('cost_price', 15, 3)->default(0);
            $table->decimal('pur_price', 15, 3)->default(0);
            $table->decimal('sale_price', 15, 3)->default(0);
            $table->foreignId('category_id')->constrained('category_models')->onDelete('cascade');
            $table->foreignId('unit_id')->constrained('unitls')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('vat_rate')->default(0);
            $table->integer('sd_rate')->default(0);
            $table->decimal('scharge', 7, 3)->default(0);
            $table->tinyInteger('product_type')->nullable()->comment('1=sale product, 2=raw materials, 3=sub recipe');
            $table->string('product_image', 255)->nullable();
            $table->decimal('opening_balance', 15, 3)->default(0);
            $table->text('supplier_id')->nullable();
            $table->foreignId('food_type_id');
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('validity')->default(1);
            $table->timestamps();

            // Indexes
            $table->index('product_code');
            $table->index('category_id');
            $table->index('unit_id');
            $table->index('status');
            $table->index('validity');
            $table->index('food_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product');
    }
};
