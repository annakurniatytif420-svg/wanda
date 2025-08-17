<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureMua
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->role === 'mua') {
            return $next($request);
        }

        return response()->json(['error' => 'Unauthorized (MUA only)'], 403);
    }
}
