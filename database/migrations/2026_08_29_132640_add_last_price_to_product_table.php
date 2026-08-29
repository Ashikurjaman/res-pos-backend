// database/migrations/xxxx_xx_xx_add_last_price_to_product_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product', function (Blueprint $table) {
            // ✅ Add last_price column as nullable
            $table->decimal('last_price', 15, 3)->nullable()->after('pur_price');
        });
    }

    public function down(): void
    {
        Schema::table('product', function (Blueprint $table) {
            $table->dropColumn('last_price');
        });
    }
};
