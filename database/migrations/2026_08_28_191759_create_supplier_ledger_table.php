<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_ledger', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date');
            $table->foreignId('supplier_id')->constrained('suppliersetup')->onDelete('cascade');
            $table->string('table_name', 255);
            $table->integer('unique_id');
            $table->text('description')->nullable();
            $table->decimal('debit_amt', 15, 3)->nullable();
            $table->decimal('credit_amt', 15, 3)->nullable();
            $table->tinyInteger('type')->comment('1=DR,2=CR');
            $table->decimal('closing_balance', 15, 3)->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->tinyInteger('validity')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_ledger');
    }
};
