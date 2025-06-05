<?php

namespace App\Http\Controllers;

use App\Models\Advert;
use App\Models\Machine;
use Illuminate\Http\Request;

class AdvertController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $machines = Machine::all();
        $adverts = Advert::all();
        return view('adverts', compact('adverts', 'machines'));
    }

    public function create(Request $request)
    {
        $request->validate([
            'machines' => 'required',
            'media' => 'required|mimes:jpeg,png,jpg,gif,mp4|max:200000',
        ]);

        $mediaName = "ad_" . time().'.'.$request->media->extension();
        $request->media->move(public_path('assets/adverts'), $mediaName);

        $dates = $request->daterange;
        $dates = explode(' - ', $dates);

        $advert = new Advert();
        $advert->name = $request->name;
        $advert->media_path = $mediaName;
        $advert->duration = $request->duration;
        $advert->date_from = $dates[0];
        $advert->date_to = $dates[1];
        $advert->time_from = $request->time_from;
        $advert->time_to = $request->time_to . ':59';
        $advert->save();

        foreach ($request->machines as $machine) {
            $advert->machines()->attach($machine);
        }

        return redirect()->route('advert.index')->with('success', 'Advert created successfully!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'machines' => 'required'
        ]);

        $advert = Advert::find($id);
        $advert->name = $request->name;
        $advert->duration = $request->duration;

        if ($request->hasFile('media')) {
            $request->validate([
                'media' => 'required|mimes:jpeg,png,jpg,gif,mp4|max:200000',
            ]);

            $mediaName = "ad_" . time() . '.' . $request->media->extension();
            $request->media->move(public_path('assets/adverts'), $mediaName);
            $advert->media_path = $mediaName;
        }

        $dates = $request->daterange;
        $dates = explode(' - ', $dates);

        $advert->date_from = $dates[0];
        $advert->date_to = $dates[1];
        $advert->time_from = $request->time_from;
        $advert->time_to = $request->time_to;
        $advert->save();

        $advert->machines()->detach();
        foreach ($request->machines as $machine) {
            $advert->machines()->attach($machine);
        }

        return redirect()->route('advert.index')->with('success', 'Advert updated successfully!');
    }

    public function newOrder(Request $request)
    {
        $newOrder = explode(',', $request->order);
        $i = 1;
        foreach ($newOrder as $id) {
            $advert = Advert::find($id);
            if (!$advert)
                continue;
            $advert->order = $i;
            $advert->save();
            $i++;
        }

        return redirect()->route('advert.index')->with('success', 'Advert reordered successfully!');
    }

    public function delete(Request $request, $id)
    {
        $advert = Advert::find($id);
        $advert->machines()->detach();
        $advert->delete();
        return redirect()->route('advert.index')->with('success', 'Advert deleted successfully!');
    }

}
