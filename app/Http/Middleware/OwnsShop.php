<?php

namespace App\Http\Middleware;

use App\Models\Shop;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OwnsShop
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $shopParam = $request->route('shop');
        $shopId = $shopParam instanceof Shop ? $shopParam->id : $shopParam;

        if (! $shopId || ! $user->shops()->whereKey($shopId)->exists()) {
            return response()->json([
                'message' => 'You do not have access to this shop.',
            ], 403);
        }

        return $next($request);
    }
}
