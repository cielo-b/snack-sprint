<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\SystemSetting;
use Exception;

class IremboPayService
{
    private $baseUrl;
    private $secretKey;
    private $paymentAccountIdentifier;
    
    public function __construct()
    {
        $this->baseUrl = env('IREMBOPAY_BASE_URL', 'https://api.sandbox.irembopay.com');
        $this->secretKey = env('IREMBOPAY_SECRET_KEY', 'sk_live_9d7c7633dd2e40f0a978ca4ae065fc7d');
        $this->paymentAccountIdentifier = env('IREMBOPAY_ACCOUNT_ID', 'SNACK-RWF');
    }
    
    /**
     * Create an invoice with Irembo Pay
     *
     * @param Payment $payment
     * @param array $paymentItems
     * @param array $customerInfo
     * @return array
     */
    public function createInvoice(Payment $payment, array $paymentItems, array $customerInfo = null): array
    {
        try {
            // Generate a transaction ID/reference number to use
            $transactionId = 'TST-' . rand(100000, 999999);
            
            // Set the reference_number in the payment object
            $payment->reference_number = $transactionId;
            
            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'irembopay-secretKey' => $this->secretKey
            ];
            
            // Get payment expiry time from system settings (default: 5 minutes)
            $expiryMinutes = SystemSetting::getValue('payment_expiry', 5);
            
            $expiryDate = new \DateTime();
            $expiryDate->modify('+' . $expiryMinutes . ' minutes');
            $formattedExpiryDate = str_replace('Z', '+02:00', $expiryDate->format('c'));
            
            Log::info("Irembo Pay - Setting invoice expiry for " . $expiryMinutes . " minutes");
            
            $payload = [
                'transactionId' => $transactionId,
                'paymentAccountIdentifier' => $this->paymentAccountIdentifier,
                'paymentItems' => $paymentItems,
                'description' => 'Payment for products from Snack Sprint',
                'expiryAt' => $formattedExpiryDate,
                'language' => 'EN'
            ];
            
            // Add customer info if provided
            if ($customerInfo) {
                $payload['customer'] = $customerInfo;
            }
            
            // Log the complete payload
            Log::info("Irembo Pay - Full invoice payload: " . json_encode($payload, JSON_PRETTY_PRINT));
            
            $response = Http::withHeaders($headers)
                            ->post($this->baseUrl . '/payments/invoices', $payload);
            
            Log::info("Irembo Pay - Complete invoice response: " . $response->body());
            
            if ($response->successful() && $response->json('success') === true) {
                $invoiceData = $response->json('data');
                
                // Update payment with invoice information
                $payment->transaction_reference = $transactionId;
                $payment->invoice_number = $invoiceData['invoiceNumber'];
                $payment->invoice_response = $response->body();
                $payment->expiry_at = now()->addHours(2);
                $payment->save();
                
                return [
                    'success' => true,
                    'invoice' => $invoiceData
                ];
            }
            
            return [
                'success' => false,
                'message' => $response->json('message', 'Invoice creation failed'),
                'errors' => $response->json('errors', [])
            ];
        } catch (Exception $e) {
            Log::error("Irembo Pay - Invoice creation error: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'An error occurred while creating the invoice',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Initiate a payment for an invoice
     *
     * @param string $invoiceNumber
     * @param string $phoneNumber
     * @return array
     */
    public function initiatePayment(string $invoiceNumber, string $phoneNumber): array
    {
        try {
            // Determine the payment provider
            $paymentProvider = $this->determinePaymentProvider($phoneNumber);
            
            $transactionReference = 'TXN-' . rand(100000, 999999);
            
            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'SnackSprintApp',
                'irembopay-secretKey' => $this->secretKey
            ];
            
            $payload = [
                'accountIdentifier' => $phoneNumber,
                'paymentProvider' => $paymentProvider,
                'invoiceNumber' => $invoiceNumber,
                'transactionReference' => $transactionReference
            ];
            
            // Log the complete payment payload
            Log::info("Irembo Pay - Full payment initiation payload: " . json_encode($payload, JSON_PRETTY_PRINT));
            Log::info("Irembo Pay - Full payment initiation headers: " . json_encode($headers, JSON_PRETTY_PRINT));
            
            $response = Http::withHeaders($headers)
                            ->post($this->baseUrl . '/payments/transactions/initiate', $payload);
            
            Log::info("Irembo Pay - Complete payment initiation response: " . $response->body());
            Log::info("Irembo Pay - Payment initiation status code: " . $response->status());
            
            if ($response->successful() && $response->json('success') === true) {
                return [
                    'success' => true,
                    'data' => $response->json('data'),
                    'transactionReference' => $transactionReference,
                    'paymentProvider' => $paymentProvider
                ];
            }
            
            return [
                'success' => false,
                'message' => $response->json('message', 'Payment initiation failed'),
                'errors' => $response->json('errors', [])
            ];
        } catch (Exception $e) {
            Log::error("Irembo Pay - Payment initiation error: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'An error occurred while initiating the payment',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Verify the callback signature from Irembo Pay
     *
     * @param Request $request
     * @return bool
     */
    public function verifyCallbackSignature(Request $request): bool
    {
        try {
            $signatureHeader = $request->header('irembopay-signature');
            $payload = $request->getContent();
            
            if (!$signatureHeader) {
                Log::error("Irembo Pay - Missing signature header");
                return false;
            }
            
            // Extract timestamp and signature from header
            $elements = explode(',', $signatureHeader);
            $timestamp = null;
            $signatureHash = null;
            
            foreach ($elements as $element) {
                [$prefix, $value] = explode('=', $element);
                if (trim($prefix) === 't') {
                    $timestamp = trim($value);
                } else if (trim($prefix) === 's') {
                    $signatureHash = trim($value);
                }
            }
            
            if (!$timestamp || !$signatureHash) {
                Log::error("Irembo Pay - Invalid signature header format");
                return false;
            }
            
            // Prepare the signed payload string
            $signedPayload = $timestamp . '#' . $payload;
            
            // Calculate expected signature
            $expectedSignature = hash_hmac('sha256', $signedPayload, $this->secretKey);
            
            // Compare signatures (use hash_equals for timing-safe comparison)
            $isValid = hash_equals($expectedSignature, $signatureHash);
            
            // Check timestamp validity (within 5 minutes)
            $currentTime = time() * 1000; // Current time in milliseconds
            $timestampInt = (int)$timestamp;
            $isTimeValid = abs($currentTime - $timestampInt) <= 300000; // 5 minutes
            
            if (!$isTimeValid) {
                Log::warning("Irembo Pay - Timestamp validation failed. Timestamp: $timestamp, Current: $currentTime");
            }
            
            return $isValid && $isTimeValid;
            
        } catch (Exception $e) {
            Log::error("Irembo Pay - Signature verification error: " . $e->getMessage());
            return false;
        }
    }
    
    // Getter methods for debugging
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }
    
    public function getAccountId()
    {
        return $this->paymentAccountIdentifier;
    }
    
    /**
     * Determine the payment provider based on phone number
     *
     * @param string $phoneNumber
     * @return string
     */
    private function determinePaymentProvider(string $phoneNumber): string
    {
        // Clean the phone number
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // If the number starts with country code, extract the last 9-10 digits
        if (strlen($phoneNumber) > 10) {
            $phoneNumber = substr($phoneNumber, -10);
        }
        
        // Check the first 3 digits to determine provider
        $prefix = substr($phoneNumber, 0, 3);
        
        if (in_array($prefix, ['078', '079'])) {
            return 'MTN';
        } else if (in_array($prefix, ['072', '073'])) {
            return 'AIRTEL';
        }
        
        // Default to MTN if we can't determine
        return 'MTN';
    }
}
