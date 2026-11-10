<?php

namespace App\Http\Middleware;

use App\Enums\ImpersonationEventType;
use App\Services\ImpersonationLogService;
use Closure;
use Illuminate\Http\Request;
use Lab404\Impersonate\Services\ImpersonateManager;
use Symfony\Component\HttpFoundation\Response;

class LogImpersonatedActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! is_impersonating()) {
            return $response;
        }

        $manager = app(ImpersonateManager::class);
        $studentId = $request->user($manager->getImpersonatorGuardUsingName())?->getAuthIdentifier();

        app(ImpersonationLogService::class)->record(
            adminId: $manager->getImpersonatorId() !== null ? (int) $manager->getImpersonatorId() : null,
            studentId: $studentId !== null ? (int) $studentId : null,
            eventType: ImpersonationEventType::RouteVisited,
            routeName: $request->route()?->getName(),
            url: $request->fullUrl(),
            method: $request->method(),
            meta: $request->route()?->parameters() ?? [],
        );

        return $response;
    }
}
