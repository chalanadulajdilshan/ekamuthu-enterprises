<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;

/**
 * Resolves the active branch (store) for the session and shares it with every
 * view. Branch list is cached so this costs zero queries per request.
 */
class BranchContext
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if ($user && ! $user->is_active) {
            Auth::logout();

            return redirect()->route('login')->withErrors(['email' => 'Your account is disabled.']);
        }

        $branches = Cache::rememberForever('branches.active', fn () => Branch::where('is_active', true)->orderBy('id')->get());

        $id = (int) $request->session()->get('branch_id');
        // Non-admin users are pinned to their own branch.
        if ($user && ! $user->isAdmin() && $user->branch_id) {
            $id = $user->branch_id;
        }
        $branch = $branches->firstWhere('id', $id) ?? $branches->first();

        abort_if(! $branch, 500, 'No active branch configured. Run: php artisan db:seed');

        $request->session()->put('branch_id', $branch->id);
        app()->instance('currentBranch', $branch);
        View::share('currentBranch', $branch);
        View::share('branches', $branches);

        return $next($request);
    }
}
