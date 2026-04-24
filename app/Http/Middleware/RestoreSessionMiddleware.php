<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestoreSessionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $cookieValue = $request->cookie('smartcash_uid');
        
        // Debug log
        \Illuminate\Support\Facades\Log::info('RestoreSession: cookie=' . ($cookieValue ?? 'null'));
        
        if ($cookieValue) {
            $userId = (int) $cookieValue;
            $user = User::find($userId);
            if ($user) {
                session(['user' => $user->name, 'user_id' => $user->id]);
                \Illuminate\Support\Facades\Log::info('RestoreSession: set session for user ' . $user->name);
            }
        }

        return $next($request);
    }
}