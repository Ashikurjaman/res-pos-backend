<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unitls', function (Blueprint $table) {
            if (! Schema::hasColumn('unitls', 'validity')) {
                $table->tinyInteger('validity')->default(1)->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('unitls', function (Blueprint $table) {
            if (Schema::hasColumn('unitls', 'validity')) {
                $table->dropColumn('validity');
            }
        });
    }
};
