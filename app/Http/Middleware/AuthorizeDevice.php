<?php

namespace App\Http\Middleware;

use App\Models\Enclosure;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $enclosureId = $request->route('id') ?? $request->input('enclosure_id');

        if (!$enclosureId) {
            return response()->json(['success' => false, 'message' => 'Enclosure ID is required.'], 400);
        }

        $enclosure = Enclosure::find($enclosureId);

        if (!$enclosure) {
            return response()->json(['success' => false, 'message' => 'Enclosure not found.'], 404);
        }

        if (!$this->isAuthorized($request, $enclosure)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized device. Please check X-Device-Key header.',
            ], 401);
        }

        return $next($request);
    }

    private function isAuthorized(Request $request, Enclosure $enclosure): bool
    {
        if (blank($enclosure->device_key)) {
            return true; // Dev mode: no key set
        }

        $incoming = (string) ($request->header('X-Device-Key') ?? $request->query('device_key', ''));

        return hash_equals((string) $enclosure->device_key, $incoming);
    }
}
