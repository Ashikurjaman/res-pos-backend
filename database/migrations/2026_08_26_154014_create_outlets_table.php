// database/migrations/xxxx_xx_xx_create_outlets_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outlets', function (Blueprint $table) {
            $table->id();
            $table->string('entrydate', 111)->nullable();
            $table->string('outlet_code', 50)->unique();
            $table->string('outlet_name', 255);
            $table->string('short_name', 30)->nullable();
            $table->string('outlet_address', 255);
            $table->string('outlet_mgr', 100);
            $table->string('mgr_contact_no', 111);
            $table->string('ho_mobile_no', 111);
            $table->integer('status')->default(1);
            $table->string('vat_reg_no_old', 111)->nullable();
            $table->string('vat_reg_no_new', 111)->nullable();
            $table->tinyInteger('validity')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlets');
    }
};
