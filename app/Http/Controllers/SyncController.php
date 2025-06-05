<?php

namespace App\Http\Controllers;

use App\Models\Advert;
use App\Models\Machine;
use App\Models\MachineError;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SyncController extends Controller
{

    public function syncState(Request $request)
    {
        $machine = Machine::find($request->machine);

        if ($machine == null) {
            return response()->json(['message' => 'Unknown machine : ' . $request->machine ], 404);
        }

        $machine->update(['last_checked_in_at' => now()]);

        return response()->json([
            'inactivity_milliseconds' => 10 * 1000,
            'inventory_pin' => $machine->inventory_pin,
            'support_contact' => $machine->support_contact
        ]);

    }

    public function syncStateNew(Request $request, $machineId)
    {
        Log::info("Syncing state : " . $machineId);

        $machine = Machine::find($machineId);

        if ($machine == null) {
            return response()->json(['message' => 'Unknown machine : ' . $machineId ], 404);
        }

        $internetSpeed = $request->internet_speed;

        $machine->update([
            'internet_speed' => $internetSpeed == null ? 0 : $internetSpeed,
            'last_checked_in_at' => now()
        ]);


        $inventoryState = $request->json('inventory_state');

        Log::info('inventoryState', $inventoryState);

        foreach ($inventoryState as $state) {

            $machine->inventoryState()->updateOrCreate([
                'machine_id' => $machineId,
                'lane_id' => $state['lane_id']
            ], [
                'product_id' => $state['product_id'] ?? null,
                'max_quantity' => $this->getLaneMaxQuantity($state['lane_id'], $machineId),
                'quantity' => $state['quantity']
            ]);
        }

        return response()->json([
            'inactivity_milliseconds' => 10 * 1000,
            'long_inactivity_milliseconds' => 5 * 60 * 1000,
            'inventory_pin' => $machine->inventory_pin,
            'support_contact' => $machine->support_contact
        ]);

    }

    public function syncAdverts(Request $request)
    {
        $adverts = Advert::query();

        if ($request->machine != null) {
            $adverts = $adverts->whereHas('machines', function ($query) use ($request) {
                $query->where('machine_id', $request->machine);
            });
        }


        // where current date is between date_from and date_to
        $adverts = $adverts->where('date_from', '<=', date('Y-m-d'))
            ->where('date_to', '>=', date('Y-m-d'));

        // where current time is between time_from and time_to
        $adverts = $adverts->where('time_from', '<=', date('H:i:s'))
            ->where('time_to', '>=', date('H:i:s'));

        $adverts = $adverts->get()->map(function ($advert) {
            return [
                'id' => $advert->id,
                'order' => $advert->order,
                'duration' => $advert->duration,
                'type' => $advert->type,
                'media_url' => $advert->media_url,
                'updated_at' => $advert->updated_at->timestamp
            ];
        });
        return response()->json(['data' => $adverts]);
    }

    public function syncProducts(Request $request)
    {
        $products = Product::query();

        if ($request->last_updated != null) {
            $products = $products
                ->where('updated_at', '>', date("Y-m-d H:i:s", $request->last_updated));
        }

        $products = $products->get()->map(function($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category,
                'price' => $product->price,
                'image_url' => $product->image_url,
                'updated_at' => $product->updated_at->timestamp
            ];
        });
        return response()->json(['data' => $products]);
    }

    public function machineError(Request $request)
    {
        $error = new MachineError();
        $error->machine_id = $request->machine;
        $error->message = $request->message;
        $error->payment_reference_number = $request->referenceNumber;
        $error->save();


        return response()->json(['message' => 'Error logged successfully']);
    }

    private function getLaneMaxQuantity(mixed $param, $machineId)
    {
        if ($param == null) {
            return 0;
        }

        // split laneId by dot (.)
        $laneIdParts = explode('.', $param);
        $aisleRow = $laneIdParts[0];

        if ($aisleRow == 1) {
            if ($machineId == 34234)
                return 3;
            else
                return 5;
        } elseif ($aisleRow == 2) {
            return 7;
        } else {
            return 5;
        }
    }
}
