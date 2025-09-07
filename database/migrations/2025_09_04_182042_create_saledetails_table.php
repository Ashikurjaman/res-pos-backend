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
        Schema::create('saledetails', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('sale_id'); // FK -> sales.id
            $table->string('invoiceNo', 100)->nullable();

            $table->unsignedBigInteger('product_id'); // FK -> products.id
            $table->string('product_name', 100);

            $table->decimal('quantity', 15, 3);
            $table->decimal('sd', 15, 3)->default(0);
            $table->decimal('vat', 15, 3)->default(0);
            $table->decimal('price', 15, 3);
            $table->decimal('total', 15, 3);

            $table->unsignedBigInteger('category_id'); // FK -> categories.id
            $table->unsignedBigInteger('user')->nullable(); // FK -> users.id
            $table->boolean('validity')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saledetails');
    }
};
