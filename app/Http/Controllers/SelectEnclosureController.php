<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Enclosure;

class SelectEnclosureController extends Controller
{
    /**
     * Set active enclosure in session and redirect to dashboard.
     */

    public function index()
    {
        $enclosures = Enclosure::all();
        return view('enclosure.select', compact('enclosures'));
    }

    public function store(Request $request)
    {
        $request->validate([
    'bottom_humidity' => 'required|integer|min:0|max:100',
    'top_humidity' => 'required|integer|min:0|max:100',
    'misting_duration' => 'required|integer|min:1',
]);

$config->bottom_humidity = (int) $request->bottom_humidity;
$config->top_humidity = (int) $request->top_humidity;
$config->misting_duration = (int) $request->misting_duration;
$config->save();

        // $request->validate([
        //     'enclosure_id' => 'required|exists:enclosures,id',
        //     'name' => 'required|string|max:255',
        // ]);

        // Cari enclosure
        $enclosure = Enclosure::findOrFail($request->enclosure_id);

        // Update data
        $enclosure->name = $request->name;

        // Simpan
        $enclosure->save();

        return redirect()->route('enclosure.select');
    }
}
