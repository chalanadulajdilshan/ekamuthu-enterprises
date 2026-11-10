<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * POST /_deploy, called by GitHub Actions right after the FTP upload, because the
 * host is FTP-only (no SSH to run artisan). Header: X-Deploy-Token: <POS_DEPLOY_TOKEN>.
 *
 *  1. migrate --force       creates/updates pos_* tables only (main tables untouched)
 *  2. db:seed --force       idempotent: admin user, branch, category tabs
 *  3. pos:import-ekamuthu   until it has succeeded once (then flagged in settings), so
 *                           later deploys never overwrite prices/stock edited in the POS
 *  4. optimize              cache config / routes / views for speed
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

            if (Setting::val('ekamuthu_imported_at') === null) {
                $steps['import'] = Artisan::call('pos:import-ekamuthu');
                if ($steps['import'] === 0) {
                    Setting::store(['ekamuthu_imported_at' => now()->toDateTimeString()]);
                }
            } else {
                $steps['import'] = 'skipped';
            }

            $steps['optimize'] = Artisan::call('optimize');
        } catch (Throwable $e) {
            Log::error('Deploy hook failed', ['exception' => $e]);

            return response()->json(['ok' => false, 'steps' => $steps, 'error' => class_basename($e).' (see storage/logs)'], 500);
        }

        $failed = array_filter($steps, fn ($code) => is_int($code) && $code !== 0);

        return response()->json(['ok' => ! $failed, 'steps' => $steps], $failed ? 500 : 200);
    }
}
