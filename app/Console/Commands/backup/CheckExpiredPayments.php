<?php

namespace App\Console\Commands;

use App\Events\PaymentStatusChanged;
use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiredPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:check-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for and mark expired payments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for expired payments...');
        
        // Get system settings for expiry time
        $expiryMinutes = \App\Models\SystemSetting::getValue('payment_expiry', 5);
        
        // Find pending payments that are older than the expiry time
        $expiredPayments = Payment::where('status', 0) // Pending
            ->where('created_at', '<', now()->subMinutes($expiryMinutes))
            ->whereNotNull('invoice_number') // Only IremboPay payments have invoice numbers
            ->get();
        
        $count = $expiredPayments->count();
        $this->info("Found {$count} expired payments");
        
        foreach ($expiredPayments as $payment) {
            $this->info("Marking payment {$payment->reference_number} as expired");
            
            // Mark the payment as failed
            $payment->status = 2; // Failed
            $payment->callback_at = now();
            $payment->save();
            
            // Notify the system
            PaymentStatusChanged::dispatch($payment->reference_number, 'FAILED');
            
            Log::info("Payment {$payment->reference_number} marked as expired automatically");
        }
        
        $this->info('Done checking expired payments');
        
        return 0;
    }
}
