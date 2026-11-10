<?php

namespace App\Http\Middleware;

use App\Models\Employee;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureReferral
{
    public function handle(Request $request, Closure $next): Response
    {
        $ref = $request->query('source');

        if ($ref && ! $request->session()->has('referral_employee_id') && ! $request->session()->has('marketing_source')) {
            $request->session()->put('marketing_source', $ref);

            $employee = Employee::query()
                ->whereRaw('LOWER(employee_code) = ?', [mb_strtolower($ref)])
                ->where('is_active', true)
                ->first();

            if ($employee) {
                $request->session()->put('referral_employee_id', $employee->id);
            }
        }

        return $next($request);
    }
}
