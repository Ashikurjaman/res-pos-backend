<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_models', function (Blueprint $table) {
            $table->boolean('validity')->default(1)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('category_models', function (Blueprint $table) {
            $table->dropColumn('validity');
        });
    }
};
