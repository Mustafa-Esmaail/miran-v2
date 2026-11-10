<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiClientAbility
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $client = $request->attributes->get('api_client');

        if (! $client instanceof ApiClient || ! $client->hasAbility($ability)) {
            return response()->json([
                'message' => 'This API key is not allowed to access this resource.',
                'code' => 'ability_denied',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
