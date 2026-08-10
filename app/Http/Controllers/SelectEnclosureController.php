<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Enclosure;
use App\Models\EnclosureParameter;
use App\Models\ParameterHistory;
use App\Services\AnimalKnowledgeBase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SelectEnclosureController extends Controller
{
    /**
     * Tampilkan halaman pilih kandang dengan data sensor terbaru.
     */
    public function index()
    {
        // Eager load: latest sensor log + latest stability score + parameters
        $enclosures = Enclosure::with([
            'parameters',
            'sensorLogs' => function ($query) {
                $query->orderByDesc('logged_at')->limit(1);
            },
            'stabilityScores' => function ($query) {
                $query->orderByDesc('analyzed_date')->limit(1);
            },
        ])->get();

        $animalKnowledgeBase = AnimalKnowledgeBase::getAnimals();

        return view('enclosure.select', compact('enclosures', 'animalKnowledgeBase'));
    }

    /**
     * Update pengaturan kandang dari modal.
     */
    public function store(Request $request)
    {
        $request->validate([
            'enclosure_id' => 'required|exists:enclosures,id',
            'name' => 'required|string|max:255',
            'target_habitat' => 'nullable|string|max:255',
            'jenis_hewan' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096', // Max 4MB
        ]);

        $enclosure = Enclosure::findOrFail($request->enclosure_id);
        $enclosure->name = $request->name;
        $enclosure->target_habitat = $request->target_habitat;
        $enclosure->jenis_hewan = $request->jenis_hewan;
        
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($enclosure->image_path && Storage::disk('public')->exists($enclosure->image_path)) {
                Storage::disk('public')->delete($enclosure->image_path);
            }
            
            // Store new image
            $path = $request->file('image')->store('enclosures', 'public');
            $enclosure->image_path = $path;
        }

        DB::transaction(function () use ($enclosure, $request) {
            $isAnimalChanged = $enclosure->isDirty('jenis_hewan') && $request->jenis_hewan;
            $enclosure->save();

            // Jika jenis hewan baru saja diubah atau dipilih, sesuaikan parameter humiditas
            if ($isAnimalChanged) {
                $config = AnimalKnowledgeBase::getSpeciesConfig($request->jenis_hewan);
                if ($config) {
                    $oldParameters = $enclosure->parameters;
                    
                    if ($oldParameters) {
                        $oldParameters->update([
                            'humidity_min'             => $config['min'],
                            'humidity_max'             => $config['max'],
                            'misting_bottom_threshold' => $config['min'],
                            'misting_top_threshold'    => $config['max'],
                        ]);

                        ParameterHistory::create([
                            'enclosure_id'        => $enclosure->id,
                            'source'              => 'manual',
                            'changed_by'          => auth()->id(),
                            'old_bottom_humidity' => $oldParameters->getOriginal('misting_bottom_threshold'),
                            'old_top_humidity'    => $oldParameters->getOriginal('misting_top_threshold'),
                            'old_duration_seconds'=> $oldParameters->getOriginal('misting_duration_seconds'),
                            'new_bottom_humidity' => $oldParameters->misting_bottom_threshold,
                            'new_top_humidity'    => $oldParameters->misting_top_threshold,
                            'new_duration_seconds'=> $oldParameters->misting_duration_seconds,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('enclosure.select')->with('success', 'Pengaturan kandang berhasil diubah.');
    }

    /**
     * Buat kandang baru.
     */
    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'target_habitat' => 'nullable|string|max:255',
            'jenis_hewan' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ]);

        DB::transaction(function () use ($request) {
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('enclosures', 'public');
            }

            $deviceKey = Str::random(16);

            $enclosure = Enclosure::create([
                'user_id' => auth()->id(),
                'name' => $request->name,
                'target_habitat' => $request->target_habitat,
                'jenis_hewan' => $request->jenis_hewan,
                'image_path' => $imagePath,
                'device_key' => $deviceKey,
                'is_active' => true,
            ]);

            // Set default parameters
            $bottom = 60.0;
            $top = 80.0;

            if ($request->jenis_hewan) {
                $config = AnimalKnowledgeBase::getSpeciesConfig($request->jenis_hewan);
                if ($config) {
                    $bottom = $config['min'];
                    $top = $config['max'];
                }
            }

            $parameters = EnclosureParameter::create([
                'enclosure_id' => $enclosure->id,
                'humidity_min' => $bottom,
                'humidity_max' => $top,
                'misting_bottom_threshold' => $bottom,
                'misting_top_threshold' => $top,
                'misting_duration_seconds' => 10,
                'is_misting_auto' => true,
            ]);

            ParameterHistory::create([
                'enclosure_id'        => $enclosure->id,
                'source'              => 'manual',
                'changed_by'          => auth()->id(),
                'new_bottom_humidity' => $parameters->misting_bottom_threshold,
                'new_top_humidity'    => $parameters->misting_top_threshold,
                'new_duration_seconds'=> $parameters->misting_duration_seconds,
            ]);
        });

        return redirect()->route('enclosure.select')->with('success', 'Kandang baru berhasil ditambahkan.');
    }
}
