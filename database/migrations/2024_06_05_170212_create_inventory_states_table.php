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
        Schema::create('inventory_state', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('machine_id');
            $table->double('lane_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedInteger('max_quantity');
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_state');
    }
};
