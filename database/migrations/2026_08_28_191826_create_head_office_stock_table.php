<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('head_office_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('product')->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained('suppliersetup')->onDelete('cascade');
            $table->date('entry_date');
            $table->decimal('quantity', 15, 3)->default(0);
            $table->decimal('purchase_price', 15, 3)->default(0);
            $table->decimal('total_amount', 15, 3)->default(0);
            $table->decimal('previous_balance', 15, 3)->default(0);
            $table->decimal('current_balance', 15, 3)->default(0);
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('validity')->default(1);
            $table->timestamps();

            $table->index(['product_id', 'supplier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('head_office_stock');
    }
};
