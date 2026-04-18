<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ModeratorMiddleware — Ensures only moderators can access
 * the contribution moderation queue.
 */
class ModeratorMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->is_moderator) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Forbidden — moderator access required'], 403);
            }
            abort(403, 'You do not have moderator access.');
        }

        return $next($request);
    }
}
