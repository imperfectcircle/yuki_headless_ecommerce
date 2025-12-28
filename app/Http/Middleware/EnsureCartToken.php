<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCartToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->hasHeader('X-Cart-Token') && !$request->hasHeader('X-cart-token')) {
            return response()->json([
                'message' => 'Cart token is required.',
                'error' => 'missing_cart_token'
            ], 400);
        }

        if ($request->hasHeader('X-cart-token')) {
            $request->headers->set('X-Cart-Token', $request->header('X-cart-token'));
        }
        
        return $next($request);
    }
}
