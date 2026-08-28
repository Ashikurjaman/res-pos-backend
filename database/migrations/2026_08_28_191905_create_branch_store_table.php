<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_store', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('product')->onDelete('cascade');
            $table->date('entrydate');
            $table->decimal('balanceinhand', 15, 3)->nullable();
            $table->decimal('stockbalancebefore', 15, 3)->nullable();
            $table->decimal('stockbalanceafter', 15, 3)->nullable();
            $table->decimal('sale_price', 15, 3)->nullable()->comment('cost price');
            $table->tinyInteger('status')->default(0);
            $table->tinyInteger('extra_status')->nullable();
            $table->integer('dis_status')->nullable();
            $table->integer('vat_rate')->nullable();
            $table->integer('sd_rate')->nullable();
            $table->decimal('scharge', 7, 3)->nullable();
            $table->foreignId('outlet_id')->constrained('outlets')->onDelete('cascade');
            $table->decimal('opening_balance', 15, 3)->nullable();
            $table->integer('food_type')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliersetup')->onDelete('set null');
            $table->decimal('purchase_price', 15, 3)->default(0);
            $table->decimal('total_amount', 15, 3)->default(0);
            $table->tinyInteger('validity')->default(1);
            $table->timestamps();

            $table->index('product_id');
            $table->index('outlet_id');
            $table->index('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_store');
    }
};
