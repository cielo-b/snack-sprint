<?php

namespace App\Tasks;

use App\Events\PaymentStatusChanged;
use App\Models\Payment;
use App\Services\IremboPayService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckPendingPaymentStatus
{
    private $iremboPayService;
    
    public function __construct(IremboPayService $iremboPayService = null)
    {
        $this->iremboPayService = $iremboPayService ?? app(IremboPayService::class);
    }
    
    public function __invoke()
    {
        // Get all pending payments
        $pendingPayments = Payment::where('status', 0)->get();

        // Check system setting for payment gateway
        $paymentGateway = app('App\Models\SystemSetting')::getValue('payment_gateway', 'mopay');
        $useIremboPay = $paymentGateway === 'irembopay';
        
        Log::info('Checking Pending Payments: ' . $pendingPayments->count() . ' (Using payment gateway: ' . $paymentGateway . ')');

        foreach ($pendingPayments as $payment) {
            // Determine which payment method to check based on system settings and invoice_number
            $isIremboPay = $payment->invoice_number !== null && $payment->invoice_number !== '' && $useIremboPay;
            
            if ($isIremboPay) {
                $this->checkIremboPayStatus($payment);
                Log::info('Checked IremboPay payment: ' . $payment->invoice_number);
            } else {
                $this->checkOriginalPaymentStatus($payment);
                Log::info('Checked Original payment: ' . $payment->reference_number);
            }
        }
    }
    
    /**
     * Check the status of an Irembo Pay payment
     */
    private function checkIremboPayStatus(Payment $payment)
    {
        try {
            Log::info("Starting IremboPay status check for payment ID: " . $payment->id . 
                     " (Invoice: " . $payment->invoice_number . ", Reference: " . $payment->reference_number . ")");
            
            // Log raw timestamps for debugging timezone issues
            Log::info("Raw timestamps - Created at: " . $payment->created_at->toDateTimeString() . 
                     ", Now: " . now()->toDateTimeString() . 
                     ", App timezone: " . config('app.timezone'));
            
            // Get payment expiry time from system settings (default: 5 minutes)
            $expiryMinutes = app('App\Models\SystemSetting')::getValue('payment_expiry', 5);
            Log::info("Retrieved payment_expiry setting: " . $expiryMinutes . " minutes");
            
            // Add a small buffer to allow for callback processing (additional 2 minutes)
            $expiryMinutes += 2;
            Log::info("Total expiry time (with buffer): " . $expiryMinutes . " minutes");
            
            // Check how old the payment is - use abs() to ensure we get a positive value
            $paymentAgeMinutes = abs(now()->diffInMinutes($payment->created_at));
            Log::info("Payment age: " . $paymentAgeMinutes . " minutes (Created at: " . $payment->created_at . ", Now: " . now() . ")");
            
            // Check IremboPay API for status if needed (using $this->iremboPayService)
            // Future implementation possibility
            
            // If payment is older than the configured expiry time and still pending, mark it as failed
            if ($paymentAgeMinutes > $expiryMinutes) {
                Log::info("Marking expired Irembo Pay payment as failed: " . $payment->invoice_number . 
                         " (Expired after " . $expiryMinutes . " minutes)");
                $payment->status = 2; // Failed
                
                $responseData = [
                    'status' => 'FAILED',
                    'reason' => 'Payment expired after ' . $expiryMinutes . ' minutes',
                    'expiry_time' => $expiryMinutes,
                    'age_minutes' => $paymentAgeMinutes,
                    'processed_at' => now()->toIso8601String()
                ];
                $payment->response_body = json_encode($responseData);
                
                Log::info("Setting response_body: " . json_encode($responseData, JSON_PRETTY_PRINT));
                
                $payment->callback_at = now();
                $result = $payment->save();
                
                Log::info("Payment save result: " . ($result ? 'Success' : 'Failed'));
                
                // Dispatch event to notify the system
                PaymentStatusChanged::dispatch($payment->reference_number, 'FAILED');
                Log::info("Dispatched PaymentStatusChanged event for reference: " . $payment->reference_number);
            } else {
                Log::info("Payment is still within expiry window (" . $paymentAgeMinutes . "/" . $expiryMinutes . " minutes) - no action taken");
            }
        } catch (\Exception $e) {
            Log::error("Error checking Irembo Pay status: " . $e->getMessage());
            Log::error("Exception trace: " . $e->getTraceAsString());
        }
    }
    
    /**
     * Check the status using the original payment gateway
     */
    private function checkOriginalPaymentStatus(Payment $payment)
    {
        $url = "https://api.mopay.rw/check-status/";

//        $yourAccessTokenHere = "eyJhbGciOiJSUzI1NiIsInR5cCIgOiAiSldUIiwia2lkIiA6ICJScktMd0ppRm50aXNGT1Y4clZ5dmNuNmoyamY4WUdxaTlNS0Q3MTl2VTNrIn0.eyJleHAiOjE3NDg5Njk0MDEsImlhdCI6MTc0NjM3NzQwMSwianRpIjoiNDk4OWE2ZjQtN2Y5Yi00NWFmLTk5MjItODg4OWI4Zjg3YjljIiwiaXNzIjoiaHR0cHM6Ly9rZXljbG9hay5tb3BheS5ydy9yZWFsbXMvbW9wYXkiLCJhdWQiOiJhY2NvdW50Iiwic3ViIjoiMjkyMTAwMjItNTc3Yy00NjE4LWE0M2QtMzg0ODRlMDQ2M2JiIiwidHlwIjoiQmVhcmVyIiwiYXpwIjoiYXBpLWdhdGV3YXkiLCJzZXNzaW9uX3N0YXRlIjoiOTVkMzRiMTctNzc5MS00NTVjLWI4MTMtODA4MDRmMTkzZDU0IiwiYWNyIjoiMSIsImFsbG93ZWQtb3JpZ2lucyI6WyIvKiJdLCJyZWFsbV9hY2Nlc3MiOnsicm9sZXMiOlsib2ZmbGluZV9hY2Nlc3MiLCJ1bWFfYXV0aG9yaXphdGlvbiIsImRlZmF1bHQtcm9sZXMtbW9wYXkiXX0sInJlc291cmNlX2FjY2VzcyI6eyJhY2NvdW50Ijp7InJvbGVzIjpbIm1hbmFnZS1hY2NvdW50IiwibWFuYWdlLWFjY291bnQtbGlua3MiLCJ2aWV3LXByb2ZpbGUiXX19LCJzY29wZSI6Im9wZW5pZCBvZmZsaW5lX2FjY2VzcyBwcm9maWxlIGVtYWlsIiwic2lkIjoiOTVkMzRiMTctNzc5MS00NTVjLWI4MTMtODA4MDRmMTkzZDU0IiwiZW1haWxfdmVyaWZpZWQiOmZhbHNlLCJuYW1lIjoiU25hY2tzcHJpbnQgU25hY2tzcHJpbnQiLCJwcmVmZXJyZWRfdXNlcm5hbWUiOiJzbmFja3NwcmludCIsImdpdmVuX25hbWUiOiJTbmFja3NwcmludCIsImZhbWlseV9uYW1lIjoiU25hY2tzcHJpbnQiLCJlbWFpbCI6InNuYWNrc3ByaW50QHNuYWNrc3ByaW50LmNvbSJ9.sy_BGe0soaTG73cUKILzObEUddjJuoCYhpG8W4rvqOMc4tt7DWAX9JEdXnajll3hvy89Nv1xSaEADLCldKvNdS4eSgMHDV3KEPPXiyIQlULketVqBE5FhgzId7cVI6sO-ndtJ20HnYngBhoB0vasuU55I4RnXWhjiqY7gfUKZ6IUWdtdiQ9WIVFLP3PCybQCOiUmmhs-sXIgOaRgBFw7Pj8ukoREhNqPClbLYmqELf7dbuNSvhSKcKkgrnvBYbAiEK7UMAgKiBbhkd_iY-xGGlXnPwi7Yqjssx0eWmY8d2P4M7DRIoEn5sZr3M4uzV8P28KWp87Ew1Rn-P3xzNfQrw";
	$yourAccessTokenHere = "eyJhbGciOiJSUzI1NiIsInR5cCIgOiAiSldUIiwia2lkIiA6ICJScktMd0ppRm50aXNGT1Y4clZ5dmNuNmoyamY4WUdxaTlNS0Q3MTl2VTNrIn0.eyJleHAiOjE3NTEyMjgyOTcsImlhdCI6MTc0ODYzNjI5NywianRpIjoiZWU2NzA3OWMtNTgzOS00NGRkLThhYzctNWY2ZjQ3NmU3OGYxIiwiaXNzIjoiaHR0cHM6Ly9rZXljbG9hay5tb3BheS5ydy9yZWFsbXMvbW9wYXkiLCJhdWQiOiJhY2NvdW50Iiwic3ViIjoiMjkyMTAwMjItNTc3Yy00NjE4LWE0M2QtMzg0ODRlMDQ2M2JiIiwidHlwIjoiQmVhcmVyIiwiYXpwIjoiYXBpLWdhdGV3YXkiLCJzZXNzaW9uX3N0YXRlIjoiNmQwN2QwNWEtYjJjOS00NjU0LTk2YmQtMjZlNjA2MzA0OGJiIiwiYWNyIjoiMSIsImFsbG93ZWQtb3JpZ2lucyI6WyIvKiJdLCJyZWFsbV9hY2Nlc3MiOnsicm9sZXMiOlsib2ZmbGluZV9hY2Nlc3MiLCJ1bWFfYXV0aG9yaXphdGlvbiIsImRlZmF1bHQtcm9sZXMtbW9wYXkiXX0sInJlc291cmNlX2FjY2VzcyI6eyJhY2NvdW50Ijp7InJvbGVzIjpbIm1hbmFnZS1hY2NvdW50IiwibWFuYWdlLWFjY291bnQtbGlua3MiLCJ2aWV3LXByb2ZpbGUiXX19LCJzY29wZSI6Im9wZW5pZCBvZmZsaW5lX2FjY2VzcyBwcm9maWxlIGVtYWlsIiwic2lkIjoiNmQwN2QwNWEtYjJjOS00NjU0LTk2YmQtMjZlNjA2MzA0OGJiIiwiZW1haWxfdmVyaWZpZWQiOmZhbHNlLCJuYW1lIjoiU25hY2tzcHJpbnQgU25hY2tzcHJpbnQiLCJwcmVmZXJyZWRfdXNlcm5hbWUiOiJzbmFja3NwcmludCIsImdpdmVuX25hbWUiOiJTbmFja3NwcmludCIsImZhbWlseV9uYW1lIjoiU25hY2tzcHJpbnQiLCJlbWFpbCI6InNuYWNrc3ByaW50QHNuYWNrc3ByaW50LmNvbSJ9.tLjgEHVQhYmAUKcl226xFabZptWRkQ57RkP-s9P5Zsb9zea5cIeywQDYxHQnHyrDyzOg8zRvxxDtFfFFKqnQAkkD9SW2GI98knboSi2vj4egatHSWe_9pD0xCpH0vbWvIRSWQrF2FoDyFdTjAT19Le_ZeXOqntmMYjcWQMJLAJUZe4qxUZQ_rmuCPso7IgB-EwEg18lAtw-ZJnq0gQ4C4bkaXZV6ZBjbcMbBYaQLcKorBkr7v03RcgV9E1BrmGWEj8af17KADDOMiIkommzQ8Ua8dNVosQ6LtrYoiX765fcVRH--VKOopY1djlXiVZmDnYN2fJUxG8eMYuwzY394OA";
        $headers = [
            "Content-Type" => 'application/json',
            "Accept" => 'application/json',
            "Authorization" => 'Bearer ' . $yourAccessTokenHere
        ];

        $paymentUrl = $url . $payment->reference_number;

        Log::info("Checking payment status for: " . $payment->reference_number);
        Log::info("URL: " . $paymentUrl);
        
        try {
            $response = Http::withHeaders($headers)->connectTimeout(15)->timeout(15)->get($paymentUrl);

            Log::info("Response status: " . $response->status());
            Log::info("Response: " . $response->body());

            if ($response->status() == 200) {
                $responseArr = $response->json();
                
                // Save the response body for viewing in the UI
                $payment->response_body = $response->body();
                
                if (array_key_exists('status', $responseArr)) {
                    if ($responseArr["status"] == 200) {
                        PaymentStatusChanged::dispatch($payment->reference_number, 'SUCCESSFUL');
                        $payment->status = 1;
                        $payment->callback_at = now();
                        $payment->save();
                    } elseif ($responseArr["status"] == 500) {
                        $payment->status = 2;
                        $payment->callback_at = now();
                        $payment->save();
                    } else {
                        // For status 201 or other statuses, update the response body but don't change status
                        if ($responseArr["status"] != 201) {
                            Log::error("Unknown status: " . $responseArr["status"]);
                        }
                        $payment->save();
                    }
                } else {
                    Log::error("No status key in response");
                    $payment->save(); // Still save the response
                }
            } else {
                // Even for non-200 responses, save the response body
                $payment->response_body = $response->body();
                $payment->save();
            }
        } catch (\Exception $e) {
            Log::error("Error: " . $e->getMessage());
        }
    }
}
