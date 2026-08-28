<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('food_types', function (Blueprint $table) {
            $table->id();
            $table->string('type_name', 100);
            $table->string('printer_ip', 50)->nullable();
            $table->tinyInteger('onlinestatus')->default(1)->comment('1=online, 0=offline');
            $table->tinyInteger('validity')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('food_types');
    }
};
