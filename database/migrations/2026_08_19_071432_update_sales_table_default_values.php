<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('total', 10, 2)->default(0)->change();
            $table->decimal('discount', 10, 2)->default(0)->change();
            $table->decimal('sd', 10, 2)->default(0)->change();
            $table->decimal('vat', 10, 2)->default(0)->change();
            $table->decimal('received', 10, 2)->default(0)->change();
            $table->decimal('change', 10, 2)->default(0)->change();
        });
    }

    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('total', 10, 2)->nullable()->change();
            $table->decimal('discount', 10, 2)->nullable()->change();
            $table->decimal('sd', 10, 2)->nullable()->change();
            $table->decimal('vat', 10, 2)->nullable()->change();
            $table->decimal('received', 10, 2)->nullable()->change();
            $table->decimal('change', 10, 2)->nullable()->change();
        });
    }
};