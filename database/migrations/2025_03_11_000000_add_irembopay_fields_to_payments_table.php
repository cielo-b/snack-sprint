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
        Schema::table('payments', function (Blueprint $table) {
            // Add fields needed for Irembo Pay
            $table->string('invoice_number')->nullable()->after('reference_number');
            $table->string('payment_provider')->nullable()->after('invoice_number');
            $table->string('transaction_reference')->nullable()->after('payment_provider');
            $table->timestamp('expiry_at')->nullable()->after('transaction_reference');
            $table->text('invoice_response')->nullable()->after('response_body');
            $table->text('signature_header')->nullable()->after('invoice_response');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_number',
                'payment_provider',
                'transaction_reference',
                'expiry_at',
                'invoice_response',
                'signature_header'
            ]);
        });
    }
};
