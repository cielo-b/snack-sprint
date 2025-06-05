<?php

namespace Database\Seeders;

use App\Models\PaymentProducts;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Events\OrderShipmentStatusUpdated;

class WebSocket extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

//        OrderShipmentStatusUpdated::dispatch();


        $paymentProducts = PaymentProducts::with('product')
            ->where('unit_price', 0)->get();

        foreach ($paymentProducts as $paymentProduct) {

            $paymentProduct->unit_price = $paymentProduct->product->price;
            $paymentProduct->save();

        }

    }
}
