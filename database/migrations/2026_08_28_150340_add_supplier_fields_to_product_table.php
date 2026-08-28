<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product', function (Blueprint $table) {
            $table->decimal('purchase_price', 15, 3)->default(0)->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('product', function (Blueprint $table) {
            $table->dropColumn('purchase_price');
        });
    }
};
