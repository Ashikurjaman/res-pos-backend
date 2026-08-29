<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('company_name', 255);
            $table->string('outlet_name', 255);
            $table->string('address', 255);
            $table->string('contact_no', 255);
            $table->string('email', 155)->nullable();
            $table->string('slogan', 255);
            $table->tinyInteger('pay_type')->nullable()->comment('1=paid, 2=due');
            $table->tinyInteger('validity')->default(1);
            $table->timestamps();

            // Indexes
            $table->index('company_name');
            $table->index('validity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
