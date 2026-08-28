<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliersetup')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('product')->onDelete('set null');
            $table->date('transaction_date');
            $table->enum('transaction_type', ['purchase', 'return', 'payment', 'adjustment']);
            $table->string('reference_no', 50)->nullable();
            $table->decimal('debit', 15, 3)->default(0);
            $table->decimal('credit', 15, 3)->default(0);
            $table->decimal('balance', 15, 3)->default(0);
            $table->text('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->tinyInteger('validity')->default(1);
            $table->timestamps();

            $table->index(['supplier_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_transactions');
    }
};
