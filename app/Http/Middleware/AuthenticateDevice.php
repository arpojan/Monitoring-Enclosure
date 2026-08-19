<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Enclosure;

class AuthenticateDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        
        // Find Enclosure by device_key (Token)
        $device = Enclosure::where('device_key', $token)->first();

        if (!$device) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
