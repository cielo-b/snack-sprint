<?php

namespace App\Http\Controllers;

use App\Models\InventoryState;
use App\Models\Machine;
use Illuminate\Http\Request;

class MachineController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $machines = Machine::all();
        return view('machines', compact('machines'));
    }

    public function machineInventory(Request $request, $machineId)
    {
        $machine = Machine::find($machineId);
        $inventory =  InventoryState::with('product')->where('machine_id', $machineId)->orderBy('lane_id', 'ASC')->get();

        return view('machine-inventory', compact('machine', 'inventory'));
    }

    public function update(Request $request, $id)
    {
        $machine = Machine::find($id);
        $machine->name = $request->name;
        $machine->location = $request->location;
        $machine->support_contact = $request->support_contact;
        $machine->inventory_pin = $request->inventory_pin;

        $machine->save();

        return redirect()->route('machines.index')->with('success', 'Machine updated successfully!');
    }
}
