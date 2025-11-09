<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class EnsureEmailBelongsToCompany
{
    public function handle(Request $request, Closure $next)
    {
        $email = $request->route('email');
        if (!$email) {
            return $next($request);
        }

        $companyId = (int)auth()->user()->company_id;

        $uUsers = User::where('email', $email)->first();
        $uWorkers = DB::table('company_worker')->where('email', $email)->first();

        if (($uUsers && (int)$uUsers->company_id !== $companyId) ||
            ($uWorkers && (int)$uWorkers->company_id !== $companyId)) {
            abort(403);
        }

        return $next($request);
    }
}

