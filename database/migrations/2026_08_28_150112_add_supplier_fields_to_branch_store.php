<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branch_store', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->constrained('suppliersetup')->onDelete('set null');
            $table->decimal('purchase_price', 15, 3)->default(0);
            $table->decimal('total_amount', 15, 3)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('branch_store', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['supplier_id', 'purchase_price', 'total_amount']);
        });
    }
};
