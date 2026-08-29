// database/migrations/xxxx_xx_xx_add_all_missing_columns_to_product_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product', function (Blueprint $table) {
            // ✅ Add dis_status column
            if (!Schema::hasColumn('product', 'dis_status')) {
                $table->integer('dis_status')->nullable()->after('user_id');
            }

            // ✅ Add expire column
            if (!Schema::hasColumn('product', 'expire')) {
                $table->string('expire', 20)->nullable()->after('sale_price');
            }

            // ✅ Add last_price column
            if (!Schema::hasColumn('product', 'last_price')) {
                $table->decimal('last_price', 15, 3)->nullable()->after('pur_price');
            }

            // ✅ Add previous_price column
            if (!Schema::hasColumn('product', 'previous_price')) {
                $table->decimal('previous_price', 15, 3)->nullable()->after('last_price');
            }

            // ✅ Add avg_price column
            if (!Schema::hasColumn('product', 'avg_price')) {
                $table->decimal('avg_price', 15, 3)->nullable()->after('previous_price');
            }

            // ✅ Add food_type_id column
            if (!Schema::hasColumn('product', 'food_type_id')) {
                $table->foreignId('food_type_id')->nullable()->after('food_type')->constrained('food_types')->onDelete('set null');
            }

            // ✅ Add imagepath column
            if (!Schema::hasColumn('product', 'imagepath')) {
                $table->string('imagepath', 255)->nullable()->after('product_image');
            }

            // ✅ Add mfExStatus column
            if (!Schema::hasColumn('product', 'mfExStatus')) {
                $table->string('mfExStatus', 20)->nullable()->after('unit_id');
            }

            // ✅ Add extra_status column
            if (!Schema::hasColumn('product', 'extra_status')) {
                $table->text('extra_status')->nullable()->after('mfExStatus');
            }

            // ✅ Add prdbelowrange column
            if (!Schema::hasColumn('product', 'prdbelowrange')) {
                $table->float('prdbelowrange', 9, 3)->nullable()->after('extra_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product', function (Blueprint $table) {
            $table->dropColumn([
                'dis_status',
                'expire',
                'last_price',
                'previous_price',
                'avg_price',
                'food_type_id',
                'imagepath',
                'mfExStatus',
                'extra_status',
                'prdbelowrange'
            ]);
        });
    }
};
