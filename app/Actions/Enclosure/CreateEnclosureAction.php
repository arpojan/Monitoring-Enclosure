<?php

namespace App\Actions\Enclosure;

use App\Models\Enclosure;
use App\Models\EnclosureParameter;
use App\Models\ParameterHistory;
use App\Services\AnimalKnowledgeBase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreateEnclosureAction
{
    /**
     * Buat kandang baru beserta parameter default-nya.
     *
     * Menangani: upload gambar, generate device_key, set parameter default
     * berdasarkan jenis hewan, dan catat ParameterHistory awal.
     */
    public function execute(array $data, ?UploadedFile $image = null): Enclosure
    {
        return DB::transaction(function () use ($data, $image) {
            $imagePath = null;
            if ($image) {
                $imagePath = $image->store('enclosures', 'public');
            }

            $enclosure = Enclosure::create([
                'user_id'        => auth()->id(),
                'name'           => $data['name'],
                'target_habitat' => $data['target_habitat'] ?? null,
                'jenis_hewan'    => $data['jenis_hewan']    ?? null,
                'image_path'     => $imagePath,
                'device_key'     => Str::random(16),
                'is_active'      => true,
            ]);

            [$bottom, $top] = $this->resolveDefaultHumidityRange($data['jenis_hewan'] ?? null);

            $parameters = EnclosureParameter::create([
                'enclosure_id'             => $enclosure->id,
                'humidity_min'             => $bottom,
                'humidity_max'             => $top,
                'misting_bottom_threshold' => $bottom,
                'misting_top_threshold'    => $top,
                'misting_duration_seconds' => 10,
                'is_misting_auto'          => true,
            ]);

            ParameterHistory::create([
                'enclosure_id'         => $enclosure->id,
                'source'               => 'manual',
                'changed_by'           => auth()->id(),
                'new_bottom_humidity'  => $parameters->misting_bottom_threshold,
                'new_top_humidity'     => $parameters->misting_top_threshold,
                'new_duration_seconds' => $parameters->misting_duration_seconds,
            ]);

            return $enclosure->fresh('parameters');
        });
    }

    /**
     * Tentukan range kelembaban default berdasarkan jenis hewan.
     * Fallback ke 60–80% jika hewan tidak dikenali atau tidak dipilih.
     *
     * @return array{0: float, 1: float} [bottom, top]
     */
    private function resolveDefaultHumidityRange(?string $jenisHewan): array
    {
        if ($jenisHewan) {
            $config = AnimalKnowledgeBase::getSpeciesConfig($jenisHewan);
            if ($config) {
                return [$config['min'], $config['max']];
            }
        }

        return [60.0, 80.0];
    }
}
