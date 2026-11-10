<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * POST /_deploy, called by GitHub Actions right after the FTP upload, because the
 * host is FTP-only (no SSH to run artisan). Header: X-Deploy-Token: <POS_DEPLOY_TOKEN>.
 *
 *  1. migrate --force   creates/updates the POS tables (its own database)
 *  2. db:seed --force   idempotent: first admin user, branch, category tabs
 *  3. optimize          cache config / routes / views for speed
 *
 * The response is deliberately terse: deploy logs of a public repo are public.
 */
class DeployController extends Controller
{
    public function __invoke(Request $request)
    {
        $token = (string) config('pos.deploy_token');
        abort_if($token === '' || ! hash_equals($token, (string) $request->header('X-Deploy-Token')), 404);

        @set_time_limit(900);
        @ignore_user_abort(true);

        $steps = [];
        try {
            Artisan::call('optimize:clear');
            $steps['migrate'] = Artisan::call('migrate', ['--force' => true]);
            $steps['seed'] = Artisan::call('db:seed', ['--force' => true]);

            $steps['optimize'] = Artisan::call('optimize');
        } catch (Throwable $e) {
            Log::error('Deploy hook failed', ['exception' => $e]);

            // First line only, SQL text cut off: enough to diagnose (e.g. "Access denied
            // for user ..." / "Unknown database ...") without leaking queries. PDO never
            // includes the password in its messages.
            $msg = strtok($e->getMessage(), "\n");
            $msg = preg_replace('/\s*\(Connection: .*$/s', '', (string) $msg);

            return response()->json([
                'ok' => false, 'steps' => $steps,
                'error' => class_basename($e).': '.mb_substr($msg, 0, 300),
            ], 500);
        }

        $failed = array_filter($steps, fn ($code) => is_int($code) && $code !== 0);

        return response()->json(['ok' => ! $failed, 'steps' => $steps], $failed ? 500 : 200);
    }
}
