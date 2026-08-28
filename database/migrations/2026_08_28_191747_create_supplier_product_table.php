<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliersetup')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('product')->onDelete('cascade');
            $table->decimal('purchase_price', 15, 3)->default(0);
            $table->tinyInteger('validity')->default(1);
            $table->timestamps();

            // Unique constraint to prevent duplicate entries
            $table->unique(['supplier_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_product');
    }
};
