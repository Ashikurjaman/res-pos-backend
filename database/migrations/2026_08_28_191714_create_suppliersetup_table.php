<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliersetup', function (Blueprint $table) {
            $table->id();
            $table->date('entrydate');
            $table->string('supplier_name', 200);
            $table->text('address')->nullable();
            $table->string('contact_no', 50);
            $table->string('username', 100);
            $table->string('bin_nid', 150)->nullable();
            $table->decimal('ope_balance', 11, 2)->default(0);
            $table->decimal('adv_balance', 11, 2)->default(0);
            $table->decimal('due_balance', 11, 2)->default(0);
            $table->tinyInteger('validity')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliersetup');
    }
};
