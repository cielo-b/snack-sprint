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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Insert default values
        DB::table('system_settings')->insert([
            ['key' => 'payment_gateway', 'value' => 'mopay', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'payment_expiry', 'value' => '5', 'created_at' => now(), 'updated_at' => now()], // 5 minutes default
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
