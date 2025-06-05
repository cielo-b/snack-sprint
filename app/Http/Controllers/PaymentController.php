<?php

namespace App\Http\Controllers;

use App\Events\PaymentStatusChanged;
use App\Models\InventoryState;
use App\Models\Machine;
use App\Models\Payment;
use App\Models\PaymentProductDelivery;
use App\Models\PaymentProducts;
use App\Models\Product;
use App\Models\SystemSetting;
use App\Services\IremboPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    private $vmPhoneNumber = "250796168005";
    private $iremboPayService;
    
    public function __construct(IremboPayService $iremboPayService)
    {
        $this->iremboPayService = $iremboPayService;
    }
    
    public function initializePayment(Request $request)
    {
        Log::info($request);

        $machineId = $request->machine;

        Machine::query()->where('id', $machineId)->update(['last_checked_in_at' => now()]);

        $phoneNumber = "25" . $request->phone_number;
        $amount = $request->amount;
        $cart = is_array($request->cart) ? json_decode(json_encode($request->cart)) : json_decode($request->cart);
        Log::info($cart);

        $payment = new Payment();
        $payment->machine_id = $machineId;
        $payment->phone_number = $phoneNumber;
        $payment->amount = $amount;
        // Create a temporary reference number
        $payment->reference_number = 'TEMP-' . time();

        $paymentProductsArray = [];
        $totalAmount = 0;
        $totalQuantity = 0;
        for ($i = 0; $i < count($cart); $i++) {

            if ($cart[$i]->quantity == 0) {
                continue;
            }

            $paymentProduct = new PaymentProducts();
            $paymentProduct->product_id = $cart[$i]->product_id;
            $paymentProduct->quantity = $cart[$i]->quantity;
            $totalQuantity += $cart[$i]->quantity;

            // remove property_exists after deploying new apk to the machine
            $product = Product::find($cart[$i]->product_id);
            $paymentProduct->unit_price = property_exists($cart[$i], 'unit_price')
                ? $cart[$i]->unit_price : $product->price;

            $paymentProductsArray[] = $paymentProduct;

            $totalAmount += $product->price * $cart[$i]->quantity;
        }

        $payment->amount = $totalAmount;

        if ($totalQuantity > 8) {
            return response()->json([
                'status' => 'FAILED',
                'reason' => 'The maximum is 8 items'
            ]);
        }
        
        // Get the payment gateway from system settings
  $paymentGateway = SystemSetting::getValue('payment_gateway', 'mopay');
//        $paymentGateway = 'irembopay';
        Log::info("Using payment gateway: " . $paymentGateway);
        
        // Process payment using selected gateway
        if ($paymentGateway === 'irembopay') {
            return $this->processWithIremboPay($payment, $paymentProductsArray, $phoneNumber);
        } else {
	try{
	 $result = $this->processWithOriginalGateway($payment, $paymentProductsArray, $phoneNumber, $totalAmount);
                
                // If Mopay fails, try IremboPay as fallback
                if ($result->getData()->status === 'FAILED') {
                    Log::info("Mopay payment failed, falling back to IremboPay");
                    return $this->processWithIremboPay($payment, $paymentProductsArray, $phoneNumber);
                }
                
                return $result;
	}catch(\Exception $e){
	Log::error("Mopay payment error: " . $e->getMessage());
                Log::info("Falling back to IremboPay due to Mopay error");
                return $this->processWithIremboPay($payment, $paymentProductsArray, $phoneNumber);
            
	}
       //     return $this->processWithOriginalGateway($payment, $paymentProductsArray, $phoneNumber, $totalAmount);
	 //   return $this->processWithIremboPay($payment, $paymentProductsArray, $phoneNumber);
        }
    }
    
    /**
     * Process payment using Irembo Pay
     */
    private function processWithIremboPay(Payment $payment, array $paymentProductsArray, string $phoneNumber)
    {
        // Save payment with initial status and temporary reference number
        $payment->status = 0; // Pending
        $payment->save();
        $payment->products()->saveMany($paymentProductsArray);
        
        // Prepare payment items for Irembo Pay
        $iremboPayItems = [];
        foreach ($paymentProductsArray as $item) {
            $product = Product::find($item->product_id);
            $iremboPayItems[] = [
                'unitAmount' => $product->price,
                'quantity' => $item->quantity,
                'code' => $product->product_code
            ];
        }
        
        // Prepare customer info
        $customerInfo = [
            'phoneNumber' => $phoneNumber,
            'name' => 'Snack Sprint Customer'
        ];
        
        // Create invoice
        Log::info("Payment controller - Creating invoice with these items:", $iremboPayItems);
        Log::info("Payment controller - Using customer info:", $customerInfo ?: []);
        Log::info("Payment controller - Base URL: {$this->iremboPayService->getBaseUrl()}");
        Log::info("Payment controller - Account ID: {$this->iremboPayService->getAccountId()}");
        
        $invoiceResult = $this->iremboPayService->createInvoice($payment, $iremboPayItems, $customerInfo);
        
        if (!$invoiceResult['success']) {
            Log::error("Payment controller - Invoice creation failed:", $invoiceResult);
            $payment->status = 4; // Failed
            $payment->response_body = json_encode($invoiceResult);
            $payment->save();
            
            return response()->json([
                'status' => 'FAILED',
                'reason' => 'Failed to create invoice: ' . ($invoiceResult['message'] ?? 'Unknown error'),
                'details' => $invoiceResult['errors'] ?? []
            ]);
        }
        $maxRetries = 3;
        $retryDelay = 2; // seconds between retries
        $paymentResult = null;
        $lastError = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            Log::info("Attempting payment initiation (Attempt {$attempt}/{$maxRetries})");

            $paymentResult = $this->iremboPayService->initiatePayment(
                $invoiceResult['invoice']['invoiceNumber'],
                $phoneNumber
            );

            if ($paymentResult['success']) {
                break; // Success, exit retry loop
            }

            // Store the last error for final response
            $lastError = $paymentResult;
            Log::warning("Payment initiation attempt {$attempt} failed: " .
                ($paymentResult['message'] ?? 'Unknown error'));

            if ($attempt < $maxRetries) {
                sleep($retryDelay); // Wait before next attempt
            }
        }
        // Invoice created successfully, now initiate payment
       // $paymentResult = $this->iremboPayService->initiatePayment($invoiceResult['invoice']['invoiceNumber'], $phoneNumber);
        
        if (!$paymentResult['success']) {
            // Default to insufficient balance message for 500 errors
            $errorMessage = 'Make sure you have enough funds and try again.';
            $status = 3; // Using the insufficient balance status code
            
            // Check for specific error codes
            if (isset($paymentResult['errors']) && is_array($paymentResult['errors'])) {
                foreach ($paymentResult['errors'] as $error) {
                    Log::info("Payment error code: " . ($error['code'] ?? 'none') . ", status: " . ($error['status'] ?? 'none'));
                    
                    // Handle specific error codes
                    if (isset($error['code'])) {
                        switch ($error['code']) {
                            case 'INVOICE_NOT_FOUND':
                                $errorMessage = 'If you keep having issues, contact support!';
                                $status = 4; // General failure
                                break;
                            // Add other specific error codes here as needed
                        }
                    }
                    
                    // Also handle based on HTTP status
                    if (isset($error['status'])) {
                        // Only for non-500 errors (500 is likely insufficient balance)
                        if ($error['status'] != 500) {
                            // If we haven't set a specific error message above, use a generic one
                            if ($status == 3) { // Only change if we haven't already set it above
                                $errorMessage = 'If you keep having issues, contact support!';
                                $status = 4; // General failure
                            }
                        }
                    }
                }
            }
            
            $payment->status = $status;
            $payment->response_body = json_encode($paymentResult);
            $payment->save();
            
            return response()->json([
                'status' => 'FAILED',
                'reason' => $errorMessage
            ]);
        }
        
        // Update payment with transaction info
        $payment->transaction_reference = $paymentResult['transactionReference'];
        $payment->payment_provider = $paymentResult['paymentProvider'];
        $payment->reference_number = $invoiceResult['invoice']['invoiceNumber']; // Use invoice number as reference
        $payment->invoice_number = $invoiceResult['invoice']['invoiceNumber'];
        $payment->response_body = json_encode($paymentResult);
        $payment->save();
        
        return response()->json([
            'status' => 'PENDING',
            'reference_number' => $payment->reference_number,
        ]);
    }
    
    /**
     * Process payment using the original payment gateway
     */
    private function processWithOriginalGateway(Payment $payment, array $paymentProductsArray, string $phoneNumber, float $totalAmount)
    {
        $body = [
            "amount" => $totalAmount,
            "currency" => "RWF",
            "phone" => $phoneNumber,
            "payment_mode" => "MOBILE",
            "message" => "On behalf of Snack Sprint",
            "callback_url" => "http://185.216.203.124/api/payment-callback",
            "transfers" => [
                [
                    "amount" => $totalAmount,
                    "phone" => $this->vmPhoneNumber,
                    "message" => "Snack Sprint"
                ]
            ]
        ];

        //$yourAccessTokenHere = "eyJhbGciOiJSUzI1NiIsInR5cCIgOiAiSldUIiwia2lkIiA6ICJScktMd0ppRm50aXNGT1Y4clZ5dmNuNmoyamY4WUdxaTlNS0Q3MTl2VTNrIn0.eyJleHAiOjE3NDg5Njk0MDEsImlhdCI6MTc0NjM3NzQwMSwianRpIjoiNDk4OWE2ZjQtN2Y5Yi00NWFmLTk5MjItODg4OWI4Zjg3YjljIiwiaXNzIjoiaHR0cHM6Ly9rZXljbG9hay5tb3BheS5ydy9yZWFsbXMvbW9wYXkiLCJhdWQiOiJhY2NvdW50Iiwic3ViIjoiMjkyMTAwMjItNTc3Yy00NjE4LWE0M2QtMzg0ODRlMDQ2M2JiIiwidHlwIjoiQmVhcmVyIiwiYXpwIjoiYXBpLWdhdGV3YXkiLCJzZXNzaW9uX3N0YXRlIjoiOTVkMzRiMTctNzc5MS00NTVjLWI4MTMtODA4MDRmMTkzZDU0IiwiYWNyIjoiMSIsImFsbG93ZWQtb3JpZ2lucyI6WyIvKiJdLCJyZWFsbV9hY2Nlc3MiOnsicm9sZXMiOlsib2ZmbGluZV9hY2Nlc3MiLCJ1bWFfYXV0aG9yaXphdGlvbiIsImRlZmF1bHQtcm9sZXMtbW9wYXkiXX0sInJlc291cmNlX2FjY2VzcyI6eyJhY2NvdW50Ijp7InJvbGVzIjpbIm1hbmFnZS1hY2NvdW50IiwibWFuYWdlLWFjY291bnQtbGlua3MiLCJ2aWV3LXByb2ZpbGUiXX19LCJzY29wZSI6Im9wZW5pZCBvZmZsaW5lX2FjY2VzcyBwcm9maWxlIGVtYWlsIiwic2lkIjoiOTVkMzRiMTctNzc5MS00NTVjLWI4MTMtODA4MDRmMTkzZDU0IiwiZW1haWxfdmVyaWZpZWQiOmZhbHNlLCJuYW1lIjoiU25hY2tzcHJpbnQgU25hY2tzcHJpbnQiLCJwcmVmZXJyZWRfdXNlcm5hbWUiOiJzbmFja3NwcmludCIsImdpdmVuX25hbWUiOiJTbmFja3NwcmludCIsImZhbWlseV9uYW1lIjoiU25hY2tzcHJpbnQiLCJlbWFpbCI6InNuYWNrc3ByaW50QHNuYWNrc3ByaW50LmNvbSJ9.sy_BGe0soaTG73cUKILzObEUddjJuoCYhpG8W4rvqOMc4tt7DWAX9JEdXnajll3hvy89Nv1xSaEADLCldKvNdS4eSgMHDV3KEPPXiyIQlULketVqBE5FhgzId7cVI6sO-ndtJ20HnYngBhoB0vasuU55I4RnXWhjiqY7gfUKZ6IUWdtdiQ9WIVFLP3PCybQCOiUmmhs-sXIgOaRgBFw7Pj8ukoREhNqPClbLYmqELf7dbuNSvhSKcKkgrnvBYbAiEK7UMAgKiBbhkd_iY-xGGlXnPwi7Yqjssx0eWmY8d2P4M7DRIoEn5sZr3M4uzV8P28KWp87Ew1Rn-P3xzNfQrw";
	$yourAccessTokenHere = "eyJhbGciOiJSUzI1NiIsInR5cCIgOiAiSldUIiwia2lkIiA6ICJScktMd0ppRm50aXNGT1Y4clZ5dmNuNmoyamY4WUdxaTlNS0Q3MTl2VTNrIn0.eyJleHAiOjE3NTE0MDMxODksImlhdCI6MTc0ODgxMTE4OSwianRpIjoiYjEzNzMyNjMtMTc2NC00ODQxLWEwYTYtYmEzYTAwMWNkODUyIiwiaXNzIjoiaHR0cHM6Ly9rZXljbG9hay5tb3BheS5ydy9yZWFsbXMvbW9wYXkiLCJhdWQiOiJhY2NvdW50Iiwic3ViIjoiMjkyMTAwMjItNTc3Yy00NjE4LWE0M2QtMzg0ODRlMDQ2M2JiIiwidHlwIjoiQmVhcmVyIiwiYXpwIjoiYXBpLWdhdGV3YXkiLCJzZXNzaW9uX3N0YXRlIjoiYTZlYzEzZTAtMTY3Yi00NWNkLWIxZWUtNTcxM2Q3MDYyMjRhIiwiYWNyIjoiMSIsImFsbG93ZWQtb3JpZ2lucyI6WyIvKiJdLCJyZWFsbV9hY2Nlc3MiOnsicm9sZXMiOlsib2ZmbGluZV9hY2Nlc3MiLCJ1bWFfYXV0aG9yaXphdGlvbiIsImRlZmF1bHQtcm9sZXMtbW9wYXkiXX0sInJlc291cmNlX2FjY2VzcyI6eyJhY2NvdW50Ijp7InJvbGVzIjpbIm1hbmFnZS1hY2NvdW50IiwibWFuYWdlLWFjY291bnQtbGlua3MiLCJ2aWV3LXByb2ZpbGUiXX19LCJzY29wZSI6Im9wZW5pZCBvZmZsaW5lX2FjY2VzcyBwcm9maWxlIGVtYWlsIiwic2lkIjoiYTZlYzEzZTAtMTY3Yi00NWNkLWIxZWUtNTcxM2Q3MDYyMjRhIiwiZW1haWxfdmVyaWZpZWQiOmZhbHNlLCJuYW1lIjoiU25hY2tzcHJpbnQgU25hY2tzcHJpbnQiLCJwcmVmZXJyZWRfdXNlcm5hbWUiOiJzbmFja3NwcmludCIsImdpdmVuX25hbWUiOiJTbmFja3NwcmludCIsImZhbWlseV9uYW1lIjoiU25hY2tzcHJpbnQiLCJlbWFpbCI6InNuYWNrc3ByaW50QHNuYWNrc3ByaW50LmNvbSJ9.GLigRt5Q-t3b0nm_L7U45jeodTwZToUl94LfWA94336Num7iQZh5YSWKSpWPE05FGg439VhH6HUiE3CwzxwC_aofbHWikIV5GmW0eQkpyj0GELdgBsNwmHB_uBJ73oX9YTzV1DUrI4Goxo3aVAJg-l4rjn0BCyFTwjRA7ml9MmLZ8xyyOHMIroXBZl9bMAYig2x6nx1_O4E8j_Yftw8xWgl-0ObeSCoZFgG55i_lK2Bm_AN6amOadl6M4WEHiL0X_Vsti4Sc35tN31DlJsaXdURb7-BjklpS7SwM35ANFQt89RPCHziQQr-CJCBqrX-5nTfGbfVkYbxAjLGvSvtQOA";
        $headers = [
            "Content-Type" => 'application/json',
            "Accept" => 'application/json',
            "Authorization" => 'Bearer ' . $yourAccessTokenHere
        ];

        $url = "https://api.mopay.rw/initiate-payment";

        Log::info("Payment URL : " . $url);
        Log::info("Payment request : " . json_encode($body));

        $response = Http::withHeaders($headers)->acceptJson()->asJson()->post($url, $body);

        Log::info("Payment response status : " . $response->status());
        Log::info("Payment response : " . $response->body());

        if ($response->status() == 201) {
            $responseArr = $response->json();
            if (array_key_exists('status', $responseArr)) {
                if ($responseArr["status"] == 201) {

                    $reference = $responseArr["transactionId"];

                    $payment->reference_number = $reference;
                    $payment->status = 0;
                    $payment->save();
                    $payment->products()->saveMany($paymentProductsArray);

                    return response()->json([
                        'status' => 'PENDING',
                        'reference_number' => $reference,
                    ]);
                }
            }
        } elseif ($response->status() == 500) {
            $responseArr = $response->json();
            if (array_key_exists('message', $responseArr)) {
                if ($responseArr['message'] == 'TARGET_AUTHORIZATION_ERROR') {

                    $payment->status = 3;
                    $payment->save();
                    $payment->products()->saveMany($paymentProductsArray);

                    return response()->json([
                        'status' => 'FAILED',
                        'reason' => 'INSUFFICIENT_BALANCE'
                    ]);
                }

                if ($responseArr['message'] == 'AUTHORIZATION_SENDER_ACCOUNT_NOT_ACTIVE') {

                    $payment->status = 5;
                    $payment->save();
                    $payment->products()->saveMany($paymentProductsArray);

                    return response()->json([
                        'status' => 'FAILED',
                        'reason' => 'Phone Number Issue, Try Another Phone Number'
                    ]);
                }

                if ($responseArr['message'] == 'RESOURCE_NOT_FOUND' || $responseArr['message'] == 'ACCOUNTHOLDER_WITH_FRI_NOT_FOUND') {

                    $payment->status = 6;
                    $payment->save();
                    $payment->products()->saveMany($paymentProductsArray);

                    return response()->json([
                        'status' => 'FAILED',
                        'reason' => 'Dear Customer , your Phone number is not registered for mobile money payments  Mukiliya mwiza , Telephone yanyu ntabwo yandikishije kuri mobile money . Murakoze'
                    ]);
                }
            }
        }

        $payment->status = 4;
        $payment->response_body = $response->body();
        $payment->save();
        $payment->products()->saveMany($paymentProductsArray);

        return response()->json([
            'status' => 'FAILED',
            'reason' => 'Service is temporarily unavailable. Please return shortly.'
        ]);
    }

    public function callback(Request $request)
    {
        Log::info("Callback request : ");
        Log::info($request);

        // Check if it's an Irembo Pay callback or the original payment gateway callback
        if ($request->header('irembopay-signature')) {
            return $this->handleIremboPayCallback($request);
        } else {
            return $this->handleOriginalCallback($request);
        }
    }
    
    /**
     * Handle callback from Irembo Pay
     */
    private function handleIremboPayCallback(Request $request)
    {
        Log::info("Irembo Pay callback received");
        
        // Verify the callback signature
        if (!$this->iremboPayService->verifyCallbackSignature($request)) {
            Log::error("Irembo Pay callback - Invalid signature");
            return response()->json(['message' => 'Invalid signature', 'status' => 400], 400);
        }
        
        // Store the signature header for future reference
        $signatureHeader = $request->header('irembopay-signature');
        
        // Parse the callback data
        $callbackData = $request->json()->all();
        Log::info("Irembo Pay callback data: " . json_encode($callbackData));
        
        // Check for invoice expired error
        if (isset($callbackData['errors']) && is_array($callbackData['errors'])) {
            foreach ($callbackData['errors'] as $error) {
                if (isset($error['code']) && $error['code'] === 'INVOICE_EXPIRED') {
                    Log::info("Irembo Pay - Invoice expired notification received");
                    
                    // Find payments in pending state that match the criteria
                    $pendingPayments = Payment::where('status', 0)
                        ->whereNotNull('invoice_number')
                        ->get();
                    
                    foreach ($pendingPayments as $pendingPayment) {
                        // Mark the payment as failed
                        $pendingPayment->status = 2; // Failed
                        $pendingPayment->callback_at = now();
                        $pendingPayment->save();
                        
                        // Dispatch event to notify the system about the failed payment
                        PaymentStatusChanged::dispatch($pendingPayment->reference_number, 'FAILED');
                        
                        Log::info("Payment " . $pendingPayment->reference_number . " marked as expired due to INVOICE_EXPIRED notification");
                    }
                    
                    return response()->json(["message" => "success", "status"=> 200]);
                }
            }
        }
        
        if (empty($callbackData) || !isset($callbackData['data']) || !isset($callbackData['data']['invoiceNumber'])) {
            Log::error("Irembo Pay callback - Invalid data format");
            return response()->json(['message' => 'Invalid data format', 'status' => 400], 400);
        }
        
        $invoiceNumber = $callbackData['data']['invoiceNumber'];
        $paymentStatus = $callbackData['data']['paymentStatus'] ?? null;
        
        // Find the payment by invoice number
        $payment = Payment::where('invoice_number', $invoiceNumber)->first();
        
        if (!$payment) {
            Log::error("Irembo Pay callback - Payment not found for invoice number: " . $invoiceNumber);
            return response()->json(["message" => "Payment not found", "status"=> 404], 404);
        }
        
        // Store the signature header
        $payment->signature_header = $signatureHeader;
        
        // Update payment status based on paymentStatus from Irembo Pay
        if ($paymentStatus === 'PAID') {
            if ($payment->status == 0) { // Only update if still pending
                $payment->status = 1; // Successful
                $payment->callback_at = now();
                
                // Dispatch event to notify the system about the successful payment
                PaymentStatusChanged::dispatch($payment->reference_number, 'SUCCESSFUL');
            }
        } else if ($paymentStatus === 'FAILED' || $paymentStatus === 'EXPIRED') {
            if ($payment->status == 0) { // Only update if still pending
                // For expired payments, mark as status 2 (Failed)
                $payment->status = 2; // Failed
                $payment->callback_at = now();
                
                // Dispatch event to notify the system about the failed payment
                PaymentStatusChanged::dispatch($payment->reference_number, 'FAILED');
            }
        }
        
        // Save the payment
        $payment->save();
        
        return response()->json(["message" => "success", "status"=> 200]);
    }
    
    /**
     * Handle callback from the original payment gateway
     */
    private function handleOriginalCallback(Request $request)
    {
        $referenceNumber = $request->transactionId;
        $status = $request->status;

        $payment = Payment::where('reference_number', $referenceNumber)->first();

        if (!$payment) {
            Log::error("Payment not found for reference number : " . $referenceNumber);
            return response()->json(["message" => "Payment not found for reference number", "status"=> 404]);
        }

        if ($payment->status != 0) {
            Log::error("Payment is not still pending : " . $payment->status);
            return response()->json(["message" => "Payment is not still pending", "status"=> 404]);
        }

        if ($status == 200) {
            PaymentStatusChanged::dispatch($referenceNumber, 'SUCCESSFUL');
        } else {
            PaymentStatusChanged::dispatch($referenceNumber, 'FAILED');

            // check if payment created_at is less than 2 minutes
            if (now()->diffInMinutes($payment->created_at) < 2) {
                $payment->status = 3; // insufficient balance
                $payment->save();
            }
        }

        $payment->status = $status == 200 ? 1 : 2;
        $payment->callback_at = now();
        $payment->save();

        return response()->json(["message" => "success", "status"=> 200]);
    }


    public function deliveryReport(Request $request)
    {
        Log::debug($request);

        $reference = $request->reference_number;
        $cancelled = $request->cancelled;
        $deliveryStatusCodes = $request->delivery_status_codes;

        $machine = Machine::find($request->machine);

        $payment = Payment::query()->firstWhere('reference_number', $reference);

        if ($payment == null)
            return response()->json(["message" => "Payment reference not found", "status"=> 404]);

        if ($cancelled === true) {
            $payment->update([
                'cancelled_at' => now(),
            ]);
        }

        $laneTracking = [];

        foreach ($deliveryStatusCodes as $statusCode) {
            $laneId = $statusCode['laneId'];

            if (!isset($laneTracking[$laneId])) {

                $inventory = InventoryState::query()
                    ->where('machine_id', $machine->id)
                    ->where('lane_id', $laneId)
                    ->first();

                if ($inventory) {
                    $laneTracking[$laneId] = [
                        'quantity' => $inventory->quantity,
                        'product_id' => $inventory->product_id,
                    ];
                } else {
                    Log::error("Inventory not found for lane ID: " . $laneId);
                    continue;
                }
            }


            $laneTracking[$laneId]['quantity'] -= 1;

            $productId = $laneTracking[$laneId]['product_id'];

            $paymentProduct = $payment->products()->firstWhere('product_id', $productId);

            if ($paymentProduct == null) {
                Log::error("Payment product id " . $productId . " not found in payment: " . $payment->id);
                continue;
            }

            PaymentProductDelivery::query()->create([
                'payment_product_id' => $paymentProduct->id,
                'lane_id' => $laneId,
                'lane_quantity' => $laneTracking[$laneId]['quantity'],
                'status_code' => $statusCode['statusCode'],
                // 'state' => $statusCode['state'],
                // 'embody_status' => $statusCode['embodyStatus']
            ]);
        }

        return response()->json(["message" => "success", "status"=> 200]);
    }

    private function generateReference()
    {
        return "VM" . time();
    }
}
