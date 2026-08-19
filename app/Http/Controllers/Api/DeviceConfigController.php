<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enclosure;
use App\Services\AnimalKnowledgeBase;
use Illuminate\Http\JsonResponse;

class DeviceConfigController extends Controller
{
    public function show(Enclosure $device): JsonResponse
    {
        // Return 422 if the enclosure has no species_key.
        if (empty($device->species_key)) {
            return response()->json(['error' => 'Enclosure has no species_key assigned.'], 422);
        }

        // Return 404 if the species_key is not found in AnimalKnowledgeBase.
        $config = AnimalKnowledgeBase::getSpeciesByKey($device->species_key);
        if (!$config) {
            return response()->json(['error' => 'Species key not found in Knowledge Base.'], 404);
        }

        // On success, return HTTP 200 with the specified structure.
        return response()->json([
            "species_key"  => $device->species_key,
            "species_name" => $config['name'],
            "temperature"  => [
                "temp_min"       => $config['temperature']['temp_min'],
                "temp_ideal_min" => $config['temperature']['temp_ideal_min'],
                "temp_ideal_max" => $config['temperature']['temp_ideal_max'],
                "temp_max"       => $config['temperature']['temp_max']
            ],
            "humidity"     => [
                "humid_min"       => $config['humidity']['humid_min'],
                "humid_ideal_min" => $config['humidity']['humid_ideal_min'],
                "humid_ideal_max" => $config['humidity']['humid_ideal_max'],
                "humid_max"       => $config['humidity']['humid_max']
            ],
            "fetched_at"   => now()->toIso8601String()
        ], 200);
    }
}
