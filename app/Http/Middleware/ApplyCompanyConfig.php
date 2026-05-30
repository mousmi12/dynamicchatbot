<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use App\Services\CompanyConfigService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyCompanyConfig
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
         $slug = $request->route('company_slug');

        if ($slug) {
            $company = Company::where('slug', $slug)
                              ->where('is_active', true)
                              ->with('settings')
                              ->first();

            if ($company) {
                CompanyConfigService::apply($company);
                app()->instance('current_company', $company);
            }
        }

        return $next($request);
    }
}
