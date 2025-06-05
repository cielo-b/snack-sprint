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
        Schema::create('payment_products_delivery_status', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_product_id');
            $table->string('lane_id');
            $table->string('lane_quantity');
            $table->string('status_code');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_products_delivery_status');
    }
};
