// database/migrations/xxxx_xx_xx_add_previous_price_to_product_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product', function (Blueprint $table) {
            // ✅ Add previous_price column as nullable
            if (!Schema::hasColumn('product', 'previous_price')) {
                $table->decimal('previous_price', 15, 3)->nullable()->after('last_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product', function (Blueprint $table) {
            $table->dropColumn('previous_price');
        });
    }
};
