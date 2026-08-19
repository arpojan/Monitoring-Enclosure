<?php

namespace App\Http\Controllers;

use App\Actions\Enclosure\CreateEnclosureAction;
use App\Models\Enclosure;
use App\Models\EnclosureParameter;
use App\Models\ParameterHistory;
use App\Services\AnimalKnowledgeBase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SelectEnclosureController extends Controller
{
    /**
     * Tampilkan halaman pilih kandang dengan data sensor terbaru.
     */
    public function index()
    {
        // Hanya tampilkan kandang milik user yang sedang login
        $enclosures = Enclosure::with([
            'parameters',
            'sensorLogs' => fn($q) => $q->orderByDesc('logged_at')->limit(1),
            'stabilityScores' => fn($q) => $q->orderByDesc('analyzed_date')->limit(1),
        ])->where('user_id', auth()->id())->get();

        $animalCategories = AnimalKnowledgeBase::getCategories();
        $animalSpecies = AnimalKnowledgeBase::getSpecies();

        return view('enclosure.select', compact('enclosures', 'animalCategories', 'animalSpecies'));
    }

    /**
     * Update pengaturan kandang dari modal.
     * Update nama, habitat, jenis hewan, dan gambar.
     * Jika jenis hewan berubah, sesuaikan parameter kelembaban.
     */
    public function store(Request $request)
    {
        $request->validate([
            'enclosure_id'  => 'required|exists:enclosures,id',
            'name'          => 'required|string|max:255',
            'target_habitat'=> 'nullable|string|max:255',
            'jenis_hewan'   => 'nullable|string|max:255',
            'image'         => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ]);

        $enclosure = Enclosure::findOrFail($request->enclosure_id);
        $enclosure->name          = $request->name;
        $enclosure->target_habitat= $request->target_habitat;
        $enclosure->jenis_hewan   = $request->jenis_hewan;
        $enclosure->species_key   = $request->jenis_hewan;

        if ($request->hasFile('image')) {
            if ($enclosure->image_path && Storage::disk('public')->exists($enclosure->image_path)) {
                Storage::disk('public')->delete($enclosure->image_path);
            }
            $enclosure->image_path = $request->file('image')->store('enclosures', 'public');
        }

        DB::transaction(function () use ($enclosure, $request) {
            $isAnimalChanged = $enclosure->isDirty('jenis_hewan') && $request->jenis_hewan;
            $enclosure->save();

            if ($isAnimalChanged) {
                $this->adjustParametersForAnimal($enclosure, $request->jenis_hewan);
            }
        });

        return redirect()->route('enclosure.select')->with('success', 'Pengaturan kandang berhasil diubah.');
    }

    /**
     * Buat kandang baru.
     * Logika bisnis (default parameter, history) ditangani oleh CreateEnclosureAction.
     */
    public function create(Request $request, CreateEnclosureAction $action)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'target_habitat'=> 'nullable|string|max:255',
            'jenis_hewan'   => 'nullable|string|max:255',
            'image'         => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ]);

        $action->execute(
            $request->only(['name', 'target_habitat', 'jenis_hewan']),
            $request->file('image')
        );

        return redirect()->route('enclosure.select')->with('success', 'Kandang baru berhasil ditambahkan.');
    }

    /**
     * Regenerate device_key untuk enclosure tertentu.
     * Hanya pemilik kandang yang boleh melakukan ini.
     */
    public function regenerateKey(int $id)
    {
        $enclosure = Enclosure::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $newKey = Str::random(32);
        $enclosure->update(['device_key' => $newKey]);

        return redirect()->route('enclosure.select')
            ->with('success', 'Device key berhasil diperbarui.')
            ->with('regenerated_enclosure_id', $id)
            ->with('new_device_key', $newKey);
    }

    /**
     * Sesuaikan parameter kelembaban enclosure berdasarkan jenis hewan baru.
     * Dipanggil ketika jenis hewan berubah saat update.
     */
    private function adjustParametersForAnimal(Enclosure $enclosure, string $jenisHewan): void
    {
        $config = AnimalKnowledgeBase::getSpeciesByKey($jenisHewan);
        if (!$config) {
            return;
        }

        $oldParameters = $enclosure->parameters;
        if (!$oldParameters) {
            return;
        }

        $oldParameters->update([
            'humidity_min'             => $config['humidity']['humid_ideal_min'],
            'humidity_max'             => $config['humidity']['humid_ideal_max'],
            'misting_bottom_threshold' => $config['humidity']['humid_ideal_min'],
            'misting_top_threshold'    => $config['humidity']['humid_ideal_max'],
        ]);

        ParameterHistory::create([
            'enclosure_id'         => $enclosure->id,
            'source'               => 'manual',
            'changed_by'           => auth()->id(),
            'old_bottom_humidity'  => $oldParameters->getOriginal('misting_bottom_threshold'),
            'old_top_humidity'     => $oldParameters->getOriginal('misting_top_threshold'),
            'old_duration_seconds' => $oldParameters->getOriginal('misting_duration_seconds'),
            'new_bottom_humidity'  => $oldParameters->misting_bottom_threshold,
            'new_top_humidity'     => $oldParameters->misting_top_threshold,
            'new_duration_seconds' => $oldParameters->misting_duration_seconds,
        ]);
    }
}
