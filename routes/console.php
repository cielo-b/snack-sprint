<?php

use App\Tasks\CheckPendingPaymentStatus;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('test:irembopay {product_id=1} {quantity=1} {phone=0781234567}', function ($product_id, $quantity, $phone) {
    $this->info('Testing Irembo Pay integration...');
    $this->info("Using product ID: $product_id, Quantity: $quantity, Phone: $phone");
    
    // Create a test product in the database if it doesn't exist
    $product = \App\Models\Product::firstOrCreate(
        ['id' => 1],
        [
            'name' => 'Test Product',
            'price' => 2000,
            'image_path' => 'test.jpg',
            'product_code' => 'PC-39975bcf31',  // Valid product code from Irembo Pay
            'category' => 'Snacks'
        ]
    );
    
    // Create a second test product
    $product2 = \App\Models\Product::firstOrCreate(
        ['id' => 2],
        [
            'name' => 'Test Product 2',
            'price' => 3000,
            'image_path' => 'test2.jpg',
            'product_code' => 'PC-9522817496',  // Second valid product code
            'category' => 'Drinks'
        ]
    );
    
    $machine = \App\Models\Machine::firstOrCreate(
        ['id' => 1],
        [
            'name' => 'Test Machine',
            'location' => 'Test Location'
        ]
    );
    
    // Get the product to calculate the amount
    $product = \App\Models\Product::find($product_id);
    if (!$product) {
        $this->error("Product with ID $product_id not found!");
        return 1;
    }
    
    $amount = $product->price * $quantity;
    
    // Prepare the request data
    $requestData = [
        'machine' => 1,
        'phone_number' => $phone,
        'amount' => $amount,
        'cart' => json_encode([
            [
                'product_id' => $product_id,
                'quantity' => $quantity,
                'unit_price' => $product->price
            ]
        ])
    ];
    
    // Call the payment controller with debug mode
    putenv('APP_DEBUG=true');
    
    // Capture the log output
    $initialLogLevel = Log::getLogger()->getHandlers()[0]->getLevel();
    Log::getLogger()->getHandlers()[0]->setLevel(\Monolog\Logger::DEBUG);
    
    // Create a request
    $request = new \Illuminate\Http\Request();
    $request->replace($requestData);
    
    // Call the payment controller
    $controller = app(\App\Http\Controllers\PaymentController::class);
    $response = $controller->initializePayment($request);
    
    // Reset log level
    Log::getLogger()->getHandlers()[0]->setLevel($initialLogLevel);
    
    // Output the response
    $this->info('Response:');
    $this->line(json_encode(json_decode($response->getContent()), JSON_PRETTY_PRINT));
})->purpose('Test Irembo Pay integration');

Artisan::command('test:payment {system=original} {product_id=1} {quantity=1} {phone=0781234567}', function ($system, $product_id, $quantity, $phone) {
    // Set the payment system
    if ($system == 'irembopay') {
        $this->info('Testing Irembo Pay integration...');
        putenv('USE_IREMBOPAY=true');
    } else {
        $this->info('Testing original payment gateway...');
        putenv('USE_IREMBOPAY=false');
    }
    
    $this->info("Using product ID: $product_id, Quantity: $quantity, Phone: $phone");
    
    // Make sure the products exist
    // First product with Irembo Pay product code 1
    \App\Models\Product::firstOrCreate(
        ['id' => 1],
        [
            'name' => 'Test Product 1',
            'price' => 2000,
            'image_path' => 'test1.jpg',
            'product_code' => 'PC-39975bcf31',
            'category' => 'Snacks'
        ]
    );
    
    // Second product with Irembo Pay product code 2
    \App\Models\Product::firstOrCreate(
        ['id' => 2],
        [
            'name' => 'Test Product 2',
            'price' => 3000,
            'image_path' => 'test2.jpg',
            'product_code' => 'PC-9522817496',
            'category' => 'Drinks'
        ]
    );
    
    // Make sure test machine exists
    \App\Models\Machine::firstOrCreate(
        ['id' => 1],
        [
            'name' => 'Test Machine',
            'location' => 'Test Location'
        ]
    );
    
    // Get the product to calculate the amount
    $product = \App\Models\Product::find($product_id);
    if (!$product) {
        $this->error("Product with ID $product_id not found!");
        return 1;
    }
    
    $amount = $product->price * $quantity;
    
    // Prepare the request data
    $requestData = [
        'machine' => 1,
        'phone_number' => $phone,
        'amount' => $amount,
        'cart' => json_encode([
            [
                'product_id' => $product_id,
                'quantity' => $quantity,
                'unit_price' => $product->price
            ]
        ])
    ];
    
    // Create a request
    $request = new \Illuminate\Http\Request();
    $request->replace($requestData);
    
    // Call the payment controller
    $controller = app(\App\Http\Controllers\PaymentController::class);
    $response = $controller->initializePayment($request);
    
    // Output the response
    $this->info('Response:');
    $this->line(json_encode(json_decode($response->getContent()), JSON_PRETTY_PRINT));
})->purpose('Test payment system (original or irembopay)');

Schedule::call(function() {
    app(CheckPendingPaymentStatus::class)();
})->everyFifteenSeconds();
