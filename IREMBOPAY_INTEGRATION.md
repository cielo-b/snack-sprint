# Irembo Pay Integration for Snack Sprint

This document explains how the Irembo Pay payment integration has been implemented in the Snack Sprint backend.

## Overview

The integration with Irembo Pay follows these steps:

1. Create an invoice in Irembo Pay
2. Initiate payment for the invoice
3. Handle callbacks from Irembo Pay for payment updates
4. Check status of pending payments periodically

## Configuration

The following environment variables must be set to enable and configure Irembo Pay:

```
USE_IREMBOPAY=true
IREMBOPAY_BASE_URL=https://api.sandbox.irembopay.com
IREMBOPAY_SECRET_KEY=your_secret_key_here
IREMBOPAY_ACCOUNT_ID=SNACK-RWF
```

Set `USE_IREMBOPAY` to `true` to enable the Irembo Pay integration. If set to `false`, the system will use the original payment gateway.

## How It Works

### Invoice Creation

When a payment is initiated, the system:

1. Creates a payment record in the database
2. Prepares the items for the invoice based on products in the cart
3. Sends a request to Irembo Pay to create an invoice
4. Stores the invoice number and related information

### Payment Initiation

After invoice creation:

1. The system determines the payment provider (MTN or AIRTEL) based on the phone number
2. Sends a request to Irembo Pay to initiate the payment
3. Returns the appropriate response to the client

### Callback Handling

Irembo Pay will send a callback to the endpoint `/api/payment-callback` with payment status updates. The system:

1. Verifies the signature of the callback using the secret key
2. Updates the payment status in the database
3. Dispatches events for successful or failed payments

### Periodic Status Checking

A scheduled task checks pending payments periodically:

1. For Irembo Pay payments, it checks if the payment is expired (older than 30 minutes)
2. For the original payment gateway, it checks the status via API calls

## Payment Statuses

- 0: Pending
- 1: Successful
- 2: Failed
- 3: Insufficient Balance
- 4: Service Unavailable
- 5: Phone Number Issue
- 6: Phone Number Not Registered for Mobile Money

## Database Changes

New fields added to the `payments` table:
- `invoice_number`: The Irembo Pay invoice number
- `payment_provider`: The payment provider (e.g., MTN, AIRTEL)
- `transaction_reference`: A reference for the transaction
- `expiry_at`: When the invoice expires
- `invoice_response`: The full response from invoice creation
- `signature_header`: The signature header from callbacks

New field added to the `products` table:
- `product_code`: Optional product code for Irembo Pay

## Migrating to Irembo Pay

To migrate to Irembo Pay:

1. Run the migrations:
   ```
   php artisan migrate
   ```

2. Update the `.env` file with the Irembo Pay configuration.

3. Set `USE_IREMBOPAY` to `true` to enable the Irembo Pay integration.

## Reverting to Original Gateway

To revert to the original payment gateway, set `USE_IREMBOPAY` to `false` in your `.env` file. The system will automatically use the original payment flow.
