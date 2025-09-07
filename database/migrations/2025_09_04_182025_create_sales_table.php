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
        Schema::create('sales', function (Blueprint $table) {
            $table->bigIncrements('id'); // auto_increment primary key

            $table->date('entryDate');
            $table->string('invoiceNo', 100)->nullable();

            $table->decimal('discount', 15, 3)->default(0);
            $table->decimal('sd', 15, 3)->default(0);
            $table->decimal('vat', 15, 3)->default(0);
            $table->decimal('total', 15, 3);
            $table->decimal('received', 15, 3);
            $table->decimal('change', 15, 3);

            $table->string('paymentMode', 50); // ❌ no auto_increment here
            $table->unsignedBigInteger('user')->nullable(); // ❌ no auto_increment
            $table->tinyInteger('validity')->default(1);    // ❌ no auto_increment

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
