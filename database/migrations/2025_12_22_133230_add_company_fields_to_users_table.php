<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('whatsapp')->nullable();
            $table->string('company_name')->default('Damirich Group');
            $table->string('company_type')->nullable(); // KONSULTAN BISNIS, FOOD N BEVERAGE, dll
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('whatsapp');
            $table->dropColumn('company_name');
            $table->dropColumn('company_type');
        });
    }
};