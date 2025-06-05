<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payment_products_delivery_status', function (Blueprint $table) {
            $table->string('state')->nullable()->after('status_code');
            $table->string('embody_status')->nullable()->after('state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_products_delivery_status', function (Blueprint $table) {
            //
        });
    }
};
